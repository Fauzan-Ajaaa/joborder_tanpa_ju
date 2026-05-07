<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use App\Models\OverheadPor;
use App\Models\JobOrderLabor;
use App\Models\JobOrderMaterial;
use App\Models\AuxiliaryMaterial;
use App\Models\SalesTransaction;
use App\Models\SalesItem;
use App\Models\JobOrderDetail;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\ChartOfAccount;
use App\Models\BillOfMaterial;
use App\Models\BillOfMaterialAuxiliary;
use App\Services\JobOrderCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


class JobOrderController extends Controller
{
    public function __construct(private JobOrderCalculationService $calculationService) {}

    public function index()
    {
        $jobs = JobOrder::with('jobOrderDetails.product.auxiliaryMaterials', 'product', 'materials', 'salesTransactions')->latest()->paginate(15);
        $porBulanIni = \App\Models\OverheadPor::where('periode', now()->format('Y-m'))->orderByDesc('id')->first();

        return view('job_orders.index', compact('jobs', 'porBulanIni'));
    }

    public function create()
    {
        // Ambil produk yang sudah punya BOM
        $products = Product::whereHas('bomItems')->with(['bomItems.rawMaterial'])->get();
        
        // Tampilkan semua karyawan (tidak hanya BTKL) untuk penanggung jawab
        $employees = Employee::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('job_orders.create', compact('products', 'employees', 'customers'));
    }

    public function store(Request $request)
    {
        // Custom validation logic untuk multiple products
        $rules = [
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'order_date' => ['nullable', 'date'],
            'new_customer_name' => ['required', 'string', 'max:255'],
            'new_customer_phone' => ['nullable', 'string', 'max:20'],
            'new_customer_email' => ['nullable', 'email', 'max:255'],
            'new_customer_address' => ['nullable', 'string', 'max:500'],
        ];

        $data = $request->validate($rules);

        DB::transaction(function () use ($data, $request) {
            // Cari customer yang sudah ada berdasarkan nama + phone, atau buat baru
            $phone = $data['new_customer_phone'] ?? null;
            $name = $data['new_customer_name'];
            
            $newCustomer = Customer::where('name', $name)
                ->when($phone, fn($q) => $q->where('phone', $phone))
                ->first();
            
            if (!$newCustomer) {
                $newCustomer = Customer::createNew([
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $data['new_customer_email'] ?? null,
                    'address' => $data['new_customer_address'] ?? null,
                ]);
            }

            // Create job order dengan product_id dari first product
            $firstProductId = null;
            if (!empty($data['products'])) {
                $productKeys = array_keys($data['products']);
                $firstProductId = $data['products'][$productKeys[0]]['product_id'] ?? null;
            }
            
            $job = JobOrder::create([
                'kode_job' => 'JOB-' . now()->format('YmdHis'),
                'product_id' => $firstProductId ?: 1, // Gunakan first product atau default 1
                'quantity' => 0, // Tidak digunakan lagi
                'order_date' => $data['order_date'] ?? now(),
                'customer_id' => $newCustomer->id,
                'customer_name' => $newCustomer->name,
                'customer_phone' => $newCustomer->phone,
                'customer_address' => $newCustomer->address,
                'status' => 'draft',
            ]);

            // Hapus materials lama untuk menghindari duplikasi
            JobOrderMaterial::where('job_order_id', $job->id)->delete();

            // Process each product
            foreach ($data['products'] as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $quantity = $productData['quantity'];

                $bom = BillOfMaterial::with(['items.rawMaterial', 'auxiliaries.auxiliaryMaterial'])
                    ->where('product_id', $product->id)
                    ->first();

                // Cek duplikasi produk
                $existingDetail = JobOrderDetail::where('job_order_id', $job->id)
                    ->where('product_id', $product->id)
                    ->first();

                if ($existingDetail) {
                    $existingDetail->update([
                        'quantity' => $quantity,
                        'unit_price' => $bom?->getEffectiveSellingPrice() ?: ($product->price ?? 0),
                        'total_price' => ($bom?->getEffectiveSellingPrice() ?: ($product->price ?? 0)) * $quantity,
                    ]);
                } else {
                    $unitPrice = $bom?->getEffectiveSellingPrice() ?: ($product->price ?? 0);
                    JobOrderDetail::create([
                        'job_order_id' => $job->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice * $quantity,
                    ]);
                }

                // Process materials untuk setiap produk (dari BOM yang sudah di-load)
                if ($bom) {
                    foreach ($bom->items as $bomItem) {
                        $raw = $bomItem->rawMaterial;
                        if (! $raw) {
                            continue;
                        }

                        $qtyPerUnit = $bomItem->quantity;
                        $qtyTotal = $qtyPerUnit * $quantity;
                        $hargaPerUnit = $bomItem->unit_cost;
                        $unit = $bomItem->unit;

                        JobOrderMaterial::create([
                            'job_order_id' => $job->id,
                            'bahan_baku_id' => $raw->id,
                            'qty_per_unit' => $qtyPerUnit,
                            'qty_total' => $qtyTotal,
                            'unit' => $unit,
                            'harga_per_unit' => $hargaPerUnit,
                            'total_biaya' => $qtyTotal * $hargaPerUnit,
                        ]);
                    }
                }
            }

            // Delegasi kalkulasi durasi ke service
            $this->calculationService->calculateDuration($job);

            // Delegasi kalkulasi BBB ke service dan simpan hasilnya
            $totalBbb = $this->calculationService->calculateBBB($job);
            $job->total_bbb = $totalBbb;
            $job->saveQuietly();

            // Create JobOrderLabor hanya jika ada employee_id (untuk penanggung jawab)
            if (!empty($data['employee_id'])) {
                $employee = Employee::findOrFail($data['employee_id']);

                JobOrderLabor::create([
                    'job_order_id' => $job->id,
                    'employee_id' => $employee->id,
                    'mulai_job_at' => null,
                    'selesai_job_at' => null,
                    'durasi_menit' => null,
                    'durasi_jam' => null,
                    'tarif_per_jam' => 0,
                    'biaya_btkl' => 0,
                    'keterangan' => 'Penanggung jawab produksi',
                ]);
            }
        });

        return redirect()->route('job-orders.index')->with('success', 'Job Order berhasil dibuat');
    }

    public function show(JobOrder $job_order)
    {
        // Route parameter di-definisikan sebagai {job_order}, jadi kita pakai nama yang sama
        $job = $job_order->load([
            'jobOrderDetails.product.bomItems.rawMaterial',
            'jobOrderDetails.product.auxiliaryMaterials',
            'product',
            'materials.rawMaterial',
            'labors.employee',
            'por',
            'salesTransactions',
            'customer',
        ]);

        // Jika product tidak terload tapi product_id ada, load manual
        if (! $job->product && $job->product_id) {
            $job->setRelation('product', Product::find($job->product_id));
        }

        // Hitung POR per jam jika belum ada
        if (! $job->por) {
            $now = Carbon::now();
            $periode = $job->order_date
                ? $job->order_date->format('Y-m')
                : ($job->mulai_job_at ? $job->mulai_job_at->format('Y-m') : $now->format('Y-m'));

            // Cari POR berdasarkan periode saja agar sesuai dengan data yang sudah ada
            $por = OverheadPor::where('periode', $periode)->first();

            // Simpan relasi POR ke job untuk ditampilkan
            $job->setRelation('por', $por);
        }

        // Bangun mapping: bahan_baku_id => daftar produk yang menggunakan
        $materialProductMap = [];
        foreach ($job->jobOrderDetails as $detail) {
            $product = $detail->product;
            if (! $product) {
                continue;
            }

            $label = trim(($product->code ?? '') . ' - ' . ($product->name ?? '')); 

            foreach ($product->bomItems as $recipeMaterial) {
                $raw = $recipeMaterial->rawMaterial;
                if (! $raw) {
                    continue;
                }

                $rawId = $raw->id;
                if (! isset($materialProductMap[$rawId])) {
                    $materialProductMap[$rawId] = [];
                }

                if (! in_array($label, $materialProductMap[$rawId], true)) {
                    $materialProductMap[$rawId][] = $label;
                }
            }
        }

        // Tarif BTKL: ambil dari BOM produk (sumber kebenaran), fallback ke snapshot, lalu ke service
        $btklRatePerHour = (float) ($job->snapshot_btkl_rate ?? 0);
        if ($btklRatePerHour <= 0) {
            $productId = $job->jobOrderDetails->first()?->product_id ?? $job->product_id;
            $bom = \App\Models\BillOfMaterial::where('product_id', $productId)->first();
            $btklRatePerHour = $bom ? (float)$bom->btkl_rate_per_hour : 0;
        }
        if ($btklRatePerHour <= 0) {
            $btklRatePerHour = $this->calculationService->getBtklRatePerHour();
        }

        // POR bulan berjalan untuk tampilan BOP (selalu live)
        $porBulanIni = OverheadPor::where('periode', now()->format('Y-m'))
            ->orderByDesc('id')
            ->first();

        // Daftar karyawan BTKL tidak lagi ditampilkan di view
        return view('job_orders.show', compact('job', 'materialProductMap', 'btklRatePerHour', 'porBulanIni'));
    }

    public function edit(JobOrder $job_order)
    {
        // Hanya izinkan edit untuk job order yang statusnya draft
        if (in_array($job_order->status, ['in_progress', 'completed', 'cancelled'])) {
            return redirect()->route('job-orders.index')->with('error', 'Job order tidak dapat diedit karena sudah diproses');
        }

        $job = $job_order->load(['jobOrderDetails.product', 'labors.employee', 'customer']);
        $products = Product::with('materials.rawMaterial')->get();
        $employees = Employee::all();
        $customers = Customer::orderBy('name')->get();

        return view('job_orders.edit', compact('job', 'products', 'employees', 'customers'));
    }

    public function update(Request $request, JobOrder $job_order)
    {
        // Hanya izinkan update untuk job order yang statusnya draft
        if (in_array($job_order->status, ['in_progress', 'completed', 'cancelled'])) {
            return redirect()->route('job-orders.index')->with('error', 'Job order tidak dapat diedit karena sudah diproses');
        }

        // Custom validation logic untuk multiple products
        $rules = [
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'order_date' => ['nullable', 'date'],
            'new_customer_name' => ['required', 'string', 'max:255'],
            'new_customer_phone' => ['nullable', 'string', 'max:20'],
            'new_customer_email' => ['nullable', 'email', 'max:255'],
            'new_customer_address' => ['nullable', 'string', 'max:500'],
        ];
        
        $data = $request->validate($rules);

        DB::transaction(function () use ($data, $request, $job_order) {
            // Update customer info
            $job_order->update([
                'order_date' => $data['order_date'] ?? now(),
                'customer_name' => $data['new_customer_name'],
                'customer_phone' => $data['new_customer_phone'] ?? null,
                'customer_address' => $data['new_customer_address'] ?? null,
            ]);

            // Delete existing job order details and materials
            $job_order->jobOrderDetails()->delete();
            $job_order->materials()->delete();

            $totalBbb = 0;

            // Process each product
            foreach ($data['products'] as $productData) {
                $product = Product::with('materials.rawMaterial')->findOrFail($productData['product_id']);
                $quantity = $productData['quantity'];

                $bom = BillOfMaterial::with(['items.rawMaterial', 'auxiliaries.auxiliaryMaterial'])
                    ->where('product_id', $product->id)
                    ->first();

                // Create job order detail
                $unitPrice = $bom?->getEffectiveSellingPrice() ?: ($product->price ?? 0);
                JobOrderDetail::create([
                    'job_order_id' => $job_order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                ]);

                // Process materials untuk setiap produk
                foreach ($product->materials as $material) {
                    $raw = $material->rawMaterial;
                    if (! $raw) {
                        continue;
                    }

                    $qtyPerUnit = $material->quantity_needed;
                    $qtyTotal = $qtyPerUnit * $quantity;

                    // Sesuaikan harga per unit dengan satuan yang didefinisikan di resep produk
                    $rawUnit = $raw->unit;
                    $recipeUnit = $material->unit ?: $rawUnit;
                    $hargaPerUnit = $raw->price_per_unit;

                    if ($rawUnit && $recipeUnit && $rawUnit !== $recipeUnit) {
                        $raw->loadMissing('unitConversions');
                        $factor = (float) $raw->getMaterialConversionFactor($rawUnit, $recipeUnit); // 1 rawUnit = factor recipeUnit
                        if ($factor > 0) {
                            $hargaPerUnit = $hargaPerUnit / $factor;
                        }
                    }

                    $totalBiaya = $qtyTotal * $hargaPerUnit;

                    JobOrderMaterial::create([
                        'job_order_id' => $job_order->id,
                        'bahan_baku_id' => $raw->id,
                        'qty_per_unit' => $qtyPerUnit,
                        'qty_total' => $qtyTotal,
                        'unit' => $recipeUnit,
                        'harga_per_unit' => $hargaPerUnit,
                        'total_biaya' => $totalBiaya,
                    ]);

                    $totalBbb += $totalBiaya;
                }
            }

            $job_order->update([
                'total_bbb' => $totalBbb,
            ]);

            // Update labor
            $job_order->labors()->delete();
            $employee = Employee::findOrFail($data['employee_id']);

            JobOrderLabor::create([
                'job_order_id' => $job_order->id,
                'employee_id' => $employee->id,
                'mulai_job_at' => null,
                'selesai_job_at' => null,
                'durasi_menit' => null,
                'durasi_jam' => null,
                'tarif_per_jam' => $employee->hourly_rate,
                'biaya_btkl' => 0,
            ]);
        });

        return redirect()->route('job-orders.index')->with('success', 'Job Order berhasil diperbarui');
    }

    public function start(JobOrder $job_order)
    {
        // Izinkan start untuk job yang belum selesai / dibatalkan,
        // termasuk data lama yang belum punya status (null).
        if (in_array($job_order->status, ['completed', 'cancelled'], true)) {
            return redirect()->route('job-orders.show', $job_order);
        }

        // Cegah start ulang jika sudah in_progress
        if ($job_order->status === 'in_progress') {
            return redirect()->route('job-orders.show', $job_order)
                ->with('info', 'Job order sudah dalam proses.');
        }

        // VALIDASI STOK BAHAN BAKU SEBELUM MULAI JOB (bahan penolong tidak divalidasi)
        $insufficientMaterials = [];
        
        // Cek stok bahan baku
        $materials = $job_order->materials()->with('rawMaterial')->get();
        
        foreach ($materials as $material) {
            $raw = $material->rawMaterial;
            if (!$raw) continue;

            $qtyTotal = (float) ($material->qty_total ?? 0);
            if ($qtyTotal <= 0) continue;

            $rawUnit = $raw->unit;
            $recipeUnit = $material->unit ?: $rawUnit;

            // Konversi recipe unit → base unit
            $qtyTotalBase = $qtyTotal;
            if ($recipeUnit !== $rawUnit) {
                $sampleItem = \App\Models\PurchaseItem::where('raw_material_id', $raw->id)
                    ->whereNotNull('conversion_factor')->where('conversion_factor', '>', 0)->first();
                $convFactor = $sampleItem ? (float)$sampleItem->conversion_factor : 1.0;
                if ($convFactor > 0) {
                    $qtyTotalBase = $qtyTotal / $convFactor;
                }
            }

            // Cek apakah stok cukup (gunakan <= untuk mencegah stok 0 atau negatif)
            if ($raw->stock <= 0 || $raw->stock < $qtyTotalBase) {
                $insufficientMaterials[] = [
                    'name' => $raw->name,
                    'required' => $qtyTotalBase,
                    'available' => max(0, $raw->stock), // Pastikan tidak tampilkan nilai negatif
                    'unit' => $rawUnit,
                ];
            }
        }

        // Jika ada bahan baku yang kurang, tampilkan warning
        if (!empty($insufficientMaterials)) {
            $warningMessage = '<strong>Tidak dapat memulai job order!</strong><br>';
            $warningMessage .= 'Stok bahan baku tidak mencukupi:<br><ul>';
            foreach ($insufficientMaterials as $item) {
                $warningMessage .= sprintf(
                    '<li><strong>%s</strong>: Dibutuhkan %.2f %s, Tersedia %.2f %s</li>',
                    $item['name'],
                    $item['required'],
                    $item['unit'],
                    $item['available'],
                    $item['unit']
                );
            }
            $warningMessage .= '</ul>';
            
            return redirect()->route('job-orders.show', $job_order)
                ->with('error', $warningMessage)
                ->with('show_purchase_link', true);
        }

        $now = Carbon::now();

        DB::transaction(function () use ($job_order, $now) {
            // Update status dan start time
            $job_order->update([
                'status' => 'in_progress',
                'mulai_job_at' => $now,
            ]);

            // Update labor dengan durasi dari BOM (sudah tersimpan di job order)
            $labor = $job_order->labors()->first();
            if ($labor) {
                $labor->update([
                    'mulai_job_at' => $now,
                    'durasi_menit' => $job_order->durasi_menit, // ← Dari BOM
                    'durasi_jam' => $job_order->durasi_jam,     // ← Dari BOM
                    'tarif_per_jam' => 0, // Tidak digunakan, BTKL sudah di BOM
                    'biaya_btkl' => 0,    // Tidak digunakan, BTKL sudah di BOM
                ]);
            }

            // Kurangi stok bahan baku (BBB) sesuai JobOrderMaterial yang sudah dihitung di update()
            $materials = $job_order->materials()->with('rawMaterial')->get();
            foreach ($materials as $material) {
                $raw = $material->rawMaterial;
                if (!$raw) {
                    continue;
                }

                $qtyTotal = (float) ($material->qty_total ?? 0);
                if ($qtyTotal <= 0) {
                    continue;
                }

                $rawUnit = $raw->unit;
                $recipeUnit = $material->unit ?: $rawUnit;

                // Konversi recipe unit → base unit menggunakan purchase conv factor
                // SAMA PERSIS dengan logika bahan penolong
                $qtyTotalBase = $qtyTotal;
                if ($recipeUnit !== $rawUnit) {
                    $sampleItem = \App\Models\PurchaseItem::where('raw_material_id', $raw->id)
                        ->whereNotNull('conversion_factor')->where('conversion_factor', '>', 0)->first();
                    $convFactor = $sampleItem ? (float)$sampleItem->conversion_factor : 1.0;
                    if ($convFactor > 0) {
                        $qtyTotalBase = $qtyTotal / $convFactor;
                    }
                }

                // Kurangi stok fisik (dalam satuan dasar)
                \Log::info('Reducing raw material stock', [
                    'raw_material' => $raw->name,
                    'qty_total_from_bom' => $qtyTotal,
                    'qty_to_reduce' => $qtyTotalBase,
                    'current_stock' => $raw->stock,
                    'recipe_unit' => $recipeUnit,
                    'raw_unit' => $rawUnit,
                ]);
                $raw->reduceStock($qtyTotalBase);
            }

            // Hitung ulang durasi dari BOM saat start (jangan reset ke 0)
            $this->calculationService->calculateDuration($job_order);

            // Kurangi stok bahan penolong (auxiliary materials) berdasarkan BOM produk
            $job_order->loadMissing('jobOrderDetails.product');

            foreach ($job_order->jobOrderDetails as $detail) {
                $product = $detail->product;
                if (!$product) continue;

                // Ambil dari BOM (sumber kebenaran), bukan product pivot
                $bom = \App\Models\BillOfMaterial::with('auxiliaries.auxiliaryMaterial')
                    ->where('product_id', $product->id)->first();
                if (!$bom) continue;

                foreach ($bom->auxiliaries as $bomAux) {
                    $aux = $bomAux->auxiliaryMaterial;
                    if (!$aux) continue;

                    $qtyPerUnit = (float)($bomAux->quantity ?? 0);
                    if ($qtyPerUnit <= 0) continue;

                    $qtyTotalRecipe = $qtyPerUnit * (float)$detail->quantity;
                    $recipeUnit = $bomAux->unit ?: $aux->unit;
                    $baseUnit   = $aux->unit;

                    // Konversi recipe unit → base unit (SAMA dengan bahan baku, pakai purchase conv factor)
                    $qtyTotalBase = $qtyTotalRecipe;
                    if ($recipeUnit !== $baseUnit) {
                        $sampleItem = \App\Models\PurchaseItem::where('auxiliary_material_id', $aux->id)
                            ->whereNotNull('conversion_factor')->where('conversion_factor', '>', 0)->first();
                        $convFactor = $sampleItem ? (float)$sampleItem->conversion_factor : 1.0;
                        if ($convFactor > 0) {
                            $qtyTotalBase = $qtyTotalRecipe / $convFactor;
                        }
                    }

                    if ($qtyTotalBase > 0) {
                        \Log::info('Reducing auxiliary material stock', [
                            'auxiliary_material' => $aux->name,
                            'qty_total_recipe' => $qtyTotalRecipe,
                            'recipe_unit' => $recipeUnit,
                            'qty_to_reduce_base' => $qtyTotalBase,
                            'base_unit' => $baseUnit,
                            'current_stock' => $aux->stock,
                        ]);
                        $aux->decrement('stock', $qtyTotalBase);
                    }
                }
            }

            // Create journal entry for job start (WIP)
            $job_order->createStartJournalEntry();
        });

        return redirect()->route('job-orders.show', $job_order);
    }

    public function finish(JobOrder $job_order)
    {
        // Jangan hitung ulang jika sudah selesai sebelumnya
        if ($job_order->selesai_job_at) {
            return redirect()->route('job-orders.show', $job_order);
        }

        $now = Carbon::now();

        DB::transaction(function () use ($job_order, $now) {
            $labor = $job_order->labors()->with('employee')->first();

            // Delegasi kalkulasi durasi ke service (refresh dari BOM)
            $this->calculationService->calculateDuration($job_order);
            $job_order->refresh();

            $durasiMenit = $job_order->durasi_menit;
            $durasiJam   = $job_order->durasi_jam;

            if ($labor) {
                $labor->update([
                    'selesai_job_at' => $now,
                    'durasi_menit'   => $durasiMenit,
                    'durasi_jam'     => $durasiJam,
                    'tarif_per_jam'  => 0,
                    'biaya_btkl'     => 0,
                ]);
            }

            // Delegasi kalkulasi BTKL, BOP, HPP ke service
            $totalBtkl = $this->calculationService->calculateBTKL($job_order);
            $totalBop  = $this->calculationService->calculateBOP($job_order);
            $totalHpp  = $this->calculationService->calculateHPP($job_order);

            // Update status dan simpan hasil kalkulasi
            $job_order->update([
                'status'         => 'completed',
                'selesai_job_at' => $now,
                'total_btkl'     => $totalBtkl,
                'total_bop'      => $totalBop,
                'total_hpp'      => $totalHpp,
            ]);

            // Snapshot semua nilai HPP saat selesai
            $this->calculationService->snapshotOnFinish($job_order);

            // Tambah stok produk jadi sesuai quantity per job order detail
            $job_order->loadMissing('jobOrderDetails.product');
            foreach ($job_order->jobOrderDetails as $detail) {
                $product = $detail->product()->lockForUpdate()->first();
                if ($product && $detail->quantity > 0) {
                    $product->stock = (float)($product->stock ?? 0) + (float)$detail->quantity;
                    $product->save();
                }
            }
            // Fallback untuk job order lama tanpa details
            if ($job_order->jobOrderDetails->isEmpty() && $job_order->product && $job_order->quantity) {
                $product = $job_order->product()->lockForUpdate()->first();
                if ($product) {
                    $product->stock = (float)($product->stock ?? 0) + (float)$job_order->quantity;
                    $product->save();
                }
            }

            // Create journal entry for job completion
            $job_order->createCompletionJournalEntry();
        });

        return redirect()->route('job-orders.show', $job_order);
    }

    public function destroy(JobOrder $job_order)
    {
        // Allow deletion of all job orders except those that are in progress
        if (in_array($job_order->status, ['in_progress'], true)) {
            return redirect()->route('job-orders.index')->with('error', 'Job order tidak dapat dihapus karena sedang dalam proses');
        }

        try {
            DB::transaction(function () use ($job_order) {
                // If job order is completed, reduce product stock
                if ($job_order->status === 'completed') {
                    $job_order->loadMissing('jobOrderDetails.product');
                    foreach ($job_order->jobOrderDetails as $detail) {
                        $product = $detail->product()->lockForUpdate()->first();
                        if ($product && $detail->quantity > 0) {
                            $product->stock = max(0, (float)($product->stock ?? 0) - (float)$detail->quantity);
                            $product->save();
                        }
                    }
                    // Fallback untuk job order lama
                    if ($job_order->jobOrderDetails->isEmpty() && $job_order->product && $job_order->quantity) {
                        $product = $job_order->product()->lockForUpdate()->first();
                        if ($product) {
                            $product->stock = max(0, (float)($product->stock ?? 0) - (float)$job_order->quantity);
                            $product->save();
                        }
                    }
                }

                // Delete related records
                $job_order->jobOrderDetails()->delete();
                $job_order->materials()->delete();
                $job_order->labors()->delete();
                
                // Delete the job order itself
                $job_order->delete();
            });

            return redirect()->route('job-orders.index')->with('success', 'Job Order berhasil dihapus');
        } catch (\Exception $e) {
            \Log::error('Error deleting job order: ' . $e->getMessage());
            return redirect()->route('job-orders.index')->with('error', 'Gagal menghapus job order: ' . $e->getMessage());
        }
    }

    public function regenerateJournal(JobOrder $job_order)
    {
        if ($job_order->status !== 'completed') {
            return redirect()->route('job-orders.show', $job_order)
                ->with('error', 'Hanya job order yang sudah selesai yang bisa di-regenerate jurnalnya.');
        }

        try {
            DB::transaction(function () use ($job_order) {
                // Hapus jurnal lama yang terkait job order ini
                $sourceTypes = ['job_order_labor', 'job_order_overhead', 'production_completion', 'raw_material_usage', 'auxiliary_material_usage'];
                $oldJournals = \App\Models\JournalEntry::whereIn('source_type', $sourceTypes)
                    ->where('source_id', $job_order->id)
                    ->get();

                foreach ($oldJournals as $journal) {
                    $journal->items()->delete();
                    $journal->delete();
                }

                // Buat ulang jurnal dengan kalkulasi yang benar
                $job_order->createCompletionJournalEntry();
            });

            return redirect()->route('job-orders.show', $job_order)
                ->with('success', 'Jurnal berhasil di-regenerate dengan nilai yang benar.');
        } catch (\Exception $e) {
            \Log::error('Error regenerating journal: ' . $e->getMessage());
            return redirect()->route('job-orders.show', $job_order)
                ->with('error', 'Gagal regenerate jurnal: ' . $e->getMessage());
        }
    }

}
