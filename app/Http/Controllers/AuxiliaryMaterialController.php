<?php

namespace App\Http\Controllers;

use App\Models\AuxiliaryMaterial;
use App\Models\Supplier;
use App\Models\AuxiliaryMaterialUnitConversion;
use App\Models\Unit;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuxiliaryMaterialController extends Controller
{
    public function index()
    {
        $auxiliaryMaterials = AuxiliaryMaterial::with(['supplier', 'unitConversions', 'chartOfAccount'])
            ->orderByRaw('CAST(SUBSTRING(code, 4) AS UNSIGNED) ASC')
            ->paginate(15);
        $auxiliaryMaterials->getCollection()->transform(function ($material) {
            $material->calculated_stock = $material->getCurrentStock();
            // Gunakan harga average dari transaksi, bukan dari tabel
            $material->calculated_price = $material->getAverageUnitPrice();
            return $material;
        });
        $unitMap = Unit::pluck('name', 'code');
        return view('auxiliary-materials.index', compact('auxiliaryMaterials', 'unitMap'));
    }

    /**
     * API: kembalikan kode berikutnya untuk bahan penolong (untuk tombol Refresh Kode di form create).
     */
    public function generateCode()
    {
        return response()->json(['code' => AuxiliaryMaterial::generateCode()]);
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $units = Unit::where('is_active', true)->orderBy('code')->get();
        $nextCode = AuxiliaryMaterial::generateCode();
        return view('auxiliary-materials.create', compact('suppliers', 'units', 'nextCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', Rule::unique('auxiliary_materials', 'code')],
            'name' => 'required',
            'description' => 'nullable',
            'unit' => 'required',
            'recipe_unit' => 'nullable|string',
            'recipe_conversion_factor' => 'nullable|numeric|min:0.000001',
            'price_per_unit' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = AuxiliaryMaterial::generateCode();
        }

        // Set default value for minimum_stock if not provided
        if (!isset($validated['minimum_stock'])) {
            $validated['minimum_stock'] = 0;
        }

        // Gunakan DB transaction untuk memastikan konsistensi data
        $auxiliaryMaterial = DB::transaction(function () use ($validated, $request) {
            // 1. Simpan auxiliary material
            // Juga simpan master_price_per_unit agar tidak ikut berubah saat pembelian
            $validated['master_price_per_unit'] = $validated['price_per_unit'] ?? 0;
            $auxiliaryMaterial = AuxiliaryMaterial::create($validated);

            // 2. Buat COA otomatis untuk auxiliary material
            $coaAccount = ChartOfAccount::createAuxiliaryMaterialAccount($validated['name']);

            if ($coaAccount) {
                // 3. Update auxiliary material dengan COA ID
                $auxiliaryMaterial->update(['chart_of_account_id' => $coaAccount->id]);
                \Log::info('COA created for auxiliary material: ' . $validated['name'] . ' with code: ' . $coaAccount->code);
            } else {
                \Log::warning('Failed to create COA for auxiliary material: ' . $validated['name']);
            }

            // 4. Simpan konversi multi-satuan jika ada
            $conversions = $request->input('conversions', []);
            if (is_array($conversions) && $auxiliaryMaterial->unit) {
                foreach ($conversions as $conv) {
                    $toUnit = $conv['to_unit'] ?? null;
                    if ($toUnit) {
                        AuxiliaryMaterialUnitConversion::create([
                            'auxiliary_material_id' => $auxiliaryMaterial->id,
                            'from_unit' => $auxiliaryMaterial->unit,
                            'to_unit' => $toUnit,
                            'factor' => 1, // Default factor since we're only storing units
                        ]);
                    }
                }
            }

            return $auxiliaryMaterial;
        });

        $message = 'Bahan penolong berhasil ditambahkan';

        // Tambahkan info COA jika berhasil dibuat
        $coaAccount = ChartOfAccount::find($auxiliaryMaterial->chart_of_account_id);
        if ($coaAccount) {
            $message .= ' (COA: ' . $coaAccount->code . ' - ' . $coaAccount->account_name . ')';
        }

        return redirect()->route('auxiliary-materials.index')->with('success', $message);
    }

    public function show(AuxiliaryMaterial $auxiliaryMaterial)
    {
        $auxiliaryMaterial->load(['supplier', 'chartOfAccount']);
        return view('auxiliary-materials.show', compact('auxiliaryMaterial'));
    }

    public function edit(AuxiliaryMaterial $auxiliaryMaterial)
    {
        $auxiliaryMaterial->load('unitConversions');
        $suppliers = Supplier::all();
        $units = Unit::where('is_active', true)->orderBy('code')->get();
        return view('auxiliary-materials.edit', compact('auxiliaryMaterial', 'suppliers', 'units'));
    }

    public function update(Request $request, AuxiliaryMaterial $auxiliaryMaterial)
    {
        $validated = $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'unit' => 'required',
            'recipe_unit' => 'nullable|string',
            'recipe_conversion_factor' => 'nullable|numeric|min:0.000001',
            'price_per_unit' => 'required|numeric|min:0',
            'minimum_stock' => 'nullable|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        // Debug: Log the minimum_stock value
        \Log::info('Minimum stock received: ' . ($validated['minimum_stock'] ?? 'NULL'));

        // Set default value for minimum_stock if not provided
        if (!isset($validated['minimum_stock'])) {
            $validated['minimum_stock'] = 0;
        }

        // Jika satuan dasar berubah, gunakan mekanisme konversi agar stok dan transaksi ikut menyesuaikan
        if ($validated['unit'] !== $auxiliaryMaterial->unit) {
            $auxiliaryMaterial->convertUnit($validated['unit']);

            // Setelah konversi, kita tidak ingin menimpa unit/price_per_unit hasil hitungan
            unset($validated['unit'], $validated['price_per_unit']);
        }

        // Simpan juga master_price_per_unit (harga tetap dari admin, tidak ikut berubah saat pembelian)
        if (isset($validated['price_per_unit'])) {
            $validated['master_price_per_unit'] = $validated['price_per_unit'];
        }

        $auxiliaryMaterial->update($validated);

        // Sync konversi multi-satuan
        $conversions = $request->input('conversions', []);
        $auxiliaryMaterial->unitConversions()->delete();
        if (is_array($conversions) && $auxiliaryMaterial->unit) {
            foreach ($conversions as $conv) {
                $toUnit = $conv['to_unit'] ?? null;
                if ($toUnit) {
                    AuxiliaryMaterialUnitConversion::create([
                        'auxiliary_material_id' => $auxiliaryMaterial->id,
                        'from_unit' => $auxiliaryMaterial->unit,
                        'to_unit' => $toUnit,
                        'factor' => 1, // Default factor since we're only storing units
                    ]);
                }
            }
        }

        return redirect()->route('auxiliary-materials.index')->with('success', 'Bahan penolong berhasil diupdate');
    }

    public function destroy(AuxiliaryMaterial $auxiliaryMaterial)
    {
        // Cek apakah bahan penolong sudah dipakai di transaksi lain
        // Note: Tambahkan relasi ke tabel yang menggunakan auxiliary material
        // Cek apakah dipakai di BOM
        $usedInBom = \App\Models\BillOfMaterialAuxiliary::where('auxiliary_material_id', $auxiliaryMaterial->id)->exists();
        if ($usedInBom) {
            return redirect()->route('auxiliary-materials.index')
                ->with('error', 'Bahan penolong tidak bisa dihapus karena masih digunakan di BOM.');
        }

        // Hapus purchase items terkait dulu (cascade manual)
        \App\Models\PurchaseItem::where('auxiliary_material_id', $auxiliaryMaterial->id)->delete();

        // Hapus unit conversions
        $auxiliaryMaterial->unitConversions()->delete();

        $auxiliaryMaterial->delete();

        return redirect()->route('auxiliary-materials.index')->with('success', 'Bahan penolong berhasil dihapus');
    }

    public function updateUnit(Request $request, AuxiliaryMaterial $auxiliaryMaterial)
    {
        $validated = $request->validate([
            'unit' => 'required|in:' . implode(',', array_keys(AuxiliaryMaterial::UNIT_OPTIONS)),
        ]);

        $converted = $auxiliaryMaterial->convertUnit($validated['unit']);

        if (!$converted) {
            return redirect()->route('auxiliary-materials.index')->with('error', 'Konversi satuan tidak didukung.');
        }

        return redirect()->route('auxiliary-materials.index')->with('success', 'Satuan dan stok bahan penolong berhasil diupdate');
    }

    public function updateMinimumStock(Request $request, AuxiliaryMaterial $auxiliaryMaterial)
    {
        $validated = $request->validate([
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $auxiliaryMaterial->update(['minimum_stock' => $validated['minimum_stock']]);

        // Check if request is AJAX
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Stok minimum berhasil diupdate'
            ]);
        }

        return redirect()->route('auxiliary-materials.index')->with('success', 'Stok minimum berhasil diupdate');
    }

    /**
     * Kartu stok bahan penolong: mutasi dari pembelian (masuk), retur pembelian (keluar), dan penggunaan JO (keluar).
     * Metode: FIFO atau Moving Average; saldo dan harga dihitung di controller.
     */
    public function stockCard(Request $request, AuxiliaryMaterial $auxiliaryMaterial)
    {
        $auxiliaryMaterial->load('unitConversions');

        // Default metode ke FIFO supaya tampilan utama kartu stok penolong sesuai permintaan
        $method = $request->query('method', 'fifo');
        $month = $request->query('month');
        $year = $request->query('year');
        $hasPeriod = $month && $year;
        $startOfPeriod = $hasPeriod ? Carbon::create((int) $year, (int) $month, 1)->startOfDay() : null;
        $endOfPeriod = $hasPeriod ? (clone $startOfPeriod)->endOfMonth() : null;

        $recipeUnit = $this->getRecipeUnitForAuxiliaryMaterial($auxiliaryMaterial);
        $selectedUnit = $request->query('unit', $recipeUnit ?? $auxiliaryMaterial->unit);
        if (!$selectedUnit) {
            $selectedUnit = $auxiliaryMaterial->unit; // fallback keras supaya tidak null
        }
        $selectedMethod = $method;

        $unitCodes = collect([$auxiliaryMaterial->unit])
            ->merge($auxiliaryMaterial->unitConversions->pluck('to_unit'))
            ->when($recipeUnit, fn($c) => $c->push($recipeUnit))
            ->unique()
            ->values();

        $unitMap = Unit::whereIn('code', $unitCodes)->pluck('name', 'code');

        // displayFactor: berapa unit tampilan per 1 unit dasar
        // Ambil dari conversion_factor di purchase_items (lebih akurat dari unit_conversions table yang factornya 1)
        if (strtoupper(trim($selectedUnit)) === strtoupper(trim($auxiliaryMaterial->unit))) {
            $displayFactor = 1.0;
        } else {
            // Cari purchase item yang punya unit konversi ke selectedUnit
            $sampleItem = \App\Models\PurchaseItem::where('auxiliary_material_id', $auxiliaryMaterial->id)
                ->whereNotNull('conversion_factor')
                ->where('conversion_factor', '>', 0)
                ->first();
            $displayFactor = $sampleItem ? (float) $sampleItem->conversion_factor : 1.0;
        }

        // Mutasi MASUK: dari pembelian (draft, approved, received)
        $purchaseItems = \App\Models\PurchaseItem::with('purchase')
            ->where('auxiliary_material_id', $auxiliaryMaterial->id)
            ->whereHas('purchase', function ($q) {
                $q->whereIn('status', ['draft', 'approved', 'received']);
            })
            ->get()
            ->map(function ($item) use ($auxiliaryMaterial) {
                $purchase = $item->purchase;

                // Determine qty in BASE unit.
                // If item was stored in alt unit (unit ≠ base unit), multiply by factor to get base qty.
                // If item was stored in base unit directly, quantity IS already in base unit.
                $storedQty = (float) ($item->quantity ?? 0);
                $itemUnit = $item->unit;                         // unit user selected at purchase
                $baseUnit = $auxiliaryMaterial->unit;
                $convFactor = (float) ($item->conversion_factor ?? 0);
                $unitPriceBase = (float) ($item->unit_price ?? 0);

                if ($convFactor > 0 && $itemUnit && strtolower($itemUnit) !== strtolower($baseUnit)) {
                    $qtyInBase = $storedQty / $convFactor;
                } else {
                    $qtyInBase = $storedQty;
                }

                // Hitung harga efektif (DPP setelah diskon) untuk kartu stok
                // Kartu stok mencatat nilai persediaan = DPP setelah diskon (bukan harga termasuk PPN, bukan harga kotor)
                $ppnRate = (float) ($purchase?->ppn_rate ?? 0);
                $discountRate = (float) ($purchase?->discount_rate ?? 0);
                $taxType = $item->tax_type ?? 'after_tax';

                // Langkah 1: Dapatkan DPP per unit (hilangkan PPN jika after_tax)
                if ($taxType === 'after_tax' && $ppnRate > 0) {
                    $dppPerUnit = $unitPriceBase / (1 + ($ppnRate / 100));
                } else {
                    $dppPerUnit = $unitPriceBase;
                }

                // Langkah 2: Terapkan diskon ke DPP
                $effectivePrice = $discountRate > 0
                    ? $dppPerUnit * (1 - ($discountRate / 100))
                    : $dppPerUnit;

                $sortDate = $purchase?->created_at ?? $purchase?->purchase_date;

                return (object) [
                    'date' => $sortDate,
                    'display_date' => $purchase?->purchase_date,
                    'type' => 'in',
                    'qty_in' => $qtyInBase,
                    'qty_out' => 0,
                    'unit_price_in' => $effectivePrice,
                    'unit_price_out' => null,
                    'notes' => $purchase ? ('Pembelian PO ' . $purchase->purchase_number) : null,
                ];
            });

        // Mutasi KELUAR: retur pembelian (satuan dasar)
        $returnItems = \App\Models\PurchaseReturnItem::with('purchaseReturn')
            ->where('auxiliary_material_id', $auxiliaryMaterial->id)
            ->get()
            ->map(function ($item) use ($auxiliaryMaterial) {
                $return = $item->purchaseReturn;

                // Return quantities are stored in the unit they were returned in
                $fromUnit = $item->unit ?: $auxiliaryMaterial->unit;
                $toUnit = $auxiliaryMaterial->unit;
                $factor = $fromUnit && $toUnit ? $auxiliaryMaterial->getMaterialConversionFactor($fromUnit, $toUnit) : 1;
                $qtyOutBase = ($item->quantity ?? 0) * $factor;

                // Harga retur: ambil dari harga beli item terkait, hitung DPP setelah diskon
                $relatedPurchaseItem = \App\Models\PurchaseItem::with('purchase')->find($item->purchase_item_id);
                $relatedPurchase = $relatedPurchaseItem?->purchase;

                $rawPrice = (float) ($relatedPurchaseItem?->unit_price ?? 0);
                $ppnRateRet = (float) ($relatedPurchase?->ppn_rate ?? 0);
                $discountRet = (float) ($relatedPurchase?->discount_rate ?? 0);
                $taxTypeRet = $relatedPurchaseItem?->tax_type ?? 'after_tax';

                $dppRet = ($taxTypeRet === 'after_tax' && $ppnRateRet > 0)
                    ? $rawPrice / (1 + ($ppnRateRet / 100))
                    : $rawPrice;

                $unitPriceOut = $discountRet > 0
                    ? $dppRet * (1 - ($discountRet / 100))
                    : $dppRet;

                $sortDate = $return?->return_date ?? $return?->created_at;

                return (object) [
                    'date' => $sortDate,
                    'display_date' => $return?->return_date,
                    'type' => 'out',
                    'qty_in' => 0,
                    'qty_out' => $qtyOutBase,
                    'unit_price_in' => null,
                    'unit_price_out' => $unitPriceOut,
                    'notes' => $return ? ('Retur ' . $return->return_number) : null,
                ];
            });

        // Mutasi KELUAR: pemakaian bahan penolong dari Job Order (berdasarkan BOM produk)
        $usageDetails = \App\Models\JobOrderDetail::with(['jobOrder', 'product'])
            ->whereHas('jobOrder', function ($q) {
                $q->whereIn('status', ['in_progress', 'completed']);
            })
            ->get();

        $usageEntries = $usageDetails->flatMap(function ($detail) use ($auxiliaryMaterial) {
            $job = $detail->jobOrder;
            $product = $detail->product;
            if (!$job || !$product)
                return [];

            // Ambil dari BOM (sumber kebenaran)
            $bom = \App\Models\BillOfMaterial::with('auxiliaries.auxiliaryMaterial')
                ->where('product_id', $product->id)->first();
            if (!$bom)
                return [];

            $result = [];
            foreach ($bom->auxiliaries as $bomAux) {
                if ($bomAux->auxiliary_material_id !== $auxiliaryMaterial->id)
                    continue;

                $qtyPerUnit = (float) ($bomAux->quantity ?? 0);
                if ($qtyPerUnit <= 0)
                    continue;

                $qtyTotalRecipe = $qtyPerUnit * (float) ($detail->quantity ?? 0);
                if ($qtyTotalRecipe <= 0)
                    continue;

                $recipeUnit = $bomAux->unit ?: $auxiliaryMaterial->unit;
                $baseUnit = $auxiliaryMaterial->unit;

                // Konversi ke base unit
                $qtyOutBase = $qtyTotalRecipe;
                if ($recipeUnit !== $baseUnit) {
                    $sampleItem = \App\Models\PurchaseItem::where('auxiliary_material_id', $auxiliaryMaterial->id)
                        ->whereNotNull('conversion_factor')->where('conversion_factor', '>', 0)->first();
                    $convFactor = $sampleItem ? (float) $sampleItem->conversion_factor : 1.0;
                    if ($convFactor > 0)
                        $qtyOutBase = $qtyTotalRecipe / $convFactor;
                }

                $date = $job->mulai_job_at ?? $job->order_date ?? $job->created_at;
                $result[] = (object) [
                    'date' => $date,
                    'display_date' => $date,
                    'type' => 'out',
                    'qty_in' => 0,
                    'qty_out' => $qtyOutBase,
                    'unit_price_in' => null,
                    'unit_price_out' => (float) ($auxiliaryMaterial->price_per_unit ?? 0),
                    'notes' => 'Pemakaian untuk Job ' . ($job->kode_job ?? $job->id) . ' - ' . ($product->name ?? ''),
                ];
            }
            return $result;
        });

        // Gabungkan semua mutasi (masuk & keluar) dan urutkan kronologis
        $entries = collect()
            ->concat($purchaseItems)
            ->concat($usageEntries)
            ->sortBy('date')
            ->values()
            ->filter(function ($entry) {
                return !empty($entry->date) && (isset($entry->notes) || in_array($entry->type ?? '', ['in', 'out'], true));
            })
            ->sortBy(function ($e) {
                $d = $e->date instanceof Carbon ? $e->date->format('Y-m-d H:i:s') : \Carbon\Carbon::parse($e->date)->format('Y-m-d H:i:s');
                $order = ($e->type ?? '') === 'in' ? 0 : 1;
                return $d . $order;
            })
            ->values();

        // Hitung saldo dan harga (FIFO / Average) di controller
        if ($method === 'fifo') {
            $entries = $this->applyFifoToEntries($entries, $hasPeriod, $startOfPeriod, $endOfPeriod);
        } else {
            // Untuk Average, pastikan baris "Saldo Awal" tetap ditambahkan
            if ($hasPeriod) {
                $batches = [];
                // Check if balance exists
                $balance = \App\Models\MaterialStockBalance::where('material_type', 'AuxiliaryMaterial')
                    ->where('material_id', $auxiliaryMaterial->id)
                    ->where('period', $startOfPeriod->format('Y-m-d'))
                    ->first();

                $openingQty = 0.0;
                $openingTotal = 0.0;

                if ($balance) {
                    $openingQty = (float) $balance->beginning_qty;
                    $openingTotal = (float) $balance->beginning_value;
                } else {
                    // Fallback calculate
                    foreach ($entries as $entry) {
                        $type = $entry->type ?? null;
                        $entryDate = $entry->date instanceof Carbon ? $entry->date : \Carbon\Carbon::parse($entry->date);
                        if ($entryDate->lt($startOfPeriod)) {
                            if ($type === 'in') {
                                $batches[] = ['qty' => (float) ($entry->qty_in ?? 0), 'price' => (float) ($entry->unit_price_in ?? 0)];
                            } elseif ($type === 'out') {
                                $rem = (float) ($entry->qty_out ?? 0);
                                while ($rem > 0 && !empty($batches)) {
                                    $take = min($rem, $batches[0]['qty']);
                                    $batches[0]['qty'] -= $take;
                                    $rem -= $take;
                                    if ($batches[0]['qty'] <= 0.0000001)
                                        array_shift($batches);
                                }
                            }
                        }
                    }
                    foreach ($batches as $b) {
                        $openingQty += $b['qty'];
                        $openingTotal += $b['qty'] * $b['price'];
                    }
                }

                $openingEntry = (object) [
                    'date' => $startOfPeriod,
                    'display_date' => null,
                    'type' => 'opening',
                    'qty_in' => 0,
                    'qty_out' => 0,
                    'unit_price_in' => null,
                    'unit_price_out' => null,
                    'notes' => 'Saldo awal',
                    'fifo_balance_qty' => $openingQty,
                    'fifo_balance_total' => $openingTotal,
                    'fifo_balance_avg_price' => $openingQty > 0 ? $openingTotal / $openingQty : 0,
                ];

                $entries = collect([$openingEntry])->concat(
                    $entries->filter(function ($e) use ($startOfPeriod, $endOfPeriod) {
                        $d = $e->date instanceof Carbon ? $e->date : \Carbon\Carbon::parse($e->date);
                        return $d->betweenIncluded($startOfPeriod, $endOfPeriod);
                    })
                )->values();
            }
        }

        // Stok saat ini dari akumulasi transaksi (konsisten dengan kartu stok)
        $finalBalance = $auxiliaryMaterial->getCurrentStock();
        $currentStockDisplay = round($finalBalance * $displayFactor);

        // Check if posted
        $isPosted = false;
        if ($hasPeriod) {
            $isPosted = \App\Models\MaterialStockBalance::where('material_type', 'AuxiliaryMaterial')
                ->where('material_id', $auxiliaryMaterial->id)
                ->where('period', $startOfPeriod->format('Y-m-d'))
                ->where('is_posted', true)
                ->exists();
        }

        return view('auxiliary-materials.stock-card', compact(
            'auxiliaryMaterial',
            'entries',
            'unitMap',
            'selectedUnit',
            'selectedMethod',
            'displayFactor',
            'currentStockDisplay',
            'method',
            'month',
            'year',
            'hasPeriod',
            'isPosted'
        ));
    }

    /**
     * Entri keluar dari penggunaan bahan penolong di job order (saat job mulai).
     */
    private function buildAuxiliaryUsageEntries(AuxiliaryMaterial $auxiliaryMaterial): \Illuminate\Support\Collection
    {
        $jobOrders = \App\Models\JobOrder::with(['jobOrderDetails.product.auxiliaryMaterials'])
            ->whereNotNull('mulai_job_at')
            ->orderBy('mulai_job_at')
            ->get();

        $result = collect();
        foreach ($jobOrders as $job) {
            $totalQtyBase = 0.0;
            foreach ($job->jobOrderDetails as $detail) {
                $product = $detail->product;
                if (!$product) {
                    continue;
                }
                $aux = $product->auxiliaryMaterials->firstWhere('id', $auxiliaryMaterial->id);
                if (!$aux) {
                    continue;
                }
                $qtyPerUnit = (float) ($aux->pivot->quantity_needed ?? 0);
                if ($qtyPerUnit <= 0) {
                    continue;
                }
                $qtyTotalRecipe = $qtyPerUnit * (float) $detail->quantity;
                $baseUnit = $auxiliaryMaterial->unit;
                $recipeUnit = $aux->pivot->unit ?: $baseUnit;
                $factorToBase = ($baseUnit && $recipeUnit && $baseUnit !== $recipeUnit)
                    ? (float) $auxiliaryMaterial->getMaterialConversionFactor($recipeUnit, $baseUnit)
                    : 1.0;
                if ($factorToBase > 0) {
                    $totalQtyBase += $qtyTotalRecipe * $factorToBase;
                } else {
                    $totalQtyBase += $qtyTotalRecipe;
                }
            }
            if ($totalQtyBase > 0) {
                $result->push((object) [
                    'date' => $job->mulai_job_at,
                    'display_date' => $job->mulai_job_at,
                    'type' => 'out',
                    'qty_in' => 0,
                    'qty_out' => $totalQtyBase,
                    'unit_price_in' => null,
                    'unit_price_out' => null,
                    'notes' => 'Penggunaan JO ' . ($job->kode_job ?? $job->id),
                ]);
            }
        }
        return $result;
    }

    /**
     * Terapkan FIFO: untuk keluar konsumsi dari batch paling lama, set unit_price_out dan saldo per baris.
     */
    private function applyFifoToEntries(\Illuminate\Support\Collection $entries, bool $hasPeriod, $startOfPeriod, $endOfPeriod): \Illuminate\Support\Collection
    {
        $batches = [];
        $processedEntries = collect();
        $openingInserted = false;

        foreach ($entries as $entry) {
            $type = $entry->type ?? null;
            $entryDate = $entry->date ?? null;
            if (!$entryDate instanceof Carbon && $entryDate !== null) {
                $entryDate = Carbon::parse($entryDate);
            }

            // 1. Get Opening Balance from MaterialStockBalance if available
            if ($hasPeriod && !$openingInserted) {
                $balance = \App\Models\MaterialStockBalance::where('material_type', 'AuxiliaryMaterial')
                    ->where('material_id', $entries->first()?->auxiliary_material_id ?? 0) // We might need a better way to get ID
                    ->where('period', $startOfPeriod->format('Y-m-d'))
                    ->first();

                if ($balance) {
                    $openingQty = (float) $balance->beginning_qty;
                    $openingTotal = (float) $balance->beginning_value;

                    if ($openingQty > 0) {
                        $batches = [['qty' => $openingQty, 'price' => $openingQty > 0 ? $openingTotal / $openingQty : 0]];
                    }
                    $openingInserted = true;
                    // Push opening entry
                    $processedEntries->push((object) [
                        'date' => $startOfPeriod,
                        'display_date' => null,
                        'type' => 'opening',
                        'qty_in' => 0,
                        'qty_out' => 0,
                        'unit_price_in' => null,
                        'unit_price_out' => null,
                        'notes' => 'Saldo awal',
                        'fifo_balance_qty' => $openingQty,
                        'fifo_balance_total' => $openingTotal,
                        'fifo_balance_avg_price' => $openingQty > 0 ? $openingTotal / $openingQty : 0,
                        'fifo_layers' => $batches,
                    ]);
                }
            }

            // Jika sudah ada saldo dari MaterialStockBalance, lewati hitungan transaksi lama
            if ($hasPeriod && $openingInserted && $entryDate && $entryDate->lt($startOfPeriod)) {
                continue;
            }

            if ($hasPeriod && $entryDate && $entryDate->lt($startOfPeriod)) {
                if ($type === 'in') {
                    $qtyIn = (float) ($entry->qty_in ?? 0);
                    $priceIn = (float) ($entry->unit_price_in ?? 0);
                    if ($qtyIn > 0) {
                        $batches[] = ['qty' => $qtyIn, 'price' => $priceIn];
                    }
                } elseif ($type === 'out') {
                    $this->fifoConsume($batches, (float) ($entry->qty_out ?? 0), $entry);
                }
                continue;
            }

            if ($hasPeriod && !$openingInserted) {
                $openingInserted = true;
                $openingQty = 0.0;
                $openingTotal = 0.0;
                foreach ($batches as $batch) {
                    $openingQty += $batch['qty'];
                    $openingTotal += $batch['qty'] * $batch['price'];
                }
                $openingAvg = $openingQty > 0 ? $openingTotal / $openingQty : 0.0;
                $processedEntries->push((object) [
                    'date' => $startOfPeriod,
                    'display_date' => null,
                    'type' => 'opening',
                    'qty_in' => 0,
                    'qty_out' => 0,
                    'unit_price_in' => null,
                    'unit_price_out' => null,
                    'notes' => 'Saldo awal',
                    'fifo_balance_qty' => $openingQty,
                    'fifo_balance_total' => $openingTotal,
                    'fifo_balance_avg_price' => $openingAvg,
                    'fifo_layers' => array_values($batches),
                ]);
            }

            if ($type === 'in') {
                $qtyIn = (float) ($entry->qty_in ?? 0);
                $priceIn = (float) ($entry->unit_price_in ?? 0);
                if ($qtyIn > 0) {
                    $batches[] = ['qty' => $qtyIn, 'price' => $priceIn];
                }
            } elseif ($type === 'out') {
                $this->fifoConsume($batches, (float) ($entry->qty_out ?? 0), $entry);
            }

            $balanceQty = 0.0;
            $balanceTotal = 0.0;
            foreach ($batches as $batch) {
                $balanceQty += $batch['qty'];
                $balanceTotal += $batch['qty'] * $batch['price'];
            }
            $balanceAvg = $balanceQty > 0 ? $balanceTotal / $balanceQty : 0.0;
            $entry->fifo_balance_qty = $balanceQty;
            $entry->fifo_balance_total = $balanceTotal;
            $entry->fifo_balance_avg_price = $balanceAvg;
            $entry->fifo_layers = array_values($batches);

            if (!$hasPeriod || ($entryDate && $entryDate->betweenIncluded($startOfPeriod, $endOfPeriod))) {
                $processedEntries->push($entry);
            }
        }

        if ($hasPeriod && !$openingInserted) {
            $openingQty = 0.0;
            $openingTotal = 0.0;
            foreach ($batches as $batch) {
                $openingQty += $batch['qty'];
                $openingTotal += $batch['qty'] * $batch['price'];
            }
            $openingAvg = $openingQty > 0 ? $openingTotal / $openingQty : 0.0;
            $processedEntries->push((object) [
                'date' => $startOfPeriod,
                'display_date' => null,
                'type' => 'opening',
                'qty_in' => 0,
                'qty_out' => 0,
                'unit_price_in' => null,
                'unit_price_out' => null,
                'notes' => 'Saldo awal',
                'fifo_balance_qty' => $openingQty,
                'fifo_balance_total' => $openingTotal,
                'fifo_balance_avg_price' => $openingAvg,
                'fifo_layers' => array_values($batches),
            ]);
        }

        return $processedEntries->values();
    }

    /**
     * Konsumsi qty dari batches FIFO; set unit_price_out pada entry (rata-rata tertimbang yang dikonsumsi).
     */
    private function fifoConsume(array &$batches, float $qtyNeeded, object $entry): void
    {
        $remaining = $qtyNeeded;
        $costConsumed = 0.0;
        $idx = 0;
        while ($remaining > 0 && $idx < count($batches)) {
            $take = min($remaining, $batches[$idx]['qty']);
            if ($take > 0) {
                $costConsumed += $take * $batches[$idx]['price'];
                $batches[$idx]['qty'] -= $take;
                $remaining -= $take;
            }
            if ($batches[$idx]['qty'] <= 0) {
                array_splice($batches, $idx, 1);
            } else {
                $idx++;
            }
        }
        $qtyOut = $qtyNeeded - $remaining;
        $entry->unit_price_out = $qtyOut > 0 ? $costConsumed / $qtyOut : 0.0;
    }

    /**
     * Terapkan moving average: hitung saldo dan harga rata-rata per baris, set unit_price_out untuk keluar.
     */
    private function applyAverageToEntries(\Illuminate\Support\Collection $entries, bool $hasPeriod, $startOfPeriod, $endOfPeriod): \Illuminate\Support\Collection
    {
        $balanceQty = 0.0;
        $balanceTotal = 0.0;
        $processedEntries = collect();
        $openingInserted = false;

        foreach ($entries as $entry) {
            $type = $entry->type ?? null;
            $entryDate = $entry->date ?? null;
            if (!$entryDate instanceof Carbon && $entryDate !== null) {
                $entryDate = Carbon::parse($entryDate);
            }

            if ($hasPeriod && $entryDate && $entryDate->lt($startOfPeriod)) {
                if ($type === 'in') {
                    $qtyIn = (float) ($entry->qty_in ?? 0);
                    $priceIn = (float) ($entry->unit_price_in ?? 0);
                    $balanceTotal += $qtyIn * $priceIn;
                    $balanceQty += $qtyIn;
                } elseif ($type === 'out') {
                    $qtyOut = (float) ($entry->qty_out ?? 0);
                    $avg = $balanceQty > 0 ? $balanceTotal / $balanceQty : 0.0;
                    $balanceTotal -= $qtyOut * $avg;
                    $balanceQty -= $qtyOut;
                }
                continue;
            }

            if ($hasPeriod && !$openingInserted) {
                $openingInserted = true;
                $avgPrice = $balanceQty > 0 ? $balanceTotal / $balanceQty : 0.0;
                $processedEntries->push((object) [
                    'date' => $startOfPeriod,
                    'display_date' => null,
                    'type' => 'opening',
                    'qty_in' => 0,
                    'qty_out' => 0,
                    'unit_price_in' => null,
                    'unit_price_out' => null,
                    'notes' => 'Saldo awal',
                    'avg_balance_qty' => $balanceQty,
                    'avg_balance_total' => $balanceTotal,
                    'avg_balance_avg_price' => $avgPrice,
                ]);
            }

            if ($type === 'in') {
                $qtyIn = (float) ($entry->qty_in ?? 0);
                $priceIn = (float) ($entry->unit_price_in ?? 0);
                $balanceTotal += $qtyIn * $priceIn;
                $balanceQty += $qtyIn;
                $entry->unit_price_out = $balanceQty > 0 ? $balanceTotal / $balanceQty : 0.0;
            } elseif ($type === 'out') {
                $qtyOut = (float) ($entry->qty_out ?? 0);
                $avg = $balanceQty > 0 ? $balanceTotal / $balanceQty : 0.0;
                $entry->unit_price_out = $avg;
                $balanceTotal -= $qtyOut * $avg;
                $balanceQty -= $qtyOut;
            }

            $avgPrice = $balanceQty > 0 ? $balanceTotal / $balanceQty : 0.0;
            $entry->avg_balance_qty = $balanceQty;
            $entry->avg_balance_total = $balanceTotal;
            $entry->avg_balance_avg_price = $avgPrice;

            if (!$hasPeriod || ($entryDate && $entryDate->betweenIncluded($startOfPeriod, $endOfPeriod))) {
                $processedEntries->push($entry);
            }
        }

        if ($hasPeriod && !$openingInserted) {
            $avgPrice = $balanceQty > 0 ? $balanceTotal / $balanceQty : 0.0;
            $processedEntries->push((object) [
                'date' => $startOfPeriod,
                'display_date' => null,
                'type' => 'opening',
                'qty_in' => 0,
                'qty_out' => 0,
                'unit_price_in' => null,
                'unit_price_out' => null,
                'notes' => 'Saldo awal',
                'avg_balance_qty' => $balanceQty,
                'avg_balance_total' => $balanceTotal,
                'avg_balance_avg_price' => $avgPrice,
            ]);
        }

        return $processedEntries->values();
    }

    private function getRecipeUnitForAuxiliaryMaterial(AuxiliaryMaterial $auxiliaryMaterial): ?string
    {
        $pivot = \App\Models\Product::whereHas('auxiliaryMaterials', function ($q) use ($auxiliaryMaterial) {
            $q->where('auxiliary_materials.id', $auxiliaryMaterial->id)->whereNotNull('product_auxiliary_materials.unit');
        })
            ->with([
                'auxiliaryMaterials' => function ($q) use ($auxiliaryMaterial) {
                    $q->where('auxiliary_materials.id', $auxiliaryMaterial->id);
                }
            ])
            ->first();

        if (!$pivot) {
            return null;
        }

        $pivotRow = $pivot->auxiliaryMaterials->first();
        return $pivotRow?->pivot?->unit ?? null;
    }
}