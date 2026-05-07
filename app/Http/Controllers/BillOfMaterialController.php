<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\BillOfMaterialItem;
use App\Models\BillOfMaterialAuxiliary;
use App\Models\BillOfMaterialProcess;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\AuxiliaryMaterial;
use Illuminate\Http\Request;

class BillOfMaterialController extends Controller
{
    private function getCurrentPOR(): ?\App\Models\OverheadPor
    {
        $period = date('Y-m');
        return \App\Models\OverheadPor::where('dasar_alokasi', 'jam_tk_langsung')
            ->where('periode', $period)
            ->first();
    }
    private function calcDisplayPrice($material, string $materialType): array
        {
            $conv = $material->unitConversions->where('from_unit', $material->unit)->first();
            $displayUnit = $conv ? $conv->to_unit : $material->unit;

            $idColumn = $materialType === 'auxiliary' ? 'auxiliary_material_id' : 'raw_material_id';

            // Hitung average price per satuan KECIL (display unit)
            // quantity di purchase_items = qty dalam satuan BELI (BUNGKUS/PCS/KG)
            // conversion_factor = berapa satuan kecil per satuan beli (1 BUNGKUS = 26 GRAM)
            // qty dalam satuan kecil = quantity * conversion_factor
            // harga per satuan kecil = unit_price / conversion_factor
            $purchaseItems = \App\Models\PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                ->where("purchase_items.{$idColumn}", $material->id)
                ->where('purchases.status', 'received')
                ->select('purchase_items.*', 'purchases.ppn_rate', 'purchases.discount_rate')
                ->get();

            $totalQtySmall = 0.0;
            $totalCost = 0.0;
            foreach ($purchaseItems as $item) {
                $qtyBeli = (float)($item->quantity ?? 0);
                $convFactor = (float)($item->conversion_factor ?? 1);
                $unitPrice = (float)($item->unit_price ?? 0);
                $ppnRate = (float)($item->ppn_rate ?? 0);
                $discRate = (float)($item->discount_rate ?? 0);
                $taxType = $item->tax_type ?? 'after_tax';

                // qty dalam satuan kecil = qty beli * conversion_factor
                $qtySmall = $convFactor > 0 ? $qtyBeli * $convFactor : $qtyBeli;

                // DPP setelah diskon per satuan beli
                $dpp = ($taxType === 'after_tax' && $ppnRate > 0) ? $unitPrice / (1 + $ppnRate / 100) : $unitPrice;
                $effective = $discRate > 0 ? $dpp * (1 - $discRate / 100) : $dpp;

                // harga per satuan kecil = harga per satuan beli / conversion_factor
                $pricePerSmall = $convFactor > 0 ? $effective / $convFactor : $effective;

                $totalQtySmall += $qtySmall;
                $totalCost += $qtySmall * $pricePerSmall;
            }

            $avgPriceSmall = $totalQtySmall > 0 ? $totalCost / $totalQtySmall : (float)($material->price_per_unit ?? 0);

            return ['unit' => $displayUnit, 'price' => round($avgPriceSmall)];
        }

    public function index()
    {
        $boms = BillOfMaterial::with(['product.auxiliaryMaterials', 'items', 'auxiliaries', 'processes'])
            ->paginate(15);
        
        return view('bill-of-materials.index', compact('boms'));
    }

    public function create()
    {
        // Hanya tampilkan produk yang belum punya BOM
        $existingBomProductIds = BillOfMaterial::pluck('product_id')->toArray();
        $products = Product::whereNotIn('id', $existingBomProductIds)->get();

        if ($products->isEmpty()) {
            return redirect()->route('bill-of-materials.index')
                ->with('info', 'Semua produk sudah memiliki BOM.');
        }

        $rawMaterials = RawMaterial::with('unitConversions')->get()->each(function($m) {
            $result = $this->calcDisplayPrice($m, 'raw');
            $m->display_unit = $result['unit'];
            $m->display_price = $result['price'];
        });
        $auxiliaryMaterials = AuxiliaryMaterial::with('unitConversions')->get()->each(function($m) {
            $result = $this->calcDisplayPrice($m, 'auxiliary');
            $m->display_unit = $result['unit'];
            $m->display_price = $result['price'];
        });
        
        // Get current month period (YYYY-MM format)
        $currentPeriod = date('Y-m');
        
        // Get overhead POR for current month (fallback ke terbaru jika tidak ada)
        $currentPOR = $this->getCurrentPOR();
        
        // Get BTKL employees (like in pemesanan)
        $btklEmployees = \App\Models\Employee::where('employee_type', 'BTKL')->get();
        
        // Hitung total tarif per jam dari semua karyawan BTKL aktif
        // tarif = base_salary / jam_kerja_per_bulan
        $btklRatePerHour = 0;
        foreach ($btklEmployees as $emp) {
            $jam = (float)($emp->jam_kerja_per_bulan ?? 0);
            $salary = (float)($emp->base_salary ?? 0);
            $rate = ($jam > 0 && $salary > 0) ? $salary / $jam : 0;
            $btklRatePerHour += $rate;
        }
        
        return view('bill-of-materials.create', compact(
            'products', 
            'rawMaterials', 
            'auxiliaryMaterials', 
            'currentPOR',
            'currentPeriod',
            'btklEmployees',
            'btklRatePerHour'
        ));
    }

    public function store(Request $request)
    {
        // Basic validation first
        \Log::info('BOM Store - Request received');
        \Log::info('Request data: ' . json_encode($request->all()));
        
        // reuse same rules as store to ensure proper types
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'description' => 'nullable|string',
            'production_time_minutes' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string',
            'auxiliaries' => 'nullable|array',
            'auxiliaries.*.auxiliary_material_id' => 'required|exists:auxiliary_materials,id',
            'auxiliaries.*.quantity' => 'required|numeric|min:0',
            'auxiliaries.*.unit_cost' => 'nullable|numeric|min:0',
            'auxiliaries.*.unit' => 'nullable|string',
        ]);

        \Log::info('Validation passed');

        // Cek apakah produk sudah punya BOM
        $existing = BillOfMaterial::where('product_id', $request->product_id)->first();
        if ($existing) {
            return redirect()->back()
                ->with('error', 'Produk ini sudah memiliki BOM. Silakan edit BOM yang sudah ada.')
                ->withInput();
        }
        
        \DB::beginTransaction();
        try {
            // Get current month period and POR
        $currentPeriod = date('Y-m');
        $currentPOR = $this->getCurrentPOR();
        
        \Log::info('Current Period: ' . $currentPeriod);
        \Log::info('Current POR: ' . ($currentPOR ? 'Found' : 'Not found'));
        
        // Get BTKL data from employees (like in pemesanan)
        $btklEmployees = \App\Models\Employee::where('employee_type', 'BTKL')->get();
        $totalBTKLSalary = $btklEmployees->sum('base_salary');
        $totalWorkHours = $btklEmployees->sum('jam_kerja_per_bulan');

        \Log::info('BTKL Employees: ' . $btklEmployees->count());
        \Log::info('Total BTKL Salary: ' . $totalBTKLSalary);
        \Log::info('Total Work Hours: ' . $totalWorkHours);

        // BOP rate from current POR (0 if no POR for this month)
        $bopRatePerHour = $currentPOR ? $currentPOR->por_per_jam : 0;
        
        // Calculate BTKL rate
        $btklRatePerHour = $totalWorkHours > 0 ? $totalBTKLSalary / $totalWorkHours : 0;
        
        \Log::info('BTKL Rate per Hour: ' . $btklRatePerHour);
        \Log::info('BOP Rate per Hour: ' . $bopRatePerHour);

        $bom = BillOfMaterial::create([
            'product_id' => $request->product_id,
            'code' => BillOfMaterial::generateCode(),
            'name' => 'BOM ' . \App\Models\Product::find($request->product_id)->name,
            'description' => $request->description,
            'btkl_rate_per_hour' => $btklRatePerHour,
            'bop_rate_per_hour' => $bopRatePerHour,
            'selling_price' => $request->selling_price ?? 0,
            'is_active' => true,
        ]);

        // Sync harga jual ke produk jika diisi
        $newSellingPrice = (float) ($request->selling_price ?? 0);
        if ($newSellingPrice > 0) {
            \App\Models\Product::where('id', $request->product_id)
                ->update(['price' => $newSellingPrice]);
        }
        
        \Log::info('BOM created with ID: ' . $bom->id);

            // Save items with conversion data from unit conversions table
            foreach ($request->items as $item) {
                $rawMaterial = \App\Models\RawMaterial::find($item['raw_material_id']);
                
                // optional: convert inputs to numeric types to avoid casting errors
                $qty = (float) $item['quantity'];
                $cost = (float) $item['unit_cost'];

                // Get conversion data from unit conversions table (unused right now)
                $conversion = \App\Models\RawMaterialUnitConversion::where('raw_material_id', $item['raw_material_id'])
                    ->where('from_unit', $rawMaterial->unit)
                    ->first();
                
                BillOfMaterialItem::create([
                    'bill_of_material_id' => $bom->id,
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $qty,
                    'unit' => $item['unit'],
                    'unit_cost' => $cost,
                ]);
            }

            // Save auxiliaries (optional)
            if ($request->has('auxiliaries')) {
                foreach ($request->auxiliaries as $aux) {
                    $qty = (float) $aux['quantity'];
                    $cost = 0; // unit_cost tidak dipakai lagi (bahan penolong tidak ada nominal)
                    BillOfMaterialAuxiliary::create([
                        'bill_of_material_id' => $bom->id,
                        'auxiliary_material_id' => $aux['auxiliary_material_id'],
                        'quantity' => $qty,
                        'unit' => $aux['unit'] ?? null,
                        'unit_cost' => $cost,
                    ]);
                }
            }

            // Sync auxiliaries ke product_auxiliary_materials (dipakai job order)
            $product = \App\Models\Product::find($request->product_id);
            if ($product && $request->has('auxiliaries')) {
                $syncData = [];
                foreach ($request->auxiliaries as $aux) {
                    if (empty($aux['auxiliary_material_id'])) continue;
                    $auxQty  = (float)($aux['quantity'] ?? 0);
                    $auxUnit = $aux['unit'] ?? null;
                    if (empty($auxUnit)) {
                        $auxMaster = \App\Models\AuxiliaryMaterial::find($aux['auxiliary_material_id']);
                        $auxUnit = $auxMaster?->unit ?? '';
                    }
                    $syncData[$aux['auxiliary_material_id']] = [
                        'quantity_needed' => $auxQty,
                        'unit' => $auxUnit,
                    ];
                }
                $product->auxiliaryMaterials()->sync($syncData);
            }

            // Save process (single process based on production time)
            BillOfMaterialProcess::create([
                'bill_of_material_id' => $bom->id,
                'process_name' => 'Produksi ' . $bom->product->name,
                'duration_minutes' => $request->production_time_minutes,
                'sequence_order' => 1,
            ]);

            \DB::commit();
            return redirect()->route('bill-of-materials.index')
                ->with('success', 'Bill of Material berhasil dibuat.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(BillOfMaterial $billOfMaterial)
    {
        $billOfMaterial->load(['product.auxiliaryMaterials', 'items.rawMaterial', 'auxiliaries.auxiliaryMaterial', 'processes']);
        return view('bill-of-materials.show', compact('billOfMaterial'));
    }

    public function edit(BillOfMaterial $billOfMaterial)
    {
        $products = Product::all();
        $rawMaterials = RawMaterial::with('unitConversions')->get()->each(function($m) {
            $result = $this->calcDisplayPrice($m, 'raw');
            $m->display_unit = $result['unit'];
            $m->display_price = $result['price'];
        });
        $auxiliaryMaterials = AuxiliaryMaterial::with('unitConversions')->get()->each(function($m) {
            $result = $this->calcDisplayPrice($m, 'auxiliary');
            $m->display_unit = $result['unit'];
            $m->display_price = $result['price'];
        });
        
        // Get current month period and POR (same as create)
        $currentPeriod = date('Y-m');
        $currentPOR = $this->getCurrentPOR();
        
        // Get BTKL employees data
        $btklEmployees = \App\Models\Employee::where('employee_type', 'BTKL')
            ->whereRaw('UPPER(status) = ?', ['ACTIVE'])
            ->get();
        
        $btklRatePerHour = 0;
        foreach ($btklEmployees as $emp) {
            $jam = (float)($emp->jam_kerja_per_bulan ?? 0);
            $salary = (float)($emp->base_salary ?? 0);
            $rate = ($jam > 0 && $salary > 0) ? $salary / $jam : 0;
            $btklRatePerHour += $rate;
        }
        
        $billOfMaterial->load(['items.rawMaterial', 'auxiliaries.auxiliaryMaterial', 'processes']);
        
        return view('bill-of-materials.edit', compact('billOfMaterial', 'products', 'rawMaterials', 'auxiliaryMaterials', 'currentPOR', 'currentPeriod', 'btklEmployees', 'btklRatePerHour'));
    }

    public function update(Request $request, BillOfMaterial $billOfMaterial)
    {
        // Similar validation and logic as store method
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'description' => 'nullable|string',
            'production_time_minutes' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        // Update logic similar to store
        \DB::beginTransaction();
        try {
            // Get current month period and POR
            $currentPeriod = date('Y-m');
            $currentPOR = $this->getCurrentPOR();
            
            // Get BTKL data from employees
            $btklEmployees = \App\Models\Employee::where('employee_type', 'BTKL')->get();
            $totalBTKLSalary = $btklEmployees->sum('base_salary');
            $totalWorkHours = $btklEmployees->sum('jam_kerja_per_bulan');
            
            // Calculate BTKL rate
            $btklRatePerHour = $totalWorkHours > 0 ? $totalBTKLSalary / $totalWorkHours : 0;
            
            // BOP rate from current POR (0 if no POR for this month)
            $bopRatePerHour = $currentPOR ? $currentPOR->por_per_jam : 0;
            
            $newSellingPrice = (float) ($request->selling_price ?? 0);

            // Update BOM
            $billOfMaterial->update([
                'product_id' => $request->product_id,
                'name' => $request->name ?: ('BOM ' . \App\Models\Product::find($request->product_id)->name),
                'description' => $request->description,
                'btkl_rate_per_hour' => $btklRatePerHour,
                'bop_rate_per_hour' => $bopRatePerHour,
                'selling_price' => $newSellingPrice,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Sync harga jual ke produk dan job order details yang belum selesai
            if ($newSellingPrice > 0) {
                \App\Models\Product::where('id', $request->product_id)
                    ->update(['price' => $newSellingPrice]);

                \App\Models\JobOrderDetail::where('product_id', $request->product_id)
                    ->whereHas('jobOrder', fn($q) => $q->whereIn('status', ['draft', 'in_progress']))
                    ->update(['unit_price' => $newSellingPrice]);
            }

            // Delete and recreate items
            $billOfMaterial->items()->delete();
            foreach ($request->items as $item) {
                BillOfMaterialItem::create([
                    'bill_of_material_id' => $billOfMaterial->id,
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }

            // Delete and recreate auxiliaries
            $billOfMaterial->auxiliaries()->delete();
            $syncData = [];
            if ($request->has('auxiliaries')) {
                foreach ($request->auxiliaries as $aux) {
                    if (empty($aux['auxiliary_material_id'])) continue;
                    $auxQty  = (float)($aux['quantity'] ?? 0);
                    $auxCost = 0; // unit_cost tidak dipakai lagi (bahan penolong tidak ada nominal)
                    // Fallback unit dari master jika kosong
                    $auxUnit = $aux['unit'] ?? null;
                    if (empty($auxUnit)) {
                        $auxMaster = \App\Models\AuxiliaryMaterial::find($aux['auxiliary_material_id']);
                        $auxUnit = $auxMaster?->unit ?? '';
                    }
                    BillOfMaterialAuxiliary::create([
                        'bill_of_material_id' => $billOfMaterial->id,
                        'auxiliary_material_id' => $aux['auxiliary_material_id'],
                        'quantity' => $auxQty,
                        'unit' => $auxUnit,
                        'unit_cost' => $auxCost,
                    ]);
                    $syncData[$aux['auxiliary_material_id']] = [
                        'quantity_needed' => $auxQty,
                        'unit' => $auxUnit,
                    ];
                }
            }
            // Sync ke product_auxiliary_materials (dipakai job order)
            $billOfMaterial->product->auxiliaryMaterials()->sync($syncData);

            // Update process
            $billOfMaterial->processes()->delete();
            BillOfMaterialProcess::create([
                'bill_of_material_id' => $billOfMaterial->id,
                'process_name' => 'Produksi ' . $billOfMaterial->product->name,
                'duration_minutes' => $request->production_time_minutes,
                'sequence_order' => 1,
            ]);

            \DB::commit();
            return redirect()->route('bill-of-materials.index')
                ->with('success', 'Bill of Material berhasil diperbarui.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function toggleStatus(BillOfMaterial $billOfMaterial)
    {
        $billOfMaterial->update(['is_active' => !$billOfMaterial->is_active]);
        return redirect()->back()->with('success', 'Status BOM berhasil diubah.');
    }

    public function destroy(BillOfMaterial $billOfMaterial)
    {
        $billOfMaterial->items()->delete();
        $billOfMaterial->auxiliaries()->delete();
        $billOfMaterial->processes()->delete();
        $billOfMaterial->delete();

        return redirect()->route('bill-of-materials.index')
            ->with('success', 'Bill of Material berhasil dihapus.');
    }

    public function getByProduct($productId)
    {
        $bom = BillOfMaterial::where('product_id', $productId)
            ->where('is_active', true)
            ->with(['items.rawMaterial', 'auxiliaries.auxiliaryMaterial', 'processes'])
            ->first();
        
        return response()->json($bom);
    }
}


