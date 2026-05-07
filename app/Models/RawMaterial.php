<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use App\Models\MaterialStockBalance;

use App\Traits\HasCompany;

class RawMaterial extends Model
{
    use HasCompany;

    public const UNIT_OPTIONS = [
        'Kg' => 'Kilogram (Kg)',
        'Gram' => 'Gram (g)',
        'Milligram' => 'Milligram (mg)',
        'Liter' => 'Liter (L)',
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'unit',
        'recipe_unit',
        'recipe_conversion_factor',
        'stock',
        'min_stock',
        'min_stock_unit',
        'price_per_unit',
        'master_price_per_unit',
        'supplier_id',
        'chart_of_account_id',
        'inventory_coa_id',
    ];

    protected $casts = [
        'stock' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'recipe_conversion_factor' => 'decimal:6',
    ];

    /**
     * Generate next auto code for raw material, e.g. BB-0000001
     */
    public static function generateCode(): string
    {
        $prefix = 'BB-';

        // Untuk meminimalisir tabrakan (concurrency), kita gunakan angka random 7 digit.
        // User meminta "angka random" agar saat create bersamaan tidak bentrok.
        $randomNumber = mt_rand(1000000, 9999999);
        $code = $prefix . $randomNumber;

        // Pastikan unik di database UNTUK PERUSAHAAN INI (mengikuti scope)
        while (static::where('code', $code)->exists()) {
            $randomNumber = mt_rand(1000000, 9999999);
            $code = $prefix . $randomNumber;
        }

        return $code;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rawMaterial) {
            if (empty($rawMaterial->code)) {
                $rawMaterial->code = static::generateCode();
            }

            // Auto-generate COA untuk bahan baku baru (BBB - Biaya)
            if (empty($rawMaterial->chart_of_account_id)) {
                $rawMaterial->chart_of_account_id = \App\Services\CoaAutoGenerateService::createCoaForRawMaterial($rawMaterial);
                \Log::info('BBB COA created for raw material: ' . $rawMaterial->name . ' with ID: ' . $rawMaterial->chart_of_account_id);
            }

            // Auto-generate COA untuk persediaan bahan baku (114 - Asset)
            if (empty($rawMaterial->inventory_coa_id)) {
                $rawMaterial->inventory_coa_id = \App\Services\CoaAutoGenerateService::createCoaForRawMaterialInventory($rawMaterial);
                \Log::info('Inventory COA created for raw material: ' . $rawMaterial->name . ' with ID: ' . $rawMaterial->inventory_coa_id);
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(RawMaterialUsage::class);
    }

    public function unitConversions(): HasMany
    {
        return $this->hasMany(RawMaterialUnitConversion::class);
    }

    /**
     * Get effective minimum stock (manual min_stock)
     */
    public function getEffectiveMinStockAttribute()
    {
        return $this->min_stock ?? 0;
    }

    /**
     * Check if stock is below minimum
     */
    public function getIsStockLowAttribute()
    {
        return $this->stock < $this->effective_min_stock;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute()
    {
        if ($this->is_stock_low) {
            return [
                'status' => 'low',
                'label' => 'Stok Rendah',
                'color' => 'red',
                'icon' => 'exclamation-triangle'
            ];
        }

        $buffer = $this->stock - $this->effective_min_stock;

        if ($buffer <= ($this->effective_min_stock * 0.2)) { // Less than 20% buffer
            return [
                'status' => 'warning',
                'label' => 'Stok Menipis',
                'color' => 'yellow',
                'icon' => 'exclamation'
            ];
        }

        return [
            'status' => 'good',
            'label' => 'Stok Aman',
            'color' => 'green',
            'icon' => 'check-circle'
        ];
    }

    /**
     * Convert quantity from one unit to base unit
     */
    private function convertToBaseUnit($quantity, $fromUnit, $toUnit)
    {
        if ($fromUnit === $toUnit) {
            return $quantity;
        }

        // Simple conversion factors (you may want to enhance this)
        $toGram = [
            'Kg' => 1000,
            'Gram' => 1,
            'Milligram' => 0.001,
            'Liter' => 1000, // Assuming 1L = 1kg for simplicity
        ];

        if (!isset($toGram[$fromUnit]) || !isset($toGram[$toUnit])) {
            return $quantity; // Return original if conversion not possible
        }

        // Convert from unit -> gram -> to unit
        $grams = $quantity * $toGram[$fromUnit];
        return $grams / $toGram[$toUnit];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'raw_material_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_materials')
            ->withPivot('quantity_needed')
            ->withTimestamps();
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    /**
     * Kurangi stok bahan baku
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
     * Tambah stok bahan baku
     */
    public function addStock(float $quantity): void
    {
        $this->stock += $quantity;
        $this->save();
    }

    /**
     * Hitung harga FIFO untuk quantity tertentu
     * Menggunakan data pembelian (PurchaseItem) untuk tracking batch
     */
    public function getFifoPrice(float $quantity, string $unit = null): float
    {
        $targetUnit = $unit ?? $this->unit;
        $remainingQty = $quantity;
        $totalCost = 0.0;

        // Ambil semua pembelian yang sudah diterima, urut dari yang terlama
        $purchaseItems = \App\Models\PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.raw_material_id', $this->id)
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
                    $currentPrice = $currentPrice * $convertFactor;
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
     * Harga FIFO batch terdepan yang masih ada stok (setelah dikurangi semua pemakaian).
     * Ini adalah harga yang seharusnya dipakai untuk pemakaian berikutnya.
     */

    /**
     * Harga rata-rata (moving average) dari semua pembelian yang diterima — sama dengan kartu stok average.
     * Menggunakan DPP setelah diskon, konsisten dengan kartu stok.
     */
    public function getAverageUnitPrice(string $targetUnit = null): float
    {
        $targetUnit = $targetUnit ?? $this->unit;

        // Ambil langsung dari saldo terakhir kartu stok (metode Average)
        // Ini lebih akurat dan konsisten dengan tampilan kartu stok

        // Ambil semua transaksi masuk (purchase)
        $purchaseItems = \App\Models\PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.raw_material_id', $this->id)
            ->whereIn('purchases.status', ['draft', 'approved', 'received'])
            ->select('purchase_items.*', 'purchases.ppn_rate as purchase_ppn_rate', 'purchases.discount_rate as purchase_discount_rate', 'purchases.created_at as purchase_created_at')
            ->orderBy('purchases.created_at')
            ->get();

        // Ambil semua transaksi keluar (usage)
        $usages = \App\Models\RawMaterialUsage::where('raw_material_id', $this->id)
            ->orderBy('created_at')
            ->get();

        // Hitung saldo dengan metode Average (sama persis dengan kartu stok)
        $balanceQty = 0.0;
        $balanceTotal = 0.0;
        $averagePrice = 0.0;

        // Proses semua transaksi masuk
        foreach ($purchaseItems as $item) {
            $qtyInBase = (float) ($item->quantity ?? 0);
            $unitPriceBase = (float) ($item->unit_price ?? 0);

            $ppnRate = (float) ($item->purchase_ppn_rate ?? 0);
            $discRate = (float) ($item->purchase_discount_rate ?? 0);
            $taxType = $item->tax_type ?? 'after_tax';

            // DPP setelah diskon
            $dpp = ($taxType === 'after_tax' && $ppnRate > 0) ? $unitPriceBase / (1 + $ppnRate / 100) : $unitPriceBase;
            $effectivePrice = $discRate > 0 ? $dpp * (1 - $discRate / 100) : $dpp;

            // Update saldo (metode Average)
            $totalIn = $qtyInBase * $effectivePrice;
            $balanceTotal += $totalIn;
            $balanceQty += $qtyInBase;

            if ($balanceQty > 0) {
                $averagePrice = $balanceTotal / $balanceQty;
            }
        }

        // Proses transaksi keluar (tidak mengubah average price, hanya mengurangi qty dan total)
        foreach ($usages as $usage) {
            $qtyOut = (float) ($usage->quantity_used ?? 0);
            if ($qtyOut > 0 && $balanceQty > 0) {
                $totalOut = $qtyOut * $averagePrice;
                $balanceTotal -= $totalOut;
                $balanceQty -= $qtyOut;
            }
        }

        // Harga rata-rata terakhir (dari saldo)
        $avgPriceBase = $balanceQty > 0 ? $balanceTotal / $balanceQty : $averagePrice;

        // Konversi ke targetUnit jika perlu
        if ($targetUnit !== $this->unit) {
            $factor = $this->getMaterialConversionFactor($this->unit, $targetUnit) ?: 1;
            return $avgPriceBase / $factor;
        }

        return $avgPriceBase;
    }

    public function getCurrentFifoUnitPrice(string $targetUnit = null): float
    {
        $targetUnit = $targetUnit ?? $this->unit;

        // Bangun antrian batch FIFO dari pembelian (urut terlama dulu)
        $purchaseItems = \App\Models\PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.raw_material_id', $this->id)
            ->where('purchases.status', 'received')
            ->orderBy('purchases.purchase_date', 'asc')
            ->orderBy('purchase_items.created_at', 'asc')
            ->select('purchase_items.*', 'purchases.created_at as purchase_created_at')
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

        // Kurangi batch dengan semua pemakaian (usage) yang sudah terjadi
        $usages = \App\Models\RawMaterialUsage::where('raw_material_id', $this->id)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($usages as $usage) {
            $fromUnit = $usage->unit ?: $this->unit;
            $factor = $this->getMaterialConversionFactor($fromUnit, $this->unit) ?: 1;
            $remaining = (float) ($usage->quantity_used ?? 0) * $factor;

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

        if (empty($batches)) {
            // Tidak ada batch tersisa, fallback ke price_per_unit
            $price = (float) ($this->price_per_unit ?? 0);
        } else {
            $price = $batches[0]['price']; // harga batch terdepan
        }

        // Konversi ke target unit
        if ($targetUnit !== $this->unit) {
            $factor = $this->getMaterialConversionFactor($this->unit, $targetUnit) ?: 1;
            $price = $price / $factor;
        }

        return $price;
    }


    /**
     * Konversi satuan bahan baku
     * Mengubah unit dan menyesuaikan nilai stock dan price_per_unit
     */
    public function convertUnit(string $newUnit): bool
    {
        if ($this->unit === $newUnit) {
            return true;
        }

        $conversionFactor = $this->getConversionFactor($this->unit, $newUnit);

        if ($conversionFactor === null) {
            return false;
        }

        // catatan kami: Konversi stock dan price di master bahan baku saja
        $this->stock = $this->stock * $conversionFactor;
        $this->price_per_unit = $this->price_per_unit / $conversionFactor;
        $this->unit = $newUnit;
        $this->save();

        // catatan kami: Histori transaksi (PurchaseItem, RawMaterialUsage) dibiarkan apa adanya.
        // catatan kami: Perbedaan satuan akan ditangani di sisi laporan (kartu stok, update stok saat receive).

        return true;
    }

    /**
     * Dapatkan faktor konversi antara dua satuan
     */
    public static function getConversionFactor(string $fromUnit, string $toUnit): ?float
    {
        $fromUnit = strtolower($fromUnit);
        $toUnit = strtolower($toUnit);

        if ($fromUnit === $toUnit) {
            return 1.0;
        }

        // catatan kami: Konversi ke gram sebagai satuan dasar
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
     * Calculate current stock from all transactions (purchases, usage, returns)
     * This matches the calculation used in stock card
     */
    public function getCurrentStock(): float
    {
        // Mutasi MASUK: dari pembelian (draft, approved, received)
        $purchaseItems = \App\Models\PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->where('purchase_items.raw_material_id', $this->id)
            ->whereHas('purchase', function ($q) {
                $q->whereIn('status', ['draft', 'approved', 'received']);
            })
            ->sum('purchase_items.quantity');

        // Mutasi KELUAR: dari pemakaian bahan baku (RawMaterialUsage)
        $usages = \App\Models\RawMaterialUsage::where('raw_material_id', $this->id)
            ->get()
            ->sum(function ($usage) {
                $fromUnit = $usage->unit ?: $this->unit;
                $toUnit = $this->unit;
                $factor = $fromUnit && $toUnit ? $this->getMaterialConversionFactor($fromUnit, $toUnit) : 1;
                return ($usage->quantity_used ?? 0) * $factor;
            });

        // Mutasi KELUAR: dari retur pembelian yang sudah completed
        $purchaseReturns = \App\Models\PurchaseReturnItem::where('raw_material_id', $this->id)
            ->whereHas('purchaseReturn', function ($q) {
                $q->where('status', 'completed');
            })
            ->get()
            ->sum(function ($returnItem) {
                $return = $returnItem->purchaseReturn;
                $purchaseItem = $returnItem->purchaseItem;

                // Return quantities are stored in the unit they were returned in
                $fromUnit = $returnItem->unit ?: ($purchaseItem?->unit ?: $this->unit);
                $toUnit = $this->unit;
                $factor = $fromUnit && $toUnit ? $this->getMaterialConversionFactor($fromUnit, $toUnit) : 1;
                return ($returnItem->quantity ?? 0) * $factor;
            });

        return (float) ($purchaseItems - $usages - $purchaseReturns);
    }

    /**
     * Get current stock attribute (calculated from transactions)
     */
    public function getCurrentStockAttribute(): float
    {
        return $this->getCurrentStock();
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
                ->where('raw_material_id', $this->id)
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
    public function getPeriodStats(int $month, int $year, string $method = 'fifo'): array
    {
        $startOfPeriod = Carbon::create($year, $month, 1)->startOfDay();
        $endOfPeriod = (clone $startOfPeriod)->endOfMonth();

        // 1. Get Beginning State from Previous Posted Balance
        $prevBalance = MaterialStockBalance::where('material_type', 'RawMaterial')
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
            // Fallback: single batch jika tidak ada layers tersimpan
            if (empty($batches) && $beginningQty > 0) {
                $batches[] = ['qty' => $beginningQty, 'price' => $beginningQty > 0 ? $beginningValue / $beginningQty : 0];
            }
        }

        // 2. Fetch all transactions (In and Out) to simulate the period
        // IN: Purchases
        $purchases = \App\Models\PurchaseItem::with('purchase')
            ->where('raw_material_id', $this->id)
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

                if ($taxType === 'after_tax' && $ppnRate > 0)
                    $price = $price / (1 + ($ppnRate / 100));
                if ($discountRate > 0)
                    $price = $price * (1 - ($discountRate / 100));

                return [
                    'date' => $purchase->purchase_date,
                    'created_at' => $purchase->created_at,
                    'type' => 'in',
                    'qty' => (float) $item->quantity,
                    'price' => $price,
                ];
            });

        // OUT: Usages
        $usages = \App\Models\RawMaterialUsage::where('raw_material_id', $this->id)
            ->whereBetween('created_at', [$startOfPeriod, $endOfPeriod])
            ->get()
            ->map(function ($usage) {
                $factor = $this->getMaterialConversionFactor($usage->unit ?: $this->unit, $this->unit);
                return [
                    'date' => $usage->created_at,
                    'created_at' => $usage->created_at,
                    'type' => 'out',
                    'qty' => (float) ($usage->quantity_used ?? 0) * $factor,
                ];
            });

        // OUT: Returns
        $returns = \App\Models\PurchaseReturnItem::with('purchaseReturn')
            ->where('raw_material_id', $this->id)
            ->whereHas('purchaseReturn', function ($q) use ($startOfPeriod, $endOfPeriod) {
                $q->where('status', 'completed')
                    ->whereBetween('return_date', [$startOfPeriod->format('Y-m-d'), $endOfPeriod->format('Y-m-d')]);
            })
            ->get()
            ->map(function ($item) {
                $factor = $this->getMaterialConversionFactor($item->unit ?: $this->unit, $this->unit);
                return [
                    'date' => $item->purchaseReturn->return_date,
                    'created_at' => $item->purchaseReturn->created_at,
                    'type' => 'out',
                    'qty' => (float) ($item->quantity ?? 0) * $factor,
                ];
            });

        // Combine and sort by date for simulation
        $entries = $purchases->concat($usages)->concat($returns)->sortBy('created_at');

        $inQtyTotal = 0.0;
        $inValueTotal = 0.0;
        $outQtyTotal = 0.0;
        $outValueTotal = 0.0;

        foreach ($entries as $entry) {
            // Convert array to object if needed
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

        // Final ending value is the sum of remaining batches
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
