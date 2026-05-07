<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class PurchaseReturnItem extends Model
{
    use HasCompany;

    protected $fillable = [
        'purchase_return_id',
        'purchase_item_id',
        'raw_material_id',
        'auxiliary_material_id',
        'unit',
        'quantity',
        'unit_price',
        'subtotal',
        'company_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function auxiliaryMaterial(): BelongsTo
    {
        return $this->belongsTo(AuxiliaryMaterial::class);
    }

    /** Nama item: dari bahan baku atau bahan penolong */
    public function getItemNameAttribute(): string
    {
        if ($this->raw_material_id) {
            return $this->rawMaterial?->name ?? '-';
        }
        return $this->auxiliaryMaterial?->name ?? '-';
    }

    /** Unit tampilan (dari master barang) */
    public function getDisplayUnitAttribute(): string
    {
        if ($this->raw_material_id) {
            return $this->rawMaterial?->unit ?? $this->unit ?? '';
        }
        return $this->auxiliaryMaterial?->unit ?? $this->unit ?? '';
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->subtotal = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            $item->purchaseReturn->calculateTotals();
        });

        static::deleted(function ($item) {
            if ($item->purchaseReturn) {
                $item->purchaseReturn->calculateTotals();
            }
        });
    }
}
