<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\AuxiliaryMaterial;
use App\Models\BOM_Detail;
use App\Models\BillOfMaterial;
use App\Models\BillOfMaterialItem;
use App\Models\RawMaterial;
use App\Services\BarcodeService;

use App\Traits\HasCompany;

class Product extends Model
{
    use HasCompany;

    protected $fillable = [
        'name',
        'code',
        'barcode',
        'image_data',
        'image_mime_type',
        'description',
        'price',
        'stock',
        'bom_detail_id',
        'inventory_coa_id',
        'sales_return_coa_id',
        'company_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'decimal:2',
    ];

    protected $hidden = [
        'image_data',
    ];

    protected $appends = ['available_stock', 'has_image', 'stock_unit'];

    public function getHasImageAttribute(): bool
    {
        return !empty($this->image_mime_type) && isset($this->attributes['image_data']);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $product->code = static::generateCode();
            }
            if (empty($product->barcode)) {
                $product->barcode = $product->generateBarcode();
            }

            // Auto-generate COA Persediaan Barang Jadi spesifik produk
            $coaId = \App\Services\CoaAutoGenerateService::createCoaForProductInventory($product);
            if ($coaId) {
                $product->inventory_coa_id = $coaId;
            }

            // Auto-generate COA Penjualan spesifik produk
            \App\Services\CoaAutoGenerateService::createCoaForProductSales($product);

            // Auto-generate COA Retur Penjualan spesifik produk
            $returnCoaId = \App\Services\CoaAutoGenerateService::createCoaForProductSalesReturn($product);
            if ($returnCoaId) {
                $product->sales_return_coa_id = $returnCoaId;
            }
        });

        static::deleting(function ($product) {
            // Kumpulkan semua COA ID yang akan dihapus
            $coaIds = [];

            if ($product->inventory_coa_id) {
                $coaIds[] = $product->inventory_coa_id;
            }

            $salesCoa = \App\Models\ChartOfAccount::where('account_name', 'Penjualan - ' . $product->name)->first();
            if ($salesCoa) {
                $coaIds[] = $salesCoa->id;
            }

            $inventoryCoa = \App\Models\ChartOfAccount::where('account_name', 'Pers. Barang Jadi - ' . $product->name)->first();
            if ($inventoryCoa) {
                $coaIds[] = $inventoryCoa->id;
            }

            if (!empty($coaIds)) {
                // Hapus AccountPeriodBalance terlebih dahulu (menghindari foreign key constraint error di hosting
                // yang mungkin tidak memiliki ON DELETE CASCADE pada constraint ini)
                \App\Models\AccountPeriodBalance::whereIn('chart_of_account_id', $coaIds)->delete();

                // Baru hapus COA-nya
                \App\Models\ChartOfAccount::whereIn('id', $coaIds)->delete();
            }
        });
    }

    public function inventoryCoa()
    {
        return $this->belongsTo(\App\Models\ChartOfAccount::class, 'inventory_coa_id');
    }

    public function salesReturnCoa()
    {
        return $this->belongsTo(\App\Models\ChartOfAccount::class, 'sales_return_coa_id');
    }

    /**
     * Barcode akan disamakan dengan Kode Produk agar hasil scan sesuai dengan label yang terlihat.
     */
    public function generateBarcode(): string
    {
        return $this->code ?: static::generateCode();
    }

    /**
     * Generate kode produk otomatis, misal: PROD-YYYYMMDD-0001
     */
    public static function generateCode(): string
    {
        $prefix = 'PROD-';

        $today = today();
        $base = $prefix . $today->format('Ymd') . '-';

        $lastCode = static::withoutGlobalScopes()
            ->where('code', 'like', $base . '%')
            ->orderByDesc('id')
            ->value('code');

        $nextNumber = 1;
        if ($lastCode) {
            $numericPart = substr($lastCode, strlen($base));
            if (ctype_digit($numericPart)) {
                $nextNumber = (int) $numericPart + 1;
            }
        }

        return $base . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    // legacy relation removed (product_materials table dropped)

    /**
     * Relationship to BOM items through BOMs tied to this product.
     */
    public function bomItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            BillOfMaterialItem::class,
            BillOfMaterial::class,
            'product_id',         // Foreign key on BOMs
            'bill_of_material_id',// Foreign key on items
            'id',                 // Local key on products
            'id'                  // Local key on BOMs
        );
    }

    public function rawMaterials(): BelongsToMany
    {
        // kept for compatibility but still uses pivot table if it exists
        return $this->belongsToMany(RawMaterial::class, 'product_materials')
            ->withPivot('quantity_needed', 'unit')
            ->withTimestamps();
    }

    public function auxiliaryMaterials(): BelongsToMany
    {
        return $this->belongsToMany(AuxiliaryMaterial::class, 'product_auxiliary_materials')
            ->withPivot('quantity_needed', 'unit')
            ->withTimestamps();
    }

    public function bomDetail(): BelongsTo
    {
        return $this->belongsTo(BOM_Detail::class, 'bom_detail_id');
    }

    /**
     * Menghitung jumlah produk yang bisa dibuat berdasarkan stok bahan baku
     */
    public function getAvailableStockAttribute(): int
    {
        $bom = \App\Models\BillOfMaterial::with('items.rawMaterial')->where('product_id', $this->id)->first();

        if (!$bom || $bom->items->isEmpty()) {
            return 0;
        }

        $maxProducible = PHP_INT_MAX;

        foreach ($bom->items as $bomItem) {
            $rawMaterial = $bomItem->rawMaterial;
            $quantityNeeded = (float) $bomItem->quantity;

            if ($quantityNeeded <= 0 || !$rawMaterial)
                continue;

            $bomUnit = $bomItem->unit ?: $rawMaterial->unit;
            $rawUnit = $rawMaterial->unit;
            $rawStock = $rawMaterial->getCurrentStock(); // stok dalam satuan dasar

            $stockInBomUnit = $rawStock;

            if ($bomUnit !== $rawUnit) {
                // Ambil conversion_factor dari purchase_items
                $purchaseItem = \App\Models\PurchaseItem::where('raw_material_id', $rawMaterial->id)
                    ->whereNotNull('conversion_factor')
                    ->where('conversion_factor', '>', 0)
                    ->orderByDesc('created_at')
                    ->first();

                if ($purchaseItem) {
                    $factor = (float) $purchaseItem->conversion_factor;
                    $purchaseUnit = $purchaseItem->unit;
                    // Jika dibeli dalam satuan beli (misal EKOR) dan stok dalam satuan dasar (POTONG)
                    // factor = 6 berarti 1 EKOR = 6 POTONG
                    if ($purchaseUnit === $bomUnit) {
                        // bomUnit = EKOR, rawUnit = POTONG: stok POTONG / 6 = stok EKOR
                        $stockInBomUnit = $rawStock / $factor;
                    } else {
                        // bomUnit = POTONG, rawUnit = EKOR: stok EKOR * 6 = stok POTONG
                        $stockInBomUnit = $rawStock * $factor;
                    }
                }
            }

            $possible = $quantityNeeded > 0 ? (int) floor($stockInBomUnit / $quantityNeeded) : 0;
            $maxProducible = min($maxProducible, $possible);
        }

        return $maxProducible === PHP_INT_MAX ? 0 : (int) $maxProducible;
    }

    /**
     * Cek apakah produk bisa diproduksi
     */
    public function canProduce(int $quantity = 1): bool
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Unit label used when displaying available stock.
     * Uses the BOM item's unit (conversion unit preferred), falling back to raw material base unit.
     */
    public function getStockUnitAttribute(): string
    {
        $bom = \App\Models\BillOfMaterial::with(['items.rawMaterial.unitConversions'])->where('product_id', $this->id)->first();
        if (!$bom || $bom->items->isEmpty())
            return '-';

        $first = $bom->items->first();
        $unitCode = $first->unit ?: ($first->rawMaterial?->unit ?? null);
        if (!$unitCode)
            return '-';

        $unitName = \App\Models\Unit::where('code', $unitCode)->value('name');
        return $unitName ?: $unitCode;
    }

    /**
     * Mendapatkan bahan baku yang kurang untuk produksi
     */
    public function getMissingMaterials(int $quantity = 1): array
    {
        $missing = [];
        $items = $this->bomItems()->with('rawMaterial')->get();

        foreach ($items as $material) {
            $rawMaterial = $material->rawMaterial;
            $needed = $material->quantity * $quantity;
            $available = $rawMaterial ? $rawMaterial->stock : 0;

            // Convert needed quantity to raw material's base unit for accurate comparison
            $bomUnit = $material->unit ?: ($rawMaterial ? $rawMaterial->unit : null);
            $rawUnit = $rawMaterial ? $rawMaterial->unit : null;

            $neededInBaseUnit = $needed;
            if ($bomUnit && $rawUnit && $bomUnit !== $rawUnit && $rawMaterial) {
                $factor = $rawMaterial->getMaterialConversionFactor($bomUnit, $rawUnit);
                $neededInBaseUnit = $needed * $factor;
            }

            if ($available < $neededInBaseUnit) {
                $missing[] = [
                    'material' => $rawMaterial ? $rawMaterial->name : 'Unknown',
                    'needed' => $neededInBaseUnit,
                    'available' => $available,
                    'shortage' => $neededInBaseUnit - $available,
                    'unit' => $rawUnit,
                    'bom_unit' => $bomUnit,
                ];
            }
        }

        return $missing;
    }

    /**
     * Get barcode image as base64
     */
    public function getBarcodeImageAttribute(): string
    {
        if (empty($this->barcode)) {
            return BarcodeService::generateBarcode('PROD0000000000');
        }

        return BarcodeService::generateBarcode($this->barcode);
    }
}
