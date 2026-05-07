<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\RawMaterialUnitConversion;
use App\Models\Unit;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RawMaterialController extends Controller
{
    public function index()
    {
        $rawMaterials = RawMaterial::with(['supplier', 'unitConversions', 'chartOfAccount'])->paginate(15);
        $unitMap = Unit::pluck('name', 'code'); // [code => name]

        // Calculate current stock and average price for each material
        $rawMaterials->getCollection()->transform(function ($material) {
            $material->calculated_stock = $material->getCurrentStock();
            // Gunakan harga average dari transaksi (sama dengan kartu stok)
            $material->calculated_price = $material->getAverageUnitPrice();
            return $material;
        });

        return view('raw-materials.index', compact('rawMaterials', 'unitMap'));
    }

    /**
     * API: kembalikan kode berikutnya untuk bahan baku (untuk tombol Refresh Kode).
     */
    public function generateCode()
    {
        return response()->json(['code' => RawMaterial::generateCode()]);
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $units = Unit::where('is_active', true)->orderBy('code')->get();
        $nextCode = RawMaterial::generateCode();
        return view('raw-materials.create', compact('suppliers', 'units', 'nextCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|unique:raw_materials,code',
            'name' => 'required',
            'description' => 'nullable',
            'unit' => 'required',
            'recipe_unit' => 'nullable|string',
            'recipe_conversion_factor' => 'nullable|numeric|min:0.000001',
            'price_per_unit' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'min_stock_unit' => 'nullable|string',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = RawMaterial::generateCode();
        }

        // Gunakan DB transaction untuk memastikan konsistensi data
        $rawMaterial = DB::transaction(function () use ($validated, $request) {
            // 1. Simpan raw material (COA akan auto-generate di model boot method)
            // Juga simpan master_price_per_unit agar tidak ikut berubah saat pembelian
            $validated['master_price_per_unit'] = $validated['price_per_unit'] ?? 0;
            $rawMaterial = RawMaterial::create($validated);

            // 2. Simpan konversi multi-satuan jika ada
            $conversions = $request->input('conversions', []);
            if (is_array($conversions) && $rawMaterial->unit) {
                foreach ($conversions as $conv) {
                    $toUnit = $conv['to_unit'] ?? null;
                    $factor = isset($conv['factor']) && is_numeric($conv['factor']) ? (float) $conv['factor'] : 1;
                    if ($toUnit && $factor > 0) {
                        RawMaterialUnitConversion::create([
                            'raw_material_id' => $rawMaterial->id,
                            'from_unit' => $rawMaterial->unit,
                            'to_unit' => $toUnit,
                            'factor' => $factor,
                        ]);
                    }
                }
            }

            return $rawMaterial;
        });

        $message = 'Bahan baku berhasil ditambahkan';

        // Tambahkan info COA jika berhasil dibuat
        $coaExpense = ChartOfAccount::find($rawMaterial->chart_of_account_id);
        $coaInventory = ChartOfAccount::find($rawMaterial->inventory_coa_id);

        $coaInfo = [];
        if ($coaExpense) {
            $coaInfo[] = 'BBB: ' . $coaExpense->code . ' - ' . $coaExpense->account_name;
        }
        if ($coaInventory) {
            $coaInfo[] = 'Persediaan: ' . $coaInventory->code . ' - ' . $coaInventory->account_name;
        }

        if (!empty($coaInfo)) {
            $message .= ' (COA auto-generated: ' . implode(' | ', $coaInfo) . ')';
        }

        return redirect()->route('raw-materials.index')->with('success', $message);
    }

    public function show(RawMaterial $rawMaterial)
    {
        $rawMaterial->load(['supplier', 'chartOfAccount']);
        return view('raw-materials.show', compact('rawMaterial'));
    }

    public function edit(RawMaterial $rawMaterial)
    {
        $rawMaterial->load('unitConversions');
        $suppliers = Supplier::all();
        $units = Unit::where('is_active', true)->orderBy('code')->get();
        return view('raw-materials.edit', compact('rawMaterial', 'suppliers', 'units'));
    }

    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $validated = $request->validate([
            'name' => 'required',
            'description' => 'nullable',
            'unit' => 'required',
            'recipe_unit' => 'nullable|string',
            'recipe_conversion_factor' => 'nullable|numeric|min:0.000001',
            'price_per_unit' => 'required|numeric|min:0',
            'stock' => 'nullable|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        // Hapus stock dari validated agar tidak mengubah current stock
        unset($validated['stock']);

        // Jika satuan dasar berubah, gunakan mekanisme konversi agar stok dan transaksi ikut menyesuaikan
        if ($validated['unit'] !== $rawMaterial->unit) {
            $rawMaterial->convertUnit($validated['unit']);

            // Setelah konversi, kita tidak ingin menimpa unit/price_per_unit hasil hitungan
            unset($validated['unit'], $validated['price_per_unit']);
        }

        // Simpan juga master_price_per_unit (harga tetap dari admin, tidak ikut berubah saat pembelian)
        if (isset($validated['price_per_unit'])) {
            $validated['master_price_per_unit'] = $validated['price_per_unit'];
        }

        $rawMaterial->update($validated);

        // Sync konversi multi-satuan
        $conversions = $request->input('conversions', []);
        $rawMaterial->unitConversions()->delete();
        if (is_array($conversions) && $rawMaterial->unit) {
            foreach ($conversions as $conv) {
                $toUnit = $conv['to_unit'] ?? null;
                $factor = isset($conv['factor']) && is_numeric($conv['factor']) ? (float) $conv['factor'] : 1;
                if ($toUnit && $factor > 0) {
                    RawMaterialUnitConversion::create([
                        'raw_material_id' => $rawMaterial->id,
                        'from_unit' => $rawMaterial->unit,
                        'to_unit' => $toUnit,
                        'factor' => $factor,
                    ]);
                }
            }
        }

        return redirect()->route('raw-materials.index')->with('success', 'Bahan baku berhasil diupdate');
    }

    public function destroy(RawMaterial $rawMaterial)
    {
        // Cek apakah bahan baku sudah dipakai di transaksi lain
        $hasPurchaseItems = \App\Models\PurchaseItem::where('raw_material_id', $rawMaterial->id)->exists();
        $hasUsages = \App\Models\RawMaterialUsage::where('raw_material_id', $rawMaterial->id)->exists();
        $hasReturnItems = \App\Models\PurchaseReturnItem::where('raw_material_id', $rawMaterial->id)->exists();

        if ($hasPurchaseItems || $hasUsages || $hasReturnItems) {
            return redirect()->route('raw-materials.index')
                ->with('error', 'Bahan baku tidak dapat dihapus karena sudah digunakan dalam transaksi (pembelian, retur, pemakaian, atau resep produk).');
        }

        $rawMaterial->delete();

        return redirect()->route('raw-materials.index')->with('success', 'Bahan baku berhasil dihapus');
    }

    public function stockCard(\Illuminate\Http\Request $request, RawMaterial $rawMaterial)
    {
        // Pastikan relasi konversi satuan sudah dimuat
        $rawMaterial->load('unitConversions');

        // Metode perhitungan HPP (default FIFO sesuai permintaan user, bisa override via query)
        $method = $request->query('method', 'fifo');

        // Periode laporan (opsional, berbasis bulan)
        $month = $request->query('month');
        $year = $request->query('year');
        $hasPeriod = $month && $year;
        $startOfPeriod = $hasPeriod ? Carbon::create((int) $year, (int) $month, 1)->startOfDay() : null;
        $endOfPeriod = $hasPeriod ? (clone $startOfPeriod)->endOfMonth() : null;

        // Tentukan satuan tampilan (default: satuan yang digunakan di produk, fallback ke unit dasar)
        $recipeUnit = $this->getRecipeUnitForMaterial($rawMaterial);
        $selectedUnit = $request->query('unit', $recipeUnit ?: $rawMaterial->unit);
        if (!$selectedUnit) {
            $selectedUnit = $rawMaterial->unit; // hard fallback supaya tidak null
        }

        // Tentukan metode perhitungan (sinkron dengan $method)
        $selectedMethod = $method;

        // Hanya satuan yang benar ada di bahan baku ini: satuan dasar + from_unit & to_unit dari konversi
        $unitCodes = collect([$rawMaterial->unit])
            ->merge($rawMaterial->unitConversions->pluck('from_unit'))
            ->merge($rawMaterial->unitConversions->pluck('to_unit'))
            ->filter()
            ->unique()
            ->values();

        // Pastikan satuan yang dipilih ada di daftar satuan bahan baku ini
        if ($unitCodes->isNotEmpty() && !$unitCodes->contains($selectedUnit)) {
            $selectedUnit = $rawMaterial->unit;
        }

        // Ambil nama satuan dari master Unit (hanya yang ada di bahan baku)
        $unitMap = \App\Models\Unit::whereIn('code', $unitCodes)->pluck('name', 'code');

        // Faktor tampilan: dari satuan dasar -> satuan yang dipilih (pakai konversi per-bahan terlebih dulu)
        $displayFactor = $rawMaterial->getMaterialConversionFactor($rawMaterial->unit, $selectedUnit);

        // Fallback: jika displayFactor masih 1 tapi selectedUnit bukan satuan dasar,
        // cari faktor dari purchase_items yang sudah ada (data lama)
        if ($displayFactor == 1 && $selectedUnit !== $rawMaterial->unit) {
            // Case 1: purchase_item.unit = selectedUnit (dibeli dalam satuan alt)
            $itemWithFactor = \App\Models\PurchaseItem::where('raw_material_id', $rawMaterial->id)
                ->where('unit', $selectedUnit)
                ->where('conversion_factor', '>', 0)
                ->orderByDesc('created_at')
                ->first();

            // Case 2: purchase_item.unit = baseUnit dan ada unitConversion ke selectedUnit
            // conversion_factor pada item menyimpan faktor konversi base→alt
            if (!$itemWithFactor) {
                $conv = $rawMaterial->unitConversions
                    ->where('from_unit', $rawMaterial->unit)
                    ->where('to_unit', $selectedUnit)
                    ->first();
                if ($conv) {
                    // Ada record konversi, cari purchase_item base unit dengan conversion_factor > 1
                    $itemWithFactor = \App\Models\PurchaseItem::where('raw_material_id', $rawMaterial->id)
                        ->where('unit', $rawMaterial->unit)
                        ->where('conversion_factor', '>', 1)
                        ->orderByDesc('created_at')
                        ->first();
                }
            }

            if ($itemWithFactor) {
                $displayFactor = (float) $itemWithFactor->conversion_factor;
                // Simpan ke unitConversions agar selanjutnya tidak perlu fallback
                $rawMaterial->unitConversions()->updateOrCreate(
                    ['from_unit' => $rawMaterial->unit, 'to_unit' => $selectedUnit],
                    ['factor' => $displayFactor]
                );
            }
        }

        // Mutasi MASUK: dari pembelian (draft, approved, received)
        $purchaseItems = \App\Models\PurchaseItem::with('purchase')
            ->where('raw_material_id', $rawMaterial->id)
            ->whereHas('purchase', function ($q) {
                $q->whereIn('status', ['draft', 'approved', 'received']);
            })
            ->get()
            ->map(function ($item) use ($rawMaterial) {
                $purchase = $item->purchase;

                // Purchase items are now stored in base unit quantities with base unit prices
                $qtyInBase = (float) ($item->quantity ?? 0);
                $unitPriceBase = (float) ($item->unit_price ?? 0);

                // Hitung harga efektif (DPP setelah diskon) untuk kartu stok
                // Kartu stok mencatat nilai persediaan = DPP setelah diskon (bukan harga termasuk PPN, bukan harga kotor)
                $ppnRate = (float) ($purchase?->ppn_rate ?? 0);
                $discountRate = (float) ($purchase?->discount_rate ?? 0);
                $taxType = $item->tax_type ?? 'after_tax';

                // Langkah 1: Dapatkan DPP per unit (hilangkan PPN jika after_tax)
                if ($taxType === 'after_tax' && $ppnRate > 0) {
                    $dppPerUnit = $unitPriceBase / (1 + ($ppnRate / 100));
                } else {
                    // before_tax: harga sudah merupakan DPP
                    $dppPerUnit = $unitPriceBase;
                }

                // Langkah 2: Terapkan diskon ke DPP
                if ($discountRate > 0) {
                    $effectivePrice = $dppPerUnit * (1 - ($discountRate / 100));
                } else {
                    $effectivePrice = $dppPerUnit;
                }

                // Gunakan created_at sebagai penentu urutan kronologis penuh (tanggal + jam)
                $sortDate = $purchase?->created_at ?? $purchase?->purchase_date;

                return (object) [
                    'date' => $sortDate,
                    'display_date' => $purchase?->purchase_date,
                    'type' => 'in',
                    'qty_in' => $qtyInBase,
                    'qty_out' => 0,
                    'unit_price_in' => $effectivePrice,
                    'unit_price_out' => null,
                    'notes' => $purchase
                        ? ('PO ' . $purchase->purchase_number)
                        : null,
                ];
            });

        // Mutasi KELUAR: dari pemakaian bahan baku (RawMaterialUsage)
        $usages = \App\Models\RawMaterialUsage::with('transaction')
            ->where('raw_material_id', $rawMaterial->id)
            ->get()
            ->map(function ($usage) use ($rawMaterial) {
                $trx = $usage->transaction;
                $fromUnit = $usage->unit ?: $rawMaterial->unit;
                $toUnit = $rawMaterial->unit;
                $factor = $fromUnit && $toUnit ? $rawMaterial->getMaterialConversionFactor($fromUnit, $toUnit) : 1;
                $qtyOutBase = ($usage->quantity_used ?? 0) * $factor;
                // Harga keluar: gunakan unit_price di usage jika ada, diasumsikan sudah per satuan dasar
                $unitPriceOut = (float) ($usage->unit_price ?? 0);
                // created_at usage sebagai penentu urutan kronologis
                $sortDate = $usage->created_at ?? $trx?->transaction_date;

                return (object) [
                    'date' => $sortDate,
                    'display_date' => $trx?->transaction_date ?? $usage->created_at,
                    'type' => 'out',
                    'qty_in' => 0,
                    'qty_out' => $qtyOutBase,
                    'unit_price_in' => null,
                    'unit_price_out' => $unitPriceOut,
                    'notes' => $trx->notes ?? 'Pemakaian bahan baku',
                ];
            });

        // Mutasi KELUAR: dari retur pembelian yang sudah completed
        $purchaseReturns = \App\Models\PurchaseReturnItem::with(['purchaseReturn', 'purchaseReturn.purchase', 'purchaseItem', 'purchaseItem.purchase'])
            ->where('raw_material_id', $rawMaterial->id)
            ->whereHas('purchaseReturn', function ($q) {
                $q->where('status', 'completed');
            })
            ->get()
            ->map(function ($returnItem) use ($rawMaterial) {
                $return = $returnItem->purchaseReturn;
                $purchaseItem = $returnItem->purchaseItem;

                // Return quantities are stored in the unit they were returned in
                $fromUnit = $returnItem->unit ?: ($purchaseItem?->unit ?: $rawMaterial->unit);
                $toUnit = $rawMaterial->unit;
                $factor = $fromUnit && $toUnit ? $rawMaterial->getMaterialConversionFactor($fromUnit, $toUnit) : 1;
                $qtyOutBase = ($returnItem->quantity ?? 0) * $factor;

                // Harga retur: ambil dari harga beli item terkait, hitung DPP setelah diskon
                $relatedPurchase = $purchaseItem?->purchase;
                $rawPriceRet = (float) ($purchaseItem?->unit_price ?? 0);
                $ppnRateRet = (float) ($relatedPurchase?->ppn_rate ?? 0);
                $discountRateRet = (float) ($relatedPurchase?->discount_rate ?? 0);
                $taxTypeRet = $purchaseItem?->tax_type ?? 'after_tax';

                $dppRet = ($taxTypeRet === 'after_tax' && $ppnRateRet > 0)
                    ? $rawPriceRet / (1 + ($ppnRateRet / 100))
                    : $rawPriceRet;

                $unitPriceBase = $discountRateRet > 0
                    ? $dppRet * (1 - ($discountRateRet / 100))
                    : $dppRet;

                // created_at retur sebagai penentu urutan kronologis
                $sortDate = $return?->created_at ?? $return?->return_date;

                return (object) [
                    'date' => $sortDate,
                    'display_date' => $return?->return_date,
                    'type' => 'out',
                    'qty_in' => 0,
                    'qty_out' => $qtyOutBase,
                    'unit_price_in' => null,
                    'unit_price_out' => $unitPriceBase,
                    'notes' => $return
                        ? ('Retur PO ' . optional($return->purchase)->purchase_number)
                        : 'Retur pembelian',
                ];
            });

        // Gabungkan dan urutkan berdasarkan tanggal (dalam satuan dasar)
        $entries = collect()
            ->concat($purchaseItems)
            ->concat($usages)
            ->concat($purchaseReturns)
            ->sortBy('date')
            ->values()
            ->filter(function ($entry) {
                return !empty($entry->date) && !empty($entry->notes);
            });

        // 1. Get Opening Balance from MaterialStockBalance if available
        $openingQty = 0.0;
        $openingTotal = 0.0;
        $batches = [];
        $balance = null;

        if ($hasPeriod) {
            $balance = \App\Models\MaterialStockBalance::where('material_type', 'RawMaterial')
                ->where('material_id', $rawMaterial->id)
                ->where('is_posted', true)
                ->where('period', $startOfPeriod->format('Y-m-d'))
                ->first();

            if ($balance) {
                $openingQty = (float) $balance->beginning_qty;
                $openingTotal = (float) $balance->beginning_value;

                // PENTING: Load FIFO layers dari periode sebelumnya
                // Ini akan menampilkan multiple baris di kolom SALDO untuk saldo awal
                if ($balance->beginning_fifo_layers) {
                    $batches = json_decode($balance->beginning_fifo_layers, true) ?: [];
                } elseif ($openingQty > 0) {
                    // Fallback: create single batch if no layers stored
                    $batches[] = [
                        'qty' => $openingQty,
                        'price' => $openingQty > 0 ? $openingTotal / $openingQty : 0
                    ];
                }
            }
        }

        $processedEntries = collect();
        $openingInserted = false;

        // Helper to push opening entry
        $pushOpening = function () use (&$processedEntries, &$openingInserted, $startOfPeriod, &$batches, $balance, $rawMaterial, $method) {
            if ($openingInserted)
                return;
            $openingInserted = true;
            $qty = 0.0;
            $total = 0.0;

            if ($balance) {
                $qty = (float) $balance->beginning_qty;
                $total = (float) $balance->beginning_value;
            } else {
                // Tidak ada posting untuk periode ini — jangan tampilkan saldo awal
                return;
            }

            // PENTING: Push SATU entry saja dengan semua batches di fifo_layers
            // View akan render multiple baris di kolom SALDO dari fifo_layers
            // CRITICAL: Buat COPY dari $batches agar tidak berubah saat transaksi baru ditambahkan
            $avg = $qty > 0 ? $total / $qty : 0.0;
            $openingBatches = !empty($batches) ? array_values($batches) : ($qty > 0 ? [['qty' => $qty, 'price' => $avg]] : []);
            
            $processedEntries->push((object) [
                'date' => $startOfPeriod->copy(),
                'display_date' => null,
                'type' => 'opening',
                'qty_in' => 0,
                'qty_out' => 0,
                'unit_price_in' => null,
                'unit_price_out' => null,
                'notes' => 'Saldo awal',
                'fifo_balance_qty' => $qty,
                'fifo_balance_total' => $total,
                'fifo_balance_avg_price' => $avg,
                'fifo_layers' => $openingBatches,
            ]);
        };

        // Jika diminta metode FIFO, hitung ulang harga keluar berdasarkan batch stok masuk (FIFO)
        if ($method === 'fifo') {
            foreach ($entries as $entry) {
                $type = $entry->type ?? null;
                $entryDate = $entry->date ?? null;
                if (!$entryDate instanceof Carbon && $entryDate !== null) {
                    $entryDate = Carbon::parse($entryDate);
                }

                // Jika sudah ada saldo dari MaterialStockBalance, lewati HANYA transaksi SEBELUM periode
                // Transaksi DALAM periode tetap ditampilkan
                if ($hasPeriod && $balance) {
                    if ($entryDate && $entryDate->lt($startOfPeriod)) {
                        // Skip transaksi sebelum periode karena sudah ada di saldo awal
                        continue;
                    }
                } else {
                    // Proses transaksi sebelum periode hanya jika tidak ada record di MaterialStockBalance
                    if ($hasPeriod && $entryDate && $entryDate->lt($startOfPeriod)) {
                        if ($type === 'in') {
                            $qtyIn = (float) ($entry->qty_in ?? 0);
                            $priceIn = (float) ($entry->unit_price_in ?? 0);
                            if ($qtyIn > 0) {
                                $batches[] = ['qty' => $qtyIn, 'price' => $priceIn];
                            }
                        } elseif ($type === 'out') {
                            $qtyOut = (float) ($entry->qty_out ?? 0);
                            if ($qtyOut > 0) {
                                $remaining = $qtyOut;
                                while ($remaining > 0 && !empty($batches)) {
                                    $takeQty = min($remaining, $batches[0]['qty']);
                                    $batches[0]['qty'] -= $takeQty;
                                    $remaining -= $takeQty;
                                    if ($batches[0]['qty'] <= 0.0000001)
                                        array_shift($batches);
                                }
                            }
                        }
                        continue;
                    }
                }

                // Insert opening entry if needed
                if ($hasPeriod && !$openingInserted) {
                    $pushOpening();
                }

                // Proses transaksi dalam periode (atau semua transaksi jika tidak ada periode)
                if ($type === 'in') {
                    $qtyIn = (float) ($entry->qty_in ?? 0);
                    $priceIn = (float) ($entry->unit_price_in ?? 0);
                    if ($qtyIn > 0) {
                        $batches[] = ['qty' => $qtyIn, 'price' => $priceIn];
                    }
                } elseif ($type === 'out') {
                    $qtyOut = (float) ($entry->qty_out ?? 0);
                    if ($qtyOut > 0) {
                        $remaining = $qtyOut;
                        $allocatedQty = 0.0;
                        $allocatedCost = 0.0;
                        while ($remaining > 0 && !empty($batches)) {
                            $batchQty = $batches[0]['qty'];
                            $batchPrice = $batches[0]['price'];
                            if ($batchQty <= 0) {
                                array_shift($batches);
                                continue;
                            }
                            $takeQty = min($remaining, $batchQty);
                            $allocatedQty += $takeQty;
                            $allocatedCost += $takeQty * $batchPrice;
                            $batches[0]['qty'] -= $takeQty;
                            $remaining -= $takeQty;
                            if ($batches[0]['qty'] <= 0.0000001)
                                array_shift($batches);
                        }
                        if ($allocatedQty > 0) {
                            $entry->unit_price_out = $allocatedCost / $allocatedQty;
                        }
                    }
                }

                $balanceQty = 0.0;
                $balanceTotal = 0.0;
                foreach ($batches as $batch) {
                    $balanceQty += $batch['qty'];
                    $balanceTotal += $batch['qty'] * $batch['price'];
                }
                $entry->fifo_balance_qty = $balanceQty;
                $entry->fifo_balance_total = $balanceTotal;
                $entry->fifo_balance_avg_price = $balanceQty > 0 ? $balanceTotal / $balanceQty : 0.0;
                $entry->fifo_layers = array_values($batches);

                if (!$hasPeriod || ($entryDate && $entryDate->betweenIncluded($startOfPeriod, $endOfPeriod))) {
                    $processedEntries->push($entry);
                }
            }

            if ($hasPeriod && !$openingInserted) {
                $pushOpening();
            }

            $entries = $processedEntries->values();
        } else {
            // Average method or no specific method selected
            if ($hasPeriod) {
                // If we don't have a balance, we still need to calculate the batches before period for opening
                if (!$balance) {
                    foreach ($entries as $entry) {
                        $type = $entry->type ?? null;
                        $entryDate = $entry->date ?? null;
                        if (!$entryDate instanceof Carbon && $entryDate !== null) {
                            $entryDate = Carbon::parse($entryDate);
                        }
                        if ($entryDate && $entryDate->lt($startOfPeriod)) {
                            if ($type === 'in') {
                                $qtyIn = (float) ($entry->qty_in ?? 0);
                                $priceIn = (float) ($entry->unit_price_in ?? 0);
                                if ($qtyIn > 0)
                                    $batches[] = ['qty' => $qtyIn, 'price' => $priceIn];
                            } elseif ($type === 'out') {
                                $qtyOut = (float) ($entry->qty_out ?? 0);
                                if ($qtyOut > 0) {
                                    $remaining = $qtyOut;
                                    while ($remaining > 0 && !empty($batches)) {
                                        $takeQty = min($remaining, $batches[0]['qty']);
                                        $batches[0]['qty'] -= $takeQty;
                                        $remaining -= $takeQty;
                                        if ($batches[0]['qty'] <= 0.0000001)
                                            array_shift($batches);
                                    }
                                }
                            }
                        }
                    }
                }

                $pushOpening();

                // Only keep entries within the period
                $entries = $processedEntries->concat(
                    $entries->filter(function ($entry) use ($startOfPeriod, $endOfPeriod) {
                        $date = Carbon::parse($entry->date);
                        return $date->betweenIncluded($startOfPeriod, $endOfPeriod);
                    })
                )->values();
            }
        }

        // Stok saat ini dalam satuan tampilan (dibulatkan tanpa desimal)
        // Gunakan saldo akhir dari perhitungan kartu stok, bukan dari database
        $finalBalance = 0;
        if ($entries->isNotEmpty()) {
            $lastEntry = $entries->last();
            if ($method === 'fifo') {
                // Untuk FIFO, ambil dari fifo_balance_qty
                $finalBalance = $lastEntry->fifo_balance_qty ?? 0;
            } else {
                // Untuk Average, hitung saldo akhir dengan mengakumulasi semua transaksi
                $balanceBase = 0;
                foreach ($entries as $entry) {
                    $qtyIn = $entry->qty_in ?? 0;
                    $qtyOut = $entry->qty_out ?? 0;
                    $balanceBase += ($qtyIn - $qtyOut);
                }
                $finalBalance = $balanceBase;
            }
        }

        $currentStockDisplay = round($finalBalance * $displayFactor);

        // Check if posted
        $isPosted = false;
        if ($hasPeriod) {
            $isPosted = \App\Models\MaterialStockBalance::where('material_type', 'RawMaterial')
                ->where('material_id', $rawMaterial->id)
                ->where('period', $startOfPeriod->format('Y-m-d'))
                ->where('is_posted', true)
                ->exists();
        }

        return view('raw-materials.stock-card', compact(
            'rawMaterial',
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

    public function stockCardPdf(\Illuminate\Http\Request $request, RawMaterial $rawMaterial)
    {
        // Gunakan logic yang sama dengan stockCard method
        $rawMaterial->load('unitConversions');
        $method = $request->query('method', 'fifo');
        $month = $request->query('month');
        $year = $request->query('year');
        $hasPeriod = $month && $year;
        $startOfPeriod = $hasPeriod ? \Carbon\Carbon::create((int) $year, (int) $month, 1)->startOfDay() : null;
        $endOfPeriod = $hasPeriod ? (clone $startOfPeriod)->endOfMonth() : null;

        $recipeUnit = $this->getRecipeUnitForMaterial($rawMaterial);
        $selectedUnit = $request->query('unit', $recipeUnit ?: $rawMaterial->unit);
        if (!$selectedUnit) {
            $selectedUnit = $rawMaterial->unit;
        }

        $selectedMethod = $method;

        $unitCodes = collect([$rawMaterial->unit])
            ->merge($rawMaterial->unitConversions->pluck('from_unit'))
            ->merge($rawMaterial->unitConversions->pluck('to_unit'))
            ->filter()
            ->unique()
            ->values();

        if ($unitCodes->isNotEmpty() && !$unitCodes->contains($selectedUnit)) {
            $selectedUnit = $rawMaterial->unit;
        }

        $unitMap = \App\Models\Unit::whereIn('code', $unitCodes)->pluck('name', 'code');
        $displayFactor = $rawMaterial->getMaterialConversionFactor($rawMaterial->unit, $selectedUnit);

        if ($displayFactor == 1 && $selectedUnit !== $rawMaterial->unit) {
            $itemWithFactor = \App\Models\PurchaseItem::where('raw_material_id', $rawMaterial->id)
                ->where('unit', $selectedUnit)
                ->where('conversion_factor', '>', 0)
                ->orderByDesc('created_at')
                ->first();

            if (!$itemWithFactor) {
                $conv = $rawMaterial->unitConversions
                    ->where('from_unit', $rawMaterial->unit)
                    ->where('to_unit', $selectedUnit)
                    ->first();
                if ($conv) {
                    $itemWithFactor = \App\Models\PurchaseItem::where('raw_material_id', $rawMaterial->id)
                        ->where('unit', $rawMaterial->unit)
                        ->where('conversion_factor', '>', 1)
                        ->orderByDesc('created_at')
                        ->first();
                }
            }

            if ($itemWithFactor) {
                $displayFactor = (float) $itemWithFactor->conversion_factor;
                $rawMaterial->unitConversions()->updateOrCreate(
                    ['from_unit' => $rawMaterial->unit, 'to_unit' => $selectedUnit],
                    ['factor' => $displayFactor]
                );
            }
        }

        // Ambil data transaksi (sama seperti stockCard method)
        $purchaseItems = \App\Models\PurchaseItem::with('purchase')
            ->where('raw_material_id', $rawMaterial->id)
            ->whereHas('purchase', function ($q) {
                $q->whereIn('status', ['draft', 'approved', 'received']);
            })
            ->get()
            ->map(function ($item) use ($rawMaterial) {
                $purchase = $item->purchase;
                $qtyInBase = (float) ($item->quantity ?? 0);
                $unitPriceBase = (float) ($item->unit_price ?? 0);
                $ppnRate = (float) ($purchase?->ppn_rate ?? 0);
                $discountRate = (float) ($purchase?->discount_rate ?? 0);
                $taxType = $item->tax_type ?? 'after_tax';

                if ($taxType === 'after_tax' && $ppnRate > 0) {
                    $dppPerUnit = $unitPriceBase / (1 + ($ppnRate / 100));
                } else {
                    $dppPerUnit = $unitPriceBase;
                }

                if ($discountRate > 0) {
                    $effectivePrice = $dppPerUnit * (1 - ($discountRate / 100));
                } else {
                    $effectivePrice = $dppPerUnit;
                }

                $sortDate = $purchase?->created_at ?? $purchase?->purchase_date;

                return (object) [
                    'date' => $sortDate,
                    'display_date' => $purchase?->purchase_date,
                    'type' => 'in',
                    'qty_in' => $qtyInBase,
                    'qty_out' => 0,
                    'unit_price_in' => $effectivePrice,
                    'unit_price_out' => null,
                    'notes' => $purchase ? ('PO ' . $purchase->purchase_number) : null,
                ];
            });

        $usages = \App\Models\RawMaterialUsage::with('transaction')
            ->where('raw_material_id', $rawMaterial->id)
            ->get()
            ->map(function ($usage) use ($rawMaterial) {
                $trx = $usage->transaction;
                $fromUnit = $usage->unit ?: $rawMaterial->unit;
                $toUnit = $rawMaterial->unit;
                $factor = $fromUnit && $toUnit ? $rawMaterial->getMaterialConversionFactor($fromUnit, $toUnit) : 1;
                $qtyOutBase = ($usage->quantity_used ?? 0) * $factor;
                $unitPriceOut = (float) ($usage->unit_price ?? 0);
                $sortDate = $usage->created_at ?? $trx?->transaction_date;

                return (object) [
                    'date' => $sortDate,
                    'display_date' => $trx?->transaction_date ?? $usage->created_at,
                    'type' => 'out',
                    'qty_in' => 0,
                    'qty_out' => $qtyOutBase,
                    'unit_price_in' => null,
                    'unit_price_out' => $unitPriceOut,
                    'notes' => $trx->notes ?? 'Pemakaian bahan baku',
                ];
            });

        $purchaseReturns = \App\Models\PurchaseReturnItem::with(['purchaseReturn', 'purchaseReturn.purchase', 'purchaseItem', 'purchaseItem.purchase'])
            ->where('raw_material_id', $rawMaterial->id)
            ->whereHas('purchaseReturn', function ($q) {
                $q->where('status', 'completed');
            })
            ->get()
            ->map(function ($returnItem) use ($rawMaterial) {
                $return = $returnItem->purchaseReturn;
                $purchaseItem = $returnItem->purchaseItem;
                $fromUnit = $returnItem->unit ?: ($purchaseItem?->unit ?: $rawMaterial->unit);
                $toUnit = $rawMaterial->unit;
                $factor = $fromUnit && $toUnit ? $rawMaterial->getMaterialConversionFactor($fromUnit, $toUnit) : 1;
                $qtyOutBase = ($returnItem->quantity ?? 0) * $factor;

                $relatedPurchase = $purchaseItem?->purchase;
                $rawPriceRet = (float) ($purchaseItem?->unit_price ?? 0);
                $ppnRateRet = (float) ($relatedPurchase?->ppn_rate ?? 0);
                $discountRateRet = (float) ($relatedPurchase?->discount_rate ?? 0);
                $taxTypeRet = $purchaseItem?->tax_type ?? 'after_tax';

                $dppRet = ($taxTypeRet === 'after_tax' && $ppnRateRet > 0)
                    ? $rawPriceRet / (1 + ($ppnRateRet / 100))
                    : $rawPriceRet;

                $unitPriceBase = $discountRateRet > 0
                    ? $dppRet * (1 - ($discountRateRet / 100))
                    : $dppRet;

                $sortDate = $return?->created_at ?? $return?->return_date;

                return (object) [
                    'date' => $sortDate,
                    'display_date' => $return?->return_date,
                    'type' => 'out',
                    'qty_in' => 0,
                    'qty_out' => $qtyOutBase,
                    'unit_price_in' => null,
                    'unit_price_out' => $unitPriceBase,
                    'notes' => $return ? ('Retur PO ' . optional($return->purchase)->purchase_number) : 'Retur pembelian',
                ];
            });

        $entries = collect()
            ->concat($purchaseItems)
            ->concat($usages)
            ->concat($purchaseReturns)
            ->sortBy('date')
            ->values()
            ->filter(function ($entry) {
                return !empty($entry->date) && !empty($entry->notes);
            });

        // Proses FIFO/Average (simplified untuk PDF)
        $openingQty = 0.0;
        $openingTotal = 0.0;
        $batches = [];
        $balance = null;

        if ($hasPeriod) {
            $balance = \App\Models\MaterialStockBalance::where('material_type', 'RawMaterial')
                ->where('material_id', $rawMaterial->id)
                ->where('is_posted', true)
                ->where('period', $startOfPeriod->format('Y-m-d'))
                ->first();

            if ($balance) {
                $openingQty = (float) $balance->beginning_qty;
                $openingTotal = (float) $balance->beginning_value;

                // PENTING: Load FIFO layers dari periode sebelumnya
                if ($balance->beginning_fifo_layers) {
                    $batches = json_decode($balance->beginning_fifo_layers, true) ?: [];
                } elseif ($openingQty > 0) {
                    $batches[] = [
                        'qty' => $openingQty,
                        'price' => $openingQty > 0 ? $openingTotal / $openingQty : 0
                    ];
                }
            }
        }

        $processedEntries = collect();
        $openingInserted = false;

        $pushOpening = function () use (&$processedEntries, &$openingInserted, $startOfPeriod, &$batches, $balance, $rawMaterial, $method) {
            if ($openingInserted) return;
            $openingInserted = true;
            $qty = 0.0;
            $total = 0.0;

            if ($balance) {
                $qty = (float) $balance->beginning_qty;
                $total = (float) $balance->beginning_value;
            }

            // PENTING: Push SATU entry saja dengan semua batches di fifo_layers
            // View akan render multiple baris di kolom SALDO dari fifo_layers
            $avg = $qty > 0 ? $total / $qty : 0.0;
            $processedEntries->push((object) [
                'date' => $startOfPeriod->copy(),
                'display_date' => null,
                'type' => 'opening',
                'qty_in' => 0,
                'qty_out' => 0,
                'unit_price_in' => null,
                'unit_price_out' => null,
                'notes' => 'Saldo awal',
                'fifo_balance_qty' => $qty,
                'fifo_balance_total' => $total,
                'fifo_balance_avg_price' => $avg,
                'fifo_layers' => !empty($batches) ? $batches : ($qty > 0 ? [['qty' => $qty, 'price' => $avg]] : []),
            ]);
        };

        // Simplified processing for PDF
        if ($method === 'fifo') {
            foreach ($entries as $entry) {
                $type = $entry->type ?? null;
                $entryDate = $entry->date ?? null;
                if (!$entryDate instanceof \Carbon\Carbon && $entryDate !== null) {
                    $entryDate = \Carbon\Carbon::parse($entryDate);
                }

                if ($hasPeriod && $balance) {
                    if ($entryDate && $entryDate->lt($startOfPeriod)) {
                        continue;
                    }
                }

                if ($hasPeriod && !$openingInserted) {
                    $pushOpening();
                }

                if ($type === 'in') {
                    $qtyIn = (float) ($entry->qty_in ?? 0);
                    $priceIn = (float) ($entry->unit_price_in ?? 0);
                    if ($qtyIn > 0) {
                        $batches[] = ['qty' => $qtyIn, 'price' => $priceIn];
                    }
                } elseif ($type === 'out') {
                    $qtyOut = (float) ($entry->qty_out ?? 0);
                    if ($qtyOut > 0) {
                        $remaining = $qtyOut;
                        $allocatedQty = 0.0;
                        $allocatedCost = 0.0;
                        while ($remaining > 0 && !empty($batches)) {
                            $batchQty = $batches[0]['qty'];
                            $batchPrice = $batches[0]['price'];
                            if ($batchQty <= 0) {
                                array_shift($batches);
                                continue;
                            }
                            $takeQty = min($remaining, $batchQty);
                            $allocatedQty += $takeQty;
                            $allocatedCost += $takeQty * $batchPrice;
                            $batches[0]['qty'] -= $takeQty;
                            $remaining -= $takeQty;
                            if ($batches[0]['qty'] <= 0.0000001)
                                array_shift($batches);
                        }
                        if ($allocatedQty > 0) {
                            $entry->unit_price_out = $allocatedCost / $allocatedQty;
                        }
                    }
                }

                $balanceQty = 0.0;
                $balanceTotal = 0.0;
                foreach ($batches as $batch) {
                    $balanceQty += $batch['qty'];
                    $balanceTotal += $batch['qty'] * $batch['price'];
                }
                $entry->fifo_balance_qty = $balanceQty;
                $entry->fifo_balance_total = $balanceTotal;
                $entry->fifo_balance_avg_price = $balanceQty > 0 ? $balanceTotal / $balanceQty : 0.0;
                $entry->fifo_layers = array_values($batches);

                if (!$hasPeriod || ($entryDate && $entryDate->betweenIncluded($startOfPeriod, $endOfPeriod))) {
                    $processedEntries->push($entry);
                }
            }

            if ($hasPeriod && !$openingInserted) {
                $pushOpening();
            }

            $entries = $processedEntries->values();
        } else {
            if ($hasPeriod) {
                $pushOpening();
                $entries = $processedEntries->concat(
                    $entries->filter(function ($entry) use ($startOfPeriod, $endOfPeriod) {
                        $date = \Carbon\Carbon::parse($entry->date);
                        return $date->betweenIncluded($startOfPeriod, $endOfPeriod);
                    })
                )->values();
            }
        }

        $finalBalance = 0;
        if ($entries->isNotEmpty()) {
            $lastEntry = $entries->last();
            if ($method === 'fifo') {
                $finalBalance = $lastEntry->fifo_balance_qty ?? 0;
            } else {
                $balanceBase = 0;
                foreach ($entries as $entry) {
                    $qtyIn = $entry->qty_in ?? 0;
                    $qtyOut = $entry->qty_out ?? 0;
                    $balanceBase += ($qtyIn - $qtyOut);
                }
                $finalBalance = $balanceBase;
            }
        }

        $currentStockDisplay = round($finalBalance * $displayFactor);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('raw-materials.stock-card-pdf', compact(
            'rawMaterial',
            'entries',
            'unitMap',
            'selectedUnit',
            'selectedMethod',
            'displayFactor',
            'currentStockDisplay',
            'method',
            'month',
            'year'
        ));

        return $pdf->stream('kartu-stok-' . $rawMaterial->code . '.pdf');
    }

    public function updateUnit(Request $request, RawMaterial $rawMaterial)
    {
        $validated = $request->validate([
            'unit' => 'required|in:' . implode(',', array_keys(RawMaterial::UNIT_OPTIONS)),
        ]);

        $converted = $rawMaterial->convertUnit($validated['unit']);

        if (!$converted) {
            return redirect()->route('raw-materials.index')->with('error', 'Konversi satuan tidak didukung.');
        }

        return redirect()->route('raw-materials.index')->with('success', 'Satuan dan stok bahan baku berhasil diupdate');
    }

    /**
     * Get the recipe unit used for this material in products
     */
    private function getRecipeUnitForMaterial(RawMaterial $rawMaterial): ?string
    {
        // Table product_materials dropped, return null
        return null;
    }
}
