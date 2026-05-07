<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

use Illuminate\Support\Carbon;
use App\Models\MaterialStockBalance;

use App\Traits\HasCompany;

class AuxiliaryMaterial extends Model
{
    use HasCompany;

    // ... existing ... (I'll use a better approach)

    public $incrementing = false;

    protected $keyType = 'string';

    public const UNIT_OPTIONS = [
        'Kg' => 'Kilogram (Kg)',
        'Gram' => 'Gram (g)',
        'Milligram' => 'Milligram (mg)',
        'Liter' => 'Liter (L)',
        'Pack' => 'Pack',
        'Box' => 'Box',
        'Bottle' => 'Bottle',
        'Can' => 'Can',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            // Auto-generate 2 COA untuk bahan penolong:
            // 1. BOP BP - [Nama] (expense, kode 55XX)
            // 2. Persediaan Bahan Penolong - [Nama] (asset, kode 1107X)
            $expenseCoaId = \App\Services\CoaAutoGenerateService::createCoaForAuxiliaryMaterialExpense($model);
            if ($expenseCoaId) {
                $model->expense_coa_id = $expenseCoaId;
            }

            $inventoryCoaId = \App\Services\CoaAutoGenerateService::createCoaForAuxiliaryMaterialInventory($model);
            if ($inventoryCoaId) {
                $model->chart_of_account_id = $inventoryCoaId;
            }
        });
    }

    protected $fillable = [
        'name',
        'code',
        'description',
        'unit',
        'stock',
        'minimum_stock',
        'price_per_unit',
        'master_price_per_unit',
        'supplier_id',
        'chart_of_account_id',
        'inventory_coa_id',
        'expense_coa_id',
        'company_id',
    ];

    protected $casts = [
        'stock' => 'decimal:4',
        'minimum_stock' => 'decimal:4',
        'price_per_unit' => 'decimal:4',
        'recipe_conversion_factor' => 'decimal:6',
    ];

    /**
     * Generate next auto code for auxiliary material, e.g. BP-0000001
     * Urutkan berdasarkan code (bukan id) karena id pakai UUID sehingga orderByDesc('id') tidak sesuai urutan kode.
     */
    public static function generateCode(): string
    {
        $prefix = 'BP-';

        // Gunakan angka random 7 digit sesuai permintaan user untuk menghindari tabrakan data (concurrency)
        $randomNumber = mt_rand(1000000, 9999999);
        $code = $prefix . $randomNumber;

        // Pastikan unik untuk PERUSAHAAN INI (mengikuti scope)
        while (static::where('code', $code)->exists()) {
            $randomNumber = mt_rand(1000000, 9999999);
            $code = $prefix . $randomNumber;
        }

        return $code;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function expenseCoa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'expense_coa_id');
    }

    public function unitConversions(): HasMany
    {
        return $this->hasMany(AuxiliaryMaterialUnitConversion::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_auxiliary_materials')
            ->withPivot('quantity_needed', 'unit')
            ->withTimestamps();
    }

    /**
     * Kurangi stok bahan penolong
     */
    public function reduceStock(float $quantity): bool
    {
        if ($this->stock < $quantity) {
            return false;
        }

        $this->stock -= $quantity;
        $this->save();

        return true;
    }

    /**
     * Tambah stok bahan penolong
     */
    public function addStock(float $quantity): void
    {
        $this->stock += $quantity;
        $this->save();
    }

    /**
     * Harga rata-rata (moving average) dari semua pembelian yang diterima — sama dengan kartu stok average.
     */
    public function getAverageUnitPrice(string $targetUnit = null): float
    {
        $targetUnit = $targetUnit ?? $this->unit;

        // Ambil langsung dari saldo terakhir kartu stok (metode Average)

        // Ambil semua transaksi masuk (purchase)
        $purchaseItems = \App\Models\PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.auxiliary_material_id', $this->id)
            ->whereIn('purchases.status', ['draft', 'approved', 'received'])
            ->select('purchase_items.*', 'purchases.ppn_rate as purchase_ppn_rate', 'purchases.discount_rate as purchase_discount_rate', 'purchases.created_at as purchase_created_at')
            ->orderBy('purchases.created_at')
            ->get();

        // Hitung saldo dengan metode Average
        $balanceQty = 0.0;
        $balanceTotal = 0.0;
        $averagePrice = 0.0;

        foreach ($purchaseItems as $item) {
            $qtyInBase = (float) ($item->quantity ?? 0);
            $unitPriceBase = (float) ($item->unit_price ?? 0);

            $ppnRate = (float) ($item->purchase_ppn_rate ?? 0);
            $discRate = (float) ($item->purchase_discount_rate ?? 0);
            $taxType = $item->tax_type ?? 'after_tax';

            // DPP setelah diskon
            $dpp = ($taxType === 'after_tax' && $ppnRate > 0) ? $unitPriceBase / (1 + $ppnRate / 100) : $unitPriceBase;
            $effectivePrice = $discRate > 0 ? $dpp * (1 - $discRate / 100) : $dpp;

            // Update saldo
            $totalIn = $qtyInBase * $effectivePrice;
            $balanceTotal += $totalIn;
            $balanceQty += $qtyInBase;

            if ($balanceQty > 0) {
                $averagePrice = $balanceTotal / $balanceQty;
            }
        }

        // Untuk auxiliary material, transaksi keluar dari job order
        // Tapi untuk simplicity, kita ambil harga average dari saldo masuk saja
        // Karena transaksi keluar tidak mengubah average price

        $avgPriceBase = $balanceQty > 0 ? $balanceTotal / $balanceQty : $averagePrice;

        // Konversi ke targetUnit jika perlu
        if ($targetUnit && $targetUnit !== $this->unit) {
            $factor = $this->getMaterialConversionFactor($this->unit, $targetUnit) ?: 1;
            return $avgPriceBase / $factor;
        }

        return $avgPriceBase;
    }

    /**
     * Hitung stok saat ini dari akumulasi transaksi (sama seperti kartu stok).
     */
    public function getCurrentStock(): float
    {
        // Ambil conversion_factor dari purchase items (lebih akurat dari unit_conversions table)
        $sampleItem = \App\Models\PurchaseItem::where('auxiliary_material_id', $this->id)
            ->whereNotNull('conversion_factor')
            ->where('conversion_factor', '>', 0)
            ->first();
        $purchaseConvFactor = $sampleItem ? (float) $sampleItem->conversion_factor : 1.0;

        // MASUK: dari pembelian — qty sudah dalam satuan dasar (unit = base unit)
        $purchaseQty = \App\Models\PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.auxiliary_material_id', $this->id)
            ->whereIn('purchases.status', ['draft', 'approved', 'received'])
            ->select('purchase_items.*')
            ->get()
            ->sum(function ($item) {
                // qty disimpan dalam satuan dasar (base unit)
                return (float) ($item->quantity ?? 0);
            });

        // KELUAR: pemakaian dari job order (in_progress / completed) via BOM
        $usages = \App\Models\JobOrderDetail::with(['product'])
            ->whereHas('jobOrder', fn($q) => $q->whereIn('status', ['in_progress', 'completed']))
            ->get()
            ->sum(function ($detail) use ($purchaseConvFactor) {
                $bom = \App\Models\BillOfMaterial::with('auxiliaries')
                    ->where('product_id', $detail->product_id)->first();
                if (!$bom)
                    return 0;
                $total = 0;
                foreach ($bom->auxiliaries as $bomAux) {
                    if ($bomAux->auxiliary_material_id !== $this->id)
                        continue;
                    $qtyPerUnit = (float) ($bomAux->quantity ?? 0);
                    $recipeUnit = $bomAux->unit ?: $this->unit;
                    if ($recipeUnit !== $this->unit && $purchaseConvFactor > 0) {
                        $total += ($qtyPerUnit / $purchaseConvFactor) * (float) ($detail->quantity ?? 0);
                    } else {
                        $total += $qtyPerUnit * (float) ($detail->quantity ?? 0);
                    }
                }
                return $total;
            });

        // KELUAR: retur pembelian
        $returns = \App\Models\PurchaseReturnItem::where('auxiliary_material_id', $this->id)
            ->whereHas('purchaseReturn', fn($q) => $q->where('status', 'completed'))
            ->get()
            ->sum(function ($item) {
                return (float) ($item->quantity ?? 0);
            });

        return max(0, $purchaseQty - $usages - $returns);
    }

    /**
     * Harga FIFO batch terdepan (unit dasar), mirip RawMaterial::getCurrentFifoUnitPrice()
     */
    public function getCurrentFifoUnitPrice(string $targetUnit = null): float
    {
        $targetUnit = $targetUnit ?? $this->unit;

        // Bangun antrian batch FIFO dari pembelian (urut terlama dulu)
        $purchaseItems = \App\Models\PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.auxiliary_material_id', $this->id)
            ->where('purchases.status', 'received')
            ->orderBy('purchases.purchase_date', 'asc')
            ->orderBy('purchase_items.created_at', 'asc')
            ->select('purchase_items.*')
            ->get();

        $batches = [];
        foreach ($purchaseItems as $item) {
            // Data di purchase_items sudah dalam bentuk base_unit
            $qty = (float) ($item->quantity ?? 0);
            $price = (float) ($item->unit_price ?? 0);

            if ($qty > 0) {
                $batches[] = ['qty' => $qty, 'price' => $price];
            }
        }

        // Kurangi batch dengan pemakaian dari job order (in_progress / completed)
        $sampleItem = \App\Models\PurchaseItem::where('auxiliary_material_id', $this->id)
            ->whereNotNull('conversion_factor')->where('conversion_factor', '>', 0)->first();
        $purchaseConvFactor = $sampleItem ? (float) $sampleItem->conversion_factor : 1.0;

        $usageDetails = \App\Models\JobOrderDetail::with(['jobOrder', 'product'])
            ->whereHas('jobOrder', fn($q) => $q->whereIn('status', ['in_progress', 'completed']))
            ->get();

        foreach ($usageDetails as $detail) {
            $bom = \App\Models\BillOfMaterial::with('auxiliaries')
                ->where('product_id', $detail->product_id)->first();
            if (!$bom)
                continue;
            foreach ($bom->auxiliaries as $bomAux) {
                if ($bomAux->auxiliary_material_id !== $this->id)
                    continue;
                $qtyPerUnit = (float) ($bomAux->quantity ?? 0);
                $recipeUnit = $bomAux->unit ?: $this->unit;
                if ($recipeUnit !== $this->unit && $purchaseConvFactor > 0) {
                    $remaining = ($qtyPerUnit / $purchaseConvFactor) * (float) ($detail->quantity ?? 0);
                } else {
                    $remaining = $qtyPerUnit * (float) ($detail->quantity ?? 0);
                }

                while ($remaining > 0 && !empty($batches)) {
                    if ($batches[0]['qty'] <= $remaining) {
                        $remaining -= $batches[0]['qty'];
                        array_shift($batches);
                    } else {
                        $batches[0]['qty'] -= $remaining;
                        $remaining = 0;
                    }
                }
            }
        }

        if (empty($batches)) {
            $price = (float) ($this->price_per_unit ?? 0);
        } else {
            $price = $batches[0]['price'];
        }

        if ($targetUnit !== $this->unit) {
            $factor = $purchaseConvFactor > 0 ? $purchaseConvFactor : 1;
            $price = $price / $factor;
        }

        return $price;
    }

    /**
     * Hitung harga FIFO untuk quantity tertentu
     */
    public function getFifoPrice(float $quantity, string $unit = null): float
    {
        $targetUnit = $unit ?? $this->unit;
        $remainingQty = $quantity;
        $totalCost = 0.0;

        // Ambil semua pembelian yang sudah diterima, urut dari yang terlama
        $purchaseItems = \App\Models\PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.auxiliary_material_id', $this->id)
            ->where('purchases.status', 'received')
            ->orderBy('purchases.purchase_date', 'asc')
            ->orderBy('purchase_items.created_at', 'asc')
            ->select('purchase_items.*')
            ->get();

        foreach ($purchaseItems as $item) {
            if ($remainingQty <= 0)
                break;

            // Konversi quantity ke satuan dasar material
            $fromUnit = $item->unit ?: $this->unit;
            $toUnit = $this->unit;

            // Data di purchase_items sudah dalam bentuk base_unit
            $availableQty = (float) ($item->quantity ?? 0);

            // Ambil dari batch ini
            $usedQty = min($remainingQty, $availableQty);
            if ($usedQty <= 0)
                continue;

            // Hitung harga per unit dasar (sudah base unit)
            $unitPriceBase = (float) ($item->unit_price ?? 0);

            // Tambah ke total cost
            $totalCost += $usedQty * $unitPriceBase;
            $remainingQty -= $usedQty;
        }

        // Jika quantity tidak cukup dari pembelian yang ada, gunakan harga current
        if ($remainingQty > 0) {
            $currentPrice = (float) ($this->price_per_unit ?? 0);

            // Konversi ke target unit
            if ($targetUnit !== $this->unit) {
                $convertFactor = $this->getMaterialConversionFactor($this->unit, $targetUnit);
                if ($convertFactor > 0) {
                    $currentPrice = $currentPrice / $convertFactor;
                }
            }

            $totalCost += $remainingQty * $currentPrice;
        }

        // Hitung harga rata-rata per target unit
        $avgPrice = $quantity > 0 ? $totalCost / $quantity : 0;

        // Konversi ke target unit
        if ($targetUnit !== $this->unit) {
            $convertFactor = $this->getMaterialConversionFactor($this->unit, $targetUnit);
            if ($convertFactor > 0) {
                $avgPrice = $avgPrice / $convertFactor;
            }
        }

        return $avgPrice;
    }

    /**
     * Konversi satuan bahan penolong
     * Mengubah unit dan menyesuaikan nilai stock dan price_per_unit
     */
    public function convertUnit(string $newUnit): bool
    {
        if ($this->unit === $newUnit) {
            return true;
        }

        // Cek di tabel konversi khusus per-bahan
        $conversion = $this->unitConversions()
            ->where('from_unit', $this->unit)
            ->where('to_unit', $newUnit)
            ->first();

        if ($conversion) {
            $factor = (float) $conversion->factor;
            if ($factor > 0) {
                // 1 oldUnit = factor newUnit
                $this->stock = $this->stock * $factor;
                $this->price_per_unit = $this->price_per_unit / $factor;
                $this->unit = $newUnit;
                $this->save();
                return true;
            }
        }

        // Fallback ke konversi umum
        $genericFactor = self::getConversionFactor($this->unit, $newUnit);
        if ($genericFactor !== null) {
            $this->stock = $this->stock * $genericFactor;
            $this->price_per_unit = $this->price_per_unit / $genericFactor;
            $this->unit = $newUnit;
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Konversi satuan umum (fallback)
     */
    public static function getConversionFactor(string $fromUnit, string $toUnit): ?float
    {
        $fromUnit = strtolower($fromUnit);
        $toUnit = strtolower($toUnit);

        if ($fromUnit === $toUnit) {
            return 1.0;
        }

        // Konversi ke gram sebagai satuan dasar
        $toGram = [
            'kg' => 1000,
            'kilogram' => 1000,
            'gram' => 1,
            'g' => 1,
            'milligram' => 0.001,
            'mg' => 0.001,
            'liter' => 1000, // catatan kami: Asumsi 1 liter = 1000 gram (untuk cairan dengan densitas air)
            'l' => 1000,
            'mililiter' => 1,
            'milliliter' => 1,
            'ml' => 1,
        ];

        if (!isset($toGram[$fromUnit]) || !isset($toGram[$toUnit])) {
            return null;
        }

        // catatan kami: Konversi: dari unit -> gram -> ke unit baru
        return $toGram[$fromUnit] / $toGram[$toUnit];
    }

    /**
     * Faktor konversi per-bahan.
     * Prioritaskan konversi dari pembelian terakhir, fallback ke tabel konversi
     */
    public function getMaterialConversionFactor(string $fromUnit, string $toUnit): float
    {
        // Jika tidak perlu konversi
        if (strtolower($fromUnit) === strtolower($toUnit)) {
            return 1.0;
        }

        // 1) Cek konversi dari pembelian terakhir (prioritas utama)
        if ($fromUnit === $this->unit || $toUnit === $this->unit) {
            $baseUnit = $this->unit;
            $targetUnit = ($fromUnit === $this->unit) ? $toUnit : $fromUnit;

            $latestPurchaseItems = \App\Models\PurchaseItem::with('purchase')
                ->where('auxiliary_material_id', $this->id)
                ->whereHas('purchase', function ($q) {
                    $q->whereIn('status', ['draft', 'approved', 'received']);
                })
                ->whereNotNull('conversion_factor')
                ->where('conversion_factor', '>', 0)
                ->orderByDesc('created_at')
                ->take(10)->get();

            // Exact match
            foreach ($latestPurchaseItems as $item) {
                if ($item->unit && strtolower($item->unit) === strtolower($targetUnit)) {
                    $factor = (float) $item->conversion_factor;
                    return ($fromUnit === $this->unit) ? $factor : ($factor > 0 ? 1.0 / $factor : 1.0);
                }
            }

            // Transitive global match (e.g. BUNGKUS -> LITER (from purchase) -> ML (global))
            foreach ($latestPurchaseItems as $item) {
                if ($item->unit) {
                    $globalTransitive = self::getConversionFactor($item->unit, $targetUnit);
                    if ($globalTransitive !== null) {
                        $transitiveFactor = (float) $item->conversion_factor * $globalTransitive;
                        return ($fromUnit === $this->unit) ? $transitiveFactor : ($transitiveFactor > 0 ? 1.0 / $transitiveFactor : 1.0);
                    }
                }
            }
        }

        // 2) Cek di tabel konversi khusus per-bahan (multi-satuan)
        foreach ($this->unitConversions as $conv) {
            $baseUnit = $conv->from_unit;
            $altUnit = $conv->to_unit;
            $factor = (float) $conv->factor; // 1 baseUnit = factor altUnit

            if ($factor <= 0)
                continue;

            if (strtolower($fromUnit) === strtolower($baseUnit) && strtolower($toUnit) === strtolower($altUnit)) {
                return $factor;
            }

            if (strtolower($fromUnit) === strtolower($altUnit) && strtolower($toUnit) === strtolower($baseUnit)) {
                return 1.0 / $factor;
            }
        }

        // Transitive for tabel konversi khusus
        if ($fromUnit === $this->unit || $toUnit === $this->unit) {
            $targetUnit = ($fromUnit === $this->unit) ? $toUnit : $fromUnit;
            foreach ($this->unitConversions as $conv) {
                $factor = (float) $conv->factor;
                if ($factor > 0 && strtolower($conv->from_unit) === strtolower($this->unit)) {
                    $globalTransitive = self::getConversionFactor($conv->to_unit, $targetUnit);
                    if ($globalTransitive !== null) {
                        $transitiveFactor = $factor * $globalTransitive;
                        return ($fromUnit === $this->unit) ? $transitiveFactor : (1.0 / $transitiveFactor);
                    }
                }
            }
        }

        // 3) Fallback ke kolom legacy (recipe_unit) jika masih ada
        if ($this->recipe_unit && $this->recipe_conversion_factor) {
            $baseUnit = $this->unit;
            $recipeUnit = $this->recipe_unit;
            $factor = (float) $this->recipe_conversion_factor;

            if ($factor > 0) {
                if (strtolower($fromUnit) === strtolower($baseUnit) && strtolower($toUnit) === strtolower($recipeUnit)) {
                    return $factor;
                }

                if (strtolower($fromUnit) === strtolower($recipeUnit) && strtolower($toUnit) === strtolower($baseUnit)) {
                    return 1.0 / $factor;
                }
            }
        }

        // 4) Fallback ke konversi umum (Kg, Gram, dst)
        $generic = self::getConversionFactor($fromUnit, $toUnit);

        return $generic ?? 1.0;
    }

    /**
     * Get stock statistics for a specific period (month/year)
     */
    public function getPeriodStats(int $month, int $year): array
    {
        $startOfPeriod = Carbon::create($year, $month, 1)->startOfDay();
        $endOfPeriod = (clone $startOfPeriod)->endOfMonth();

        // 1. Get Beginning State from Previous Posted Balance
        $prevBalance = MaterialStockBalance::where('material_type', 'AuxiliaryMaterial')
            ->where('material_id', $this->id)
            ->where('is_posted', true)
            ->where('period', '<', $startOfPeriod->format('Y-m-d'))
            ->orderBy('period', 'desc')
            ->first();

        $beginningQty = 0.0;
        $beginningValue = 0.0;
        $batches = [];

        if ($prevBalance) {
            $beginningQty = (float) $prevBalance->ending_qty;
            $beginningValue = (float) $prevBalance->ending_value;
            // Restore FIFO layers dari posting sebelumnya
            if ($prevBalance->ending_fifo_layers) {
                $storedLayers = is_array($prevBalance->ending_fifo_layers)
                    ? $prevBalance->ending_fifo_layers
                    : json_decode($prevBalance->ending_fifo_layers, true);
                if (!empty($storedLayers)) {
                    $batches = $storedLayers;
                }
            }
            if (empty($batches) && $beginningQty > 0) {
                $batches[] = ['qty' => $beginningQty, 'price' => $beginningQty > 0 ? $beginningValue / $beginningQty : 0];
            }
        }

        // 2. Fetch Transactions
        // IN: Purchases
        $purchases = \App\Models\PurchaseItem::with('purchase')
            ->where('auxiliary_material_id', $this->id)
            ->whereHas('purchase', function ($q) use ($startOfPeriod, $endOfPeriod) {
                $q->whereIn('status', ['draft', 'approved', 'received'])
                    ->whereBetween('purchase_date', [$startOfPeriod->format('Y-m-d'), $endOfPeriod->format('Y-m-d')]);
            })
            ->get()
            ->map(function ($item) {
                $purchase = $item->purchase;
                $price = (float) $item->unit_price;

                $ppnRate = (float) ($purchase?->ppn_rate ?? 0);
                $discountRate = (float) ($purchase?->discount_rate ?? 0);
                $taxType = $item->tax_type ?? 'after_tax';

                if ($taxType === 'after_tax' && $ppnRate > 0) {
                    $price = $price / (1 + ($ppnRate / 100));
                }
                if ($discountRate > 0) {
                    $price = $price * (1 - ($discountRate / 100));
                }

                return (object) [
                    'created_at' => $purchase->created_at,
                    'type' => 'in',
                    'qty' => (float) $item->quantity,
                    'price' => $price,
                ];
            });

        // OUT: Usages from Job Orders
        $usageDetails = \App\Models\JobOrderDetail::with(['product', 'jobOrder'])
            ->whereHas('jobOrder', fn($q) => $q->whereIn('status', ['in_progress', 'completed'])->whereBetween('mulai_job_at', [$startOfPeriod, $endOfPeriod]))
            ->get();

        $usageEntries = collect();
        foreach ($usageDetails as $detail) {
            $bom = \App\Models\BillOfMaterial::with('auxiliaries')
                ->where('product_id', $detail->product_id)->first();
            if (!$bom)
                continue;
            foreach ($bom->auxiliaries as $bomAux) {
                if ($bomAux->auxiliary_material_id !== $this->id)
                    continue;
                $qtyPerUnit = (float) ($bomAux->quantity ?? 0);
                $qtyTotal = $qtyPerUnit * (float) ($detail->quantity ?? 0);
                if ($qtyTotal > 0) {
                    $usageEntries->push((object) [
                        'created_at' => $detail->jobOrder->mulai_job_at,
                        'type' => 'out',
                        'qty' => $qtyTotal,
                    ]);
                }
            }
        }

        // OUT: Returns
        $returns = \App\Models\PurchaseReturnItem::with('purchaseReturn')
            ->where('auxiliary_material_id', $this->id)
            ->whereHas('purchaseReturn', function ($q) use ($startOfPeriod, $endOfPeriod) {
                $q->where('status', 'completed')
                    ->whereBetween('return_date', [$startOfPeriod->format('Y-m-d'), $endOfPeriod->format('Y-m-d')]);
            })
            ->get()
            ->map(function ($item) {
                return (object) [
                    'created_at' => $item->purchaseReturn->created_at,
                    'type' => 'out',
                    'qty' => (float) $item->quantity,
                ];
            });

        $entries = $purchases->concat($usageEntries)->concat($returns)->sortBy('created_at');

        $inQtyTotal = 0.0;
        $inValueTotal = 0.0;
        $outQtyTotal = 0.0;
        $outValueTotal = 0.0;

        foreach ($entries as $entry) {
            // Pastikan $entry adalah object, bukan array
            if (is_array($entry)) {
                $entry = (object) $entry;
            }

            $entryType = $entry->type ?? null;
            if ($entryType === 'in') {
                $batches[] = ['qty' => $entry->qty, 'price' => $entry->price];
                $inQtyTotal += $entry->qty;
                $inValueTotal += $entry->qty * $entry->price;
            } elseif ($entryType === 'out') {
                $rem = $entry->qty;
                $outQtyTotal += $rem;
                $allocatedCost = 0.0;
                while ($rem > 0 && !empty($batches)) {
                    $take = min($rem, $batches[0]['qty']);
                    $allocatedCost += $take * $batches[0]['price'];
                    $batches[0]['qty'] -= $take;
                    $rem -= $take;
                    if ($batches[0]['qty'] <= 0.0000001)
                        array_shift($batches);
                }
                $outValueTotal += $allocatedCost;
            }
        }

        $endingQty = $beginningQty + $inQtyTotal - $outQtyTotal;
        $endingValue = 0.0;
        foreach ($batches as $batch) {
            $endingValue += $batch['qty'] * $batch['price'];
        }

        return [
            'beginning_qty' => $beginningQty,
            'beginning_value' => $beginningValue,
            'in_qty' => $inQtyTotal,
            'in_value' => $inValueTotal,
            'out_qty' => $outQtyTotal,
            'out_value' => $outValueTotal,
            'ending_qty' => $endingQty,
            'ending_value' => $endingValue,
            'ending_fifo_layers' => json_encode($batches), // Simpan FIFO layers untuk ditampilkan di bulan berikutnya
        ];
    }
}
