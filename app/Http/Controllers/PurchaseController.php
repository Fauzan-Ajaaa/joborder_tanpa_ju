<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\RawMaterial;
use App\Models\AuxiliaryMaterial;
use App\Models\Unit;
use App\Models\Employee;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request type: raw_material | auxiliary_material
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'raw_material');
        if (! in_array($type, ['raw_material', 'auxiliary_material'], true)) {
            $type = 'raw_material';
        }

        $query = Purchase::with('supplier', 'items')->where('purchase_type', $type)->latest();
        $purchases = $query->paginate(15)->withQueryString();

        $stats = [
            'total'    => Purchase::where('purchase_type', $type)->count(),
            'draft'    => Purchase::where('purchase_type', $type)->where('status', 'draft')->count(),
            'approved' => Purchase::where('purchase_type', $type)->where('status', 'approved')->count(),
            'received' => Purchase::where('purchase_type', $type)->where('status', 'received')->count(),
        ];

        return view('purchases.index', compact('purchases', 'type', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     * @param Request $request type: raw_material | auxiliary_material (optional, for pre-select)
     */
    public function create(Request $request)
    {
        $suppliers = Supplier::where('status', 'active')->get();
        $unitMap   = Unit::pluck('name', 'code'); // [code => name]

        // ── Raw Materials ─────────────────────────────────────────────
        // Load unitConversions agar satuan alternatif tersedia di JS
        $rawMaterials = RawMaterial::with('unitConversions')
            ->get()
            ->map(function ($m) use ($unitMap) {
                // Label satuan dasar dari master Unit
                $baseUnitLabel = $unitMap[$m->unit] ?? $m->unit;

                // Satuan dasar selalu jadi pilihan pertama
                $units = [[
                    'unit'           => $m->unit,
                    'unit_label'     => $baseUnitLabel,
                    'price_per_unit' => (float) $m->price_per_unit,
                    'is_base_unit'   => true,
                    'factor'         => 1.0,
                ]];

                // Tambahkan satuan alternatif dari RawMaterialUnitConversion
                // Skema: from_unit = satuan dasar, to_unit = satuan alternatif, factor = jumlah to_unit per 1 from_unit
                // Contoh: 1 Ekor = 5 Potong  →  from_unit=Ekor, to_unit=Potong, factor=5
                //         Harga Potong = harga Ekor / factor = 60.000 / 5 = 12.000
                foreach ($m->unitConversions as $conv) {
                    if ($conv->from_unit === $m->unit && $conv->to_unit !== $m->unit && $conv->factor > 0) {
                        $altPrice  = round((float) $m->price_per_unit / (float) $conv->factor, 2);
                        $altLabel  = $unitMap[$conv->to_unit] ?? $conv->to_unit;
                        $units[]   = [
                            'unit'           => $conv->to_unit,
                            'unit_label'     => $altLabel,
                            'price_per_unit' => $altPrice,
                            'is_base_unit'   => false,
                            'factor'         => (float) $conv->factor,
                        ];
                    }
                }

                return [
                    'id'              => $m->id,
                    'name'            => $m->name,
                    'supplier_id'     => $m->supplier_id,
                    'unit'            => $m->unit,
                    'unit_label'      => $baseUnitLabel,
                    'price_per_unit'  => (float) $m->price_per_unit,
                    'units'           => $units,   // ← array semua satuan + harga per satuan
                ];
            });

        // ── Auxiliary Materials ───────────────────────────────────────
        // Load unitConversions agar satuan alternatif tersedia di JS
        $auxiliaryMaterials = AuxiliaryMaterial::with('unitConversions')
            ->get()
            ->map(function ($m) use ($unitMap) {
                $baseUnitLabel = $unitMap[$m->unit] ?? $m->unit;

                // Satuan dasar selalu jadi pilihan pertama
                $units = [[
                    'unit'           => $m->unit,
                    'unit_label'     => $baseUnitLabel,
                    'price_per_unit' => (float) $m->price_per_unit,
                    'is_base_unit'   => true,
                    'factor'         => 1.0,
                ]];

                // Tambahkan satuan alternatif dari unitConversions
                // from_unit = satuan dasar, to_unit = satuan alternatif, factor = jumlah to_unit per 1 from_unit
                // Contoh: 1 Kg = 12 Butir → from_unit=Kg, to_unit=Butir, factor=12
                //         Harga Butir = harga Kg / factor = 16.000 / 12 ≈ 1.333
                foreach ($m->unitConversions as $conv) {
                    if ($conv->from_unit === $m->unit && $conv->to_unit !== $m->unit && $conv->factor > 0) {
                        $altPrice = round((float) $m->price_per_unit / (float) $conv->factor, 2);
                        $altLabel = $unitMap[$conv->to_unit] ?? $conv->to_unit;
                        $units[]  = [
                            'unit'           => $conv->to_unit,
                            'unit_label'     => $altLabel,
                            'price_per_unit' => $altPrice,
                            'is_base_unit'   => false,
                            'factor'         => (float) $conv->factor,
                        ];
                    }
                }

                return [
                    'id'             => $m->id,
                    'name'           => $m->name,
                    'supplier_id'    => $m->supplier_id,
                    'unit'           => $m->unit,
                    'unit_label'     => $baseUnitLabel,
                    'price_per_unit' => (float) $m->price_per_unit,
                    'units'          => $units,   // ← array semua satuan + harga per satuan
                ];
            });

        $defaultType = $request->get('type', 'raw_material');
        if (! in_array($defaultType, ['raw_material', 'auxiliary_material'], true)) {
            $defaultType = 'raw_material';
        }

        return view('purchases.create', compact('suppliers', 'rawMaterials', 'auxiliaryMaterials', 'defaultType'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'purchase_date'                             => 'required|date',
                'supplier_id'                               => 'required',
                'new_supplier_name'                         => 'nullable|required_if:supplier_id,new|string|max:255',
                'new_supplier_phone'                        => 'nullable|required_if:supplier_id,new|string|max:50',
                'purchase_type'                             => 'required|in:raw_material,auxiliary_material',
                'fob_type'                                  => 'required|in:shipping_point,destination',
                'fob_cost'                                  => 'nullable|numeric|min:0',
                'ppn_rate'                                  => 'required|numeric|min:0|max:100',
                'discount_rate'                             => 'nullable|numeric|min:0|max:100',
                'payment_method'                            => 'required|in:cash,credit',
                'down_payment'                              => 'nullable|numeric|min:0',
                'due_date'                                  => 'nullable|date|after:purchase_date',
                'notes'                                     => 'nullable|string',
                'items'                                     => 'required|array|min:1',
                'items.*.raw_material_id'                   => 'required_if:purchase_type,raw_material|nullable|exists:raw_materials,id',
                'items.*.auxiliary_material_id'             => 'required_if:purchase_type,auxiliary_material|nullable|exists:auxiliary_materials,id',
                'items.*.quantity'                          => 'required|numeric|min:0.01',
                'items.*.unit_price'                        => 'required|numeric|min:0',
                'items.*.unit'                              => 'nullable|string|max:20',
                'items.*.conversion_factor'                 => 'nullable|numeric|min:0.000001',
                'items.*.tax_type'                          => 'required|in:before_tax,after_tax',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('debug_error', 'Validation failed: ' . json_encode($e->errors()));
        }

        // Handle new supplier creation
        if ($validated['supplier_id'] === 'new') {
            $supplier   = Supplier::create([
                'name'   => $validated['new_supplier_name'],
                'phone'  => $validated['new_supplier_phone'],
                'status' => 'active',
            ]);
            $supplierId = $supplier->id;
        } else {
            $supplierId = $validated['supplier_id'];
        }

        $type    = $validated['purchase_type'];
        $fobCost = $validated['fob_type'] === 'destination' ? 0 : ($validated['fob_cost'] ?? 0);

        $purchase = Purchase::create([
            'purchase_date'   => $validated['purchase_date'],
            'supplier_id'     => $supplierId,
            'purchase_type'   => $type,
            'fob_type'        => $validated['fob_type'],
            'fob_cost'        => $fobCost,
            'ppn_rate'        => $validated['ppn_rate'],
            'discount_rate'   => $validated['discount_rate'] ?? 0,
            'payment_method'  => $validated['payment_method'],
            'down_payment'    => $validated['down_payment'] ?? 0,
            'due_date'        => $validated['payment_method'] === 'credit' ? $validated['due_date'] : null,
            'payment_status'  => $validated['payment_method'] === 'cash' ? 'paid' : 'pending',
            'paid_amount'     => $validated['down_payment'] ?? 0,
            'notes'           => $validated['notes'] ?? null,
            'status'          => 'draft',
        ]);

        foreach ($validated['items'] as $item) {
            // Get the display quantity (what user actually input)
            $displayQuantity = $item['quantity'];
            $selectedUnit = $item['unit'] ?? null;
            $customConversionFactor = $item['conversion_factor'] ?? null;
            
            // Calculate base unit quantity for inventory
            $baseQuantity = $displayQuantity;
            $baseUnitPrice = $item['unit_price'];
            
            $finalConversionFactor = $customConversionFactor;
            // If using conversion unit, convert to base unit
            if ($selectedUnit) {
                $materialId = $type === 'raw_material' ? $item['raw_material_id'] : $item['auxiliary_material_id'];
                $materialClass = $type === 'raw_material' ? \App\Models\RawMaterial::class : \App\Models\AuxiliaryMaterial::class;
                $material = $materialClass::with('unitConversions')->find($materialId);
                
                if ($material && $selectedUnit !== $material->unit) {
                    $factor = null;
                    
                    // 1. Is there a custom conversion factor from user input?
                    if ($customConversionFactor && $customConversionFactor > 0) {
                        $factor = (float)$customConversionFactor;
                    } else {
                        // 2. Is there a global automated conversion?
                        $globalFactor = $materialClass::getConversionFactor($material->unit, $selectedUnit);
                        if ($globalFactor !== null) {
                            $factor = $globalFactor;
                            $finalConversionFactor = $factor; // Save automated conversion factor to DB
                        } else {
                            // 3. Check unit conversion mapping in DB (custom units)
                            $conversion = $material->unitConversions()
                                ->where('from_unit', $material->unit)
                                ->where('to_unit', $selectedUnit)
                                ->first();
                                
                            if ($conversion && $conversion->factor > 0) {
                                $factor = (float)$conversion->factor;
                                $finalConversionFactor = $factor; // Save mapping conversion factor to DB
                            }
                        }
                    }

                    // STANDARD LOGIC:
                    // Example: 1 EKOR (Base) = 8 POTONG (Selected). Factor = 8.
                    // If user bought 24 POTONG at Rp 10.000 / POTONG
                    // Base needs to be 24 / 8 = 3 EKOR at Rp 80.000 / EKOR
                    if ($factor && $factor > 0) {
                        $baseQuantity = $displayQuantity / $factor;
                        $baseUnitPrice = $item['unit_price'] * $factor;
                        // Persist the conversion factor so stock-card / index displayFactor is always correct
                        $material->unitConversions()->updateOrCreate(
                            ['from_unit' => $material->unit, 'to_unit' => $selectedUnit],
                            ['factor' => $factor]
                        );
                    }
                } else if ($customConversionFactor && $customConversionFactor > 0 && !empty($item['conversion_target_unit'])) {
                    // Update or create custom mapping (e.g., 1 BASE = N alt)
                    $material->unitConversions()->updateOrCreate(
                        ['from_unit' => $material->unit, 'to_unit' => $item['conversion_target_unit']],
                        ['factor' => $customConversionFactor]
                    );
                }
            }
            
            $purchase->items()->create([
                'raw_material_id'       => $type === 'raw_material'       ? $item['raw_material_id']       : null,
                'auxiliary_material_id' => $type === 'auxiliary_material' ? $item['auxiliary_material_id'] : null,
                'quantity'              => $baseQuantity, // Store in base unit for inventory
                'unit'                  => $selectedUnit, // Store the selected unit for display
                'conversion_factor'     => $finalConversionFactor, // Store custom conversion factor
                'unit_price'            => $baseUnitPrice, // Store base unit price
                'subtotal'              => $baseQuantity * $baseUnitPrice, // Calculate based on base quantity and base unit price
                'tax_type'              => $item['tax_type'] ?? 'after_tax',
            ]);
        }

        $purchase->calculateTotals();

        $journalService = new JournalService();
        $journalService->createJournalFromPurchase($purchase);

        // Update stock immediately when purchase is created
        foreach ($purchase->items as $item) {
            if ($item->raw_material_id) {
                $rawMaterial = $item->rawMaterial;
                if (! $rawMaterial) continue;

                $fromUnit       = $item->unit ?: $rawMaterial->unit;
                $toUnit         = $rawMaterial->unit;
                
                // Get conversion factor
                $factor = 1;
                if ($fromUnit !== $toUnit) {
                    if ($item->conversion_factor && $item->conversion_factor > 0) {
                        $factor = $item->conversion_factor;
                    } else {
                        $factor = \App\Models\RawMaterial::getConversionFactor($fromUnit, $toUnit) ?? 1;
                    }
                }
                
                $qtyInBaseUnit  = max(0, (float) ($item->quantity ?? 0));
                if ($qtyInBaseUnit <= 0) continue;

                $oldQty   = (float) ($rawMaterial->stock ?? 0);
                $oldPrice = (float) ($rawMaterial->price_per_unit ?? 0);
                $oldTotal = $oldQty * $oldPrice;

                $newPricePerBaseUnit = (float) $item->unit_price;
                $newTotal    = $qtyInBaseUnit * $newPricePerBaseUnit;
                $combinedQty = $oldQty + $qtyInBaseUnit;
                $newAvgPrice = $combinedQty > 0 ? ($oldTotal + $newTotal) / $combinedQty : $oldPrice;

                $rawMaterial->stock          = $combinedQty;
                $rawMaterial->price_per_unit = $newAvgPrice;
                $rawMaterial->save();

            } elseif ($item->auxiliary_material_id) {
                $aux = $item->auxiliaryMaterial;
                if (! $aux) continue;

                $fromUnit       = $item->unit ?: $aux->unit;
                $toUnit         = $aux->unit;
                
                // Get conversion factor
                $factor = 1;
                if ($fromUnit !== $toUnit) {
                    if ($item->conversion_factor && $item->conversion_factor > 0) {
                        $factor = $item->conversion_factor;
                    } else {
                        $factor = \App\Models\AuxiliaryMaterial::getConversionFactor($fromUnit, $toUnit) ?? 1;
                    }
                }
                
                $qtyInBaseUnit  = max(0, (float) ($item->quantity ?? 0));
                if ($qtyInBaseUnit <= 0) continue;

                $oldQty   = (float) ($aux->stock ?? 0);
                $oldPrice = (float) ($aux->price_per_unit ?? 0);
                $oldTotal = $oldQty * $oldPrice;

                $newPricePerBaseUnit = (float) $item->unit_price;
                $newTotal    = $qtyInBaseUnit * $newPricePerBaseUnit;
                $combinedQty = $oldQty + $qtyInBaseUnit;
                $newAvgPrice = $combinedQty > 0 ? ($oldTotal + $newTotal) / $combinedQty : $oldPrice;

                $aux->stock          = $combinedQty;
                $aux->price_per_unit = $newAvgPrice;
                $aux->save();
            }
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Purchase Order berhasil dibuat. Stok telah diperbarui.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.rawMaterial.unitConversions', 'items.auxiliaryMaterial.unitConversions', 'returns', 'receivedBy');

        $employees = Employee::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('purchases.show', compact('purchase', 'employees'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Hanya purchase order dengan status draft yang bisa diedit');
        }
        
        $purchase->load('items.rawMaterial', 'items.auxiliaryMaterial');

        $suppliers          = Supplier::where('status', 'active')->get();
        $unitMap   = Unit::pluck('name', 'code'); // [code => name]

        // ── Raw Materials ─────────────────────────────────────────────
        $rawMaterials = RawMaterial::with('unitConversions')
            ->get()
            ->map(function ($m) use ($unitMap) {
                $baseUnitLabel = $unitMap[$m->unit] ?? $m->unit;

                $units = [[
                    'unit'           => $m->unit,
                    'unit_label'     => $baseUnitLabel,
                    'price_per_unit' => (float) $m->price_per_unit,
                    'is_base_unit'   => true,
                    'factor'         => 1.0,
                ]];

                foreach ($m->unitConversions as $conv) {
                    if ($conv->from_unit === $m->unit && $conv->to_unit !== $m->unit && $conv->factor > 0) {
                        $altPrice  = round((float) $m->price_per_unit / (float) $conv->factor, 2);
                        $altLabel  = $unitMap[$conv->to_unit] ?? $conv->to_unit;
                        $units[]   = [
                            'unit'           => $conv->to_unit,
                            'unit_label'     => $altLabel,
                            'price_per_unit' => $altPrice,
                            'is_base_unit'   => false,
                            'factor'         => (float) $conv->factor,
                        ];
                    }
                }

                return [
                    'id'              => $m->id,
                    'name'            => $m->name,
                    'supplier_id'     => $m->supplier_id,
                    'unit'            => $m->unit,
                    'unit_label'      => $baseUnitLabel,
                    'price_per_unit'  => (float) $m->price_per_unit,
                    'units'           => $units,
                ];
            });

        // ── Auxiliary Materials ───────────────────────────────────────
        $auxiliaryMaterials = AuxiliaryMaterial::with('unitConversions')
            ->get()
            ->map(function ($m) use ($unitMap) {
                $baseUnitLabel = $unitMap[$m->unit] ?? $m->unit;

                $units = [[
                    'unit'           => $m->unit,
                    'unit_label'     => $baseUnitLabel,
                    'price_per_unit' => (float) $m->price_per_unit,
                    'is_base_unit'   => true,
                    'factor'         => 1.0,
                ]];

                foreach ($m->unitConversions as $conv) {
                    if ($conv->from_unit === $m->unit && $conv->to_unit !== $m->unit && $conv->factor > 0) {
                        $altPrice = round((float) $m->price_per_unit / (float) $conv->factor, 2);
                        $altLabel = $unitMap[$conv->to_unit] ?? $conv->to_unit;
                        $units[]  = [
                            'unit'           => $conv->to_unit,
                            'unit_label'     => $altLabel,
                            'price_per_unit' => $altPrice,
                            'is_base_unit'   => false,
                            'factor'         => (float) $conv->factor,
                        ];
                    }
                }

                return [
                    'id'             => $m->id,
                    'name'           => $m->name,
                    'supplier_id'    => $m->supplier_id,
                    'unit'           => $m->unit,
                    'unit_label'     => $baseUnitLabel,
                    'price_per_unit' => (float) $m->price_per_unit,
                    'units'          => $units,
                ];
            });

        $unitOptions        = RawMaterial::UNIT_OPTIONS;

        return view('purchases.edit', compact('purchase', 'suppliers', 'rawMaterials', 'auxiliaryMaterials', 'unitOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Hanya purchase order dengan status draft yang bisa diupdate');
        }

        try {
            $validated = $request->validate([
                'purchase_date'                             => 'required|date',
                'supplier_id'                               => 'required',
                'new_supplier_name'                         => 'nullable|required_if:supplier_id,new|string|max:255',
                'new_supplier_phone'                        => 'nullable|required_if:supplier_id,new|string|max:50',
                'purchase_type'                             => 'required|in:raw_material,auxiliary_material',
                'fob_type'                                  => 'required|in:shipping_point,destination',
                'fob_cost'                                  => 'nullable|numeric|min:0',
                'ppn_rate'                                  => 'required|numeric|min:0|max:100',
                'discount_rate'                             => 'nullable|numeric|min:0|max:100',
                'notes'                                     => 'nullable|string',
                'items'                                     => 'required|array|min:1',
                'items.*.raw_material_id'                   => 'required_if:purchase_type,raw_material|nullable|exists:raw_materials,id',
                'items.*.auxiliary_material_id'             => 'required_if:purchase_type,auxiliary_material|nullable|exists:auxiliary_materials,id',
                'items.*.quantity'                          => 'required|numeric|min:0.01',
                'items.*.unit_price'                        => 'required|numeric|min:0',
                'items.*.unit'                              => 'nullable|string|max:20',
                'items.*.conversion_factor'                 => 'nullable|numeric|min:0.000001',
                'items.*.tax_type'                          => 'required|in:before_tax,after_tax',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('debug_error', 'Validation failed: ' . json_encode($e->errors()));
        }

        if ($validated['supplier_id'] === 'new') {
            $supplier   = Supplier::create([
                'name'   => $validated['new_supplier_name'],
                'phone'  => $validated['new_supplier_phone'],
                'status' => 'active',
            ]);
            $supplierId = $supplier->id;
        } else {
            $supplierId = $validated['supplier_id'];
        }

        $type    = $validated['purchase_type'];
        $fobCost = $validated['fob_type'] === 'destination' ? 0 : ($validated['fob_cost'] ?? 0);

        $purchase->update([
            'purchase_date' => $validated['purchase_date'],
            'supplier_id'   => $supplierId,
            'purchase_type' => $type,
            'fob_type'      => $validated['fob_type'],
            'fob_cost'      => $fobCost,
            'ppn_rate'      => $validated['ppn_rate'],
            'discount_rate' => $validated['discount_rate'] ?? 0,
            'notes'         => $validated['notes'] ?? null,
        ]);

        $purchase->items()->delete();

        foreach ($validated['items'] as $item) {
            // Get the display quantity (what user actually input)
            $displayQuantity = $item['quantity'];
            $selectedUnit = $item['unit'] ?? null;
            $customConversionFactor = $item['conversion_factor'] ?? null;
            
            // Calculate base unit quantity for inventory
            $baseQuantity = $displayQuantity;
            $baseUnitPrice = $item['unit_price'];
            
            $finalConversionFactor = $customConversionFactor;
            // If using conversion unit, convert to base unit
            if ($selectedUnit) {
                $materialId = $type === 'raw_material' ? $item['raw_material_id'] : $item['auxiliary_material_id'];
                $materialClass = $type === 'raw_material' ? \App\Models\RawMaterial::class : \App\Models\AuxiliaryMaterial::class;
                $material = $materialClass::with('unitConversions')->find($materialId);
                
                if ($material && $selectedUnit !== $material->unit) {
                    $factor = null;
                    
                    // 1. Is there a custom conversion factor from user input?
                    if ($customConversionFactor && $customConversionFactor > 0) {
                        $factor = (float)$customConversionFactor;
                    } else {
                        // 2. Is there a global automated conversion?
                        $globalFactor = $materialClass::getConversionFactor($material->unit, $selectedUnit);
                        if ($globalFactor !== null) {
                            $factor = $globalFactor;
                            $finalConversionFactor = $factor; // Save automated conversion factor to DB
                        } else {
                            // 3. Check unit conversion mapping in DB (custom units)
                            $conversion = $material->unitConversions()
                                ->where('from_unit', $material->unit)
                                ->where('to_unit', $selectedUnit)
                                ->first();
                                
                            if ($conversion && $conversion->factor > 0) {
                                $factor = (float)$conversion->factor;
                                $finalConversionFactor = $factor; // Save mapping conversion factor to DB
                            }
                        }
                    }

                    // STANDARD LOGIC:
                    // Example: 1 EKOR (Base) = 8 POTONG (Selected). Factor = 8.
                    // If user bought 24 POTONG at Rp 10.000 / POTONG
                    // Base needs to be 24 / 8 = 3 EKOR at Rp 80.000 / EKOR
                    if ($factor && $factor > 0) {
                        $baseQuantity = $displayQuantity / $factor;
                        $baseUnitPrice = $item['unit_price'] * $factor;
                        // Persist the conversion factor so stock-card / index displayFactor is always correct
                        $material->unitConversions()->updateOrCreate(
                            ['from_unit' => $material->unit, 'to_unit' => $selectedUnit],
                            ['factor' => $factor]
                        );
                    }
                } else if ($customConversionFactor && $customConversionFactor > 0 && !empty($item['conversion_target_unit'])) {
                    // Update or create custom mapping (e.g., 1 BASE = N alt)
                    $material->unitConversions()->updateOrCreate(
                        ['from_unit' => $material->unit, 'to_unit' => $item['conversion_target_unit']],
                        ['factor' => $customConversionFactor]
                    );
                }
            }
            
            $purchase->items()->create([
                'raw_material_id'       => $type === 'raw_material'       ? $item['raw_material_id']       : null,
                'auxiliary_material_id' => $type === 'auxiliary_material' ? $item['auxiliary_material_id'] : null,
                'quantity'              => $baseQuantity, // Store in base unit for inventory
                'unit'                  => $selectedUnit, // Store the selected unit for display
                'conversion_factor'     => $finalConversionFactor, // Store custom conversion factor
                'unit_price'            => $baseUnitPrice, // Store base unit price
                'subtotal'              => $baseQuantity * $baseUnitPrice, // Calculate based on base quantity and base unit price
                'tax_type'              => $item['tax_type'] ?? 'after_tax',
            ]);
        }

        $purchase->calculateTotals();

        // Update stock when purchase is updated (only if status is draft)
        if ($purchase->status === 'draft') {
            // First, revert the stock changes from the original items
            $originalItems = $purchase->items()->get();
            foreach ($originalItems as $originalItem) {
                if ($originalItem->raw_material_id) {
                    $rawMaterial = $originalItem->rawMaterial;
                    if ($rawMaterial) {
                        $oldQty   = (float) ($rawMaterial->stock ?? 0);
                        $oldPrice = (float) ($rawMaterial->price_per_unit ?? 0);
                        $oldTotal = $oldQty * $oldPrice;

                        $removeQty = (float) ($originalItem->quantity ?? 0);
                        $removePrice = (float) ($originalItem->unit_price ?? 0);
                        $removeTotal = $removeQty * $removePrice;

                        $newQty = max(0, $oldQty - $removeQty);
                        $newTotal = max(0, $oldTotal - $removeTotal);
                        $newAvgPrice = $newQty > 0 ? $newTotal / $newQty : $oldPrice;

                        $rawMaterial->stock = $newQty;
                        $rawMaterial->price_per_unit = $newAvgPrice;
                        $rawMaterial->save();
                    }
                } elseif ($originalItem->auxiliary_material_id) {
                    $aux = $originalItem->auxiliaryMaterial;
                    if ($aux) {
                        $oldQty   = (float) ($aux->stock ?? 0);
                        $oldPrice = (float) ($aux->price_per_unit ?? 0);
                        $oldTotal = $oldQty * $oldPrice;

                        $removeQty = (float) ($originalItem->quantity ?? 0);
                        $removePrice = (float) ($originalItem->unit_price ?? 0);
                        $removeTotal = $removeQty * $removePrice;

                        $newQty = max(0, $oldQty - $removeQty);
                        $newTotal = max(0, $oldTotal - $removeTotal);
                        $newAvgPrice = $newQty > 0 ? $newTotal / $newQty : $oldPrice;

                        $aux->stock = $newQty;
                        $aux->price_per_unit = $newAvgPrice;
                        $aux->save();
                    }
                }
            }

            // Now add the new items to stock
            foreach ($purchase->items as $item) {
                if ($item->raw_material_id) {
                    $rawMaterial = $item->rawMaterial;
                    if (! $rawMaterial) continue;

                    $oldQty   = (float) ($rawMaterial->stock ?? 0);
                    $oldPrice = (float) ($rawMaterial->price_per_unit ?? 0);
                    $oldTotal = $oldQty * $oldPrice;

                    $newQty = (float) ($item->quantity ?? 0);
                    $newPrice = (float) ($item->unit_price ?? 0);
                    $newTotal = $newQty * $newPrice;

                    $combinedQty = $oldQty + $newQty;
                    $combinedTotal = $oldTotal + $newTotal;
                    $newAvgPrice = $combinedQty > 0 ? $combinedTotal / $combinedQty : $oldPrice;

                    $rawMaterial->stock = $combinedQty;
                    $rawMaterial->price_per_unit = $newAvgPrice;
                    $rawMaterial->save();

                } elseif ($item->auxiliary_material_id) {
                    $aux = $item->auxiliaryMaterial;
                    if (! $aux) continue;

                    $oldQty   = (float) ($aux->stock ?? 0);
                    $oldPrice = (float) ($aux->price_per_unit ?? 0);
                    $oldTotal = $oldQty * $oldPrice;

                    $newQty = (float) ($item->quantity ?? 0);
                    $newPrice = (float) ($item->unit_price ?? 0);
                    $newTotal = $newQty * $newPrice;

                    $combinedQty = $oldQty + $newQty;
                    $combinedTotal = $oldTotal + $newTotal;
                    $newAvgPrice = $combinedQty > 0 ? $combinedTotal / $combinedQty : $oldPrice;

                    $aux->stock = $combinedQty;
                    $aux->price_per_unit = $newAvgPrice;
                    $aux->save();
                }
            }
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Purchase Order berhasil diupdate. Stok telah diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.index')
                ->with('error', 'Hanya purchase order dengan status draft yang bisa dihapus');
        }

        // Revert stock changes before deleting
        foreach ($purchase->items as $item) {
            if ($item->raw_material_id) {
                $rawMaterial = $item->rawMaterial;
                if ($rawMaterial) {
                    $oldQty   = (float) ($rawMaterial->stock ?? 0);
                    $oldPrice = (float) ($rawMaterial->price_per_unit ?? 0);
                    $oldTotal = $oldQty * $oldPrice;

                    $removeQty = (float) ($item->quantity ?? 0);
                    $removePrice = (float) ($item->unit_price ?? 0);
                    $removeTotal = $removeQty * $removePrice;

                    $newQty = max(0, $oldQty - $removeQty);
                    $newTotal = max(0, $oldTotal - $removeTotal);
                    $newAvgPrice = $newQty > 0 ? $newTotal / $newQty : $oldPrice;

                    $rawMaterial->stock = $newQty;
                    $rawMaterial->price_per_unit = $newAvgPrice;
                    $rawMaterial->save();
                }
            } elseif ($item->auxiliary_material_id) {
                $aux = $item->auxiliaryMaterial;
                if ($aux) {
                    $oldQty   = (float) ($aux->stock ?? 0);
                    $oldPrice = (float) ($aux->price_per_unit ?? 0);
                    $oldTotal = $oldQty * $oldPrice;

                    $removeQty = (float) ($item->quantity ?? 0);
                    $removePrice = (float) ($item->unit_price ?? 0);
                    $removeTotal = $removeQty * $removePrice;

                    $newQty = max(0, $oldQty - $removeQty);
                    $newTotal = max(0, $oldTotal - $removeTotal);
                    $newAvgPrice = $newQty > 0 ? $newTotal / $newQty : $oldPrice;

                    $aux->stock = $newQty;
                    $aux->price_per_unit = $newAvgPrice;
                    $aux->save();
                }
            }
        }

        $purchase->delete();

        return redirect()->route('purchases.index')
            ->with('success', 'Purchase Order berhasil dihapus. Stok telah dikembalikan.');
    }

    /**
     * Approve purchase order
     */
    public function approve(Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Purchase order sudah diapprove atau received');
        }

        $purchase->update(['status' => 'approved']);

        // Jika bahan penolong, tambahkan ke biaya_overhead saat approve (per item)
        if (($purchase->purchase_type ?? 'raw_material') === Purchase::TYPE_AUXILIARY_MATERIAL) {
            $purchase->load('items.auxiliaryMaterial');

            $totalSubtotal = $purchase->items->sum('subtotal');
            $totalDiscount = $purchase->discount_amount ?? 0;
            $totalFob      = $purchase->fob_cost ?? 0;

            foreach ($purchase->items as $item) {
                $aux = $item->auxiliaryMaterial;
                if (!$aux) continue;

                // Hitung DPP item
                $itemDpp = $item->tax_type === 'after_tax'
                    ? $item->subtotal / (1 + ($purchase->ppn_rate / 100))
                    : $item->subtotal;

                // Alokasi diskon & FOB proporsional
                $itemDiscount = $totalSubtotal > 0 ? ($itemDpp / $totalSubtotal) * $totalDiscount : 0;
                $itemFob      = $totalSubtotal > 0 ? ($itemDpp / $totalSubtotal) * $totalFob : 0;
                $itemTotalBeban = ($itemDpp - $itemDiscount) + $itemFob;

                if ($itemTotalBeban <= 0) continue;

                // Cari atau buat COA "BOP BP - [nama]"
                $coaName = 'BOP BP - ' . $aux->name;
                $bebanCoa = \App\Models\ChartOfAccount::where('account_name', $coaName)
                    ->where('code', 'LIKE', '5%')->first();

                if (!$bebanCoa) {
                    $bebanCoa = \App\Models\ChartOfAccount::create([
                        'code'         => '55' . str_pad($aux->id, 3, '0', STR_PAD_LEFT),
                        'account_name' => $coaName,
                        'account_type' => 'expense',
                        'normal_balance_position' => 'debit',
                        'account_group_name' => 'Beban',
                        'is_active'    => true,
                    ]);
                }

                // Update atau insert ke biaya_overhead
                $periode = $purchase->purchase_date;
                $existing = \App\Models\BiayaOverhead::where('kategori', 'BOP')
                    ->where('chart_of_account_id', $bebanCoa->id)
                    ->whereRaw("DATE_FORMAT(periode, '%Y-%m') = ?", [$periode->format('Y-m')])
                    ->first();

                if ($existing) {
                    $existing->update([
                        'total'   => $existing->total + round($itemTotalBeban, 2),
                        'catatan' => $existing->catatan . ' + PO: ' . $purchase->purchase_number,
                    ]);
                } else {
                    \App\Models\BiayaOverhead::create([
                        'jenis_biaya'          => 'Pembelian Bahan Penolong - ' . $aux->name,
                        'bop_code'             => 'BOP-BP-' . $aux->id . '-' . $periode->format('Ym'),
                        'periode'              => $periode,
                        'kategori'             => 'BOP',
                        'chart_of_account_id'  => $bebanCoa->id,
                        'total'                => round($itemTotalBeban, 2),
                        'catatan'              => 'PO: ' . $purchase->purchase_number . ' - ' . $aux->name,
                    ]);
                }
            }
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Purchase Order berhasil diapprove');
    }

    /**
     * Receive purchase order and update stock
     */
    public function receive(Request $request, Purchase $purchase)
    {
        if ($purchase->status !== 'approved') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Purchase order harus diapprove terlebih dahulu');
        }

        $validated = $request->validate([
            'receipt_document'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'received_by_employee_id'    => 'required|exists:employees,id',
            'invoice_number'             => 'required|string|max:100',
        ]);

        $receiptPath = $request->file('receipt_document')->store('purchase_receipts', 'public');

        // Stock already updated when purchase was created, so no need to update again
        // Just update the status and receipt information

        $purchase->update([
            'status'                  => 'received',
            'received_by_employee_id' => $validated['received_by_employee_id'],
            'receipt_document_path'   => $receiptPath,
            'invoice_number'          => $validated['invoice_number'],
        ]);

        // Update price_per_unit ke average terbaru setelah diterima
        $purchase->load('items.rawMaterial', 'items.auxiliaryMaterial');
        foreach ($purchase->items as $item) {
            if ($item->rawMaterial) {
                $avg = $item->rawMaterial->getAverageUnitPrice();
                $item->rawMaterial->update(['price_per_unit' => $avg]);
            }
            if ($item->auxiliaryMaterial) {
                $avg = $item->auxiliaryMaterial->getAverageUnitPrice();
                $item->auxiliaryMaterial->update(['price_per_unit' => $avg]);
            }
        }

        $label = ($purchase->purchase_type ?? 'raw_material') === Purchase::TYPE_AUXILIARY_MATERIAL
            ? 'bahan penolong'
            : 'bahan baku';

        return redirect()->route('purchases.show', $purchase)
            ->with('success', "Purchase Order berhasil diterima. Stok {$label} sudah diperbarui saat pembuatan PO.");
    }

    /**
     * Display debt index page
     */
    public function debtIndex(Request $request)
    {
        try {
            $purchases = Purchase::with('supplier')
                ->where('payment_method', 'credit')
                ->where('payment_status', '!=', 'paid')
                ->latest('due_date')
                ->paginate(15);

            return view('purchases.debt-index', compact('purchases'));
        } catch (\Exception $e) {
            \Log::error('Error in debtIndex: ' . $e->getMessage());
            return redirect()->route('purchases.index')
                ->with('error', 'Terjadi kesalahan saat memuat data hutang');
        }
    }

    /**
     * Show form for paying debt
     */
    public function payDebt(Purchase $purchase)
    {
        if ($purchase->payment_method !== 'credit') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Hanya purchase order dengan metode pembayaran hutang yang bisa dilunasi');
        }

        return view('purchases.pay-debt', compact('purchase'));
    }

    /**
     * Store debt payment
     */
    public function storeDebtPayment(Request $request, Purchase $purchase)
    {
        if ($purchase->payment_method !== 'credit') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Hanya purchase order dengan metode pembayaran hutang yang bisa dilunasi');
        }

        if ($purchase->payment_status === 'paid') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Purchase order ini sudah lunas');
        }

        $validated = $request->validate([
            'payment_date'   => 'required|date',
            'payment_method' => 'required|in:cash,transfer,giro,cek',
            'amount'         => 'required|numeric|min:0.01|max:' . $purchase->remaining_amount,
            'bank_account'   => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:500',
        ]);

        $payment = $purchase->payments()->create([
            'payment_date'   => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'amount'         => $validated['amount'],
            'bank_account'   => $validated['bank_account'] ?? null,
            'notes'          => $validated['notes'] ?? null,
        ]);

        $purchase->updatePaymentStatus();

        $journalService = new JournalService();
        $journalService->createJournalFromPurchasePayment($purchase, $payment);

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'Pembayaran hutang berhasil dicatat');
    }
}

