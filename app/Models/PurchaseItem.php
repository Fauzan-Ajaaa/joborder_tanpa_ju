<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class PurchaseItem extends Model
{
    use HasCompany;

    protected $fillable = [
        'purchase_id',
        'raw_material_id',
        'auxiliary_material_id',
        'quantity',
        'unit',
        'conversion_factor',
        'unit_price',
        'subtotal',
        'tax_type',
        'company_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'conversion_factor' => 'decimal:6',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /** Display quantity (what user actually input) */
    public function getDisplayQuantityAttribute(): float
    {
        // If the item has a conversion factor and unit is different from base unit
        if ($this->conversion_factor && $this->conversion_factor > 0 && $this->unit) {
            // Get the material to determine base unit
            $material = null;
            if ($this->raw_material_id) {
                $material = $this->rawMaterial;
            } elseif ($this->auxiliary_material_id) {
                $material = $this->auxiliaryMaterial;
            }
            
            if ($material && $this->unit !== $material->unit) {
                // Convert base unit quantity to display unit quantity
                return $this->quantity * $this->conversion_factor;
            }
        }
        
        // Return base unit quantity if no conversion needed
        return $this->quantity;
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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

    /** Unit tampilan untuk item */
    public function getDisplayUnitAttribute(): string
    {
        return $this->unit ?? '';
    }

    public function getUnitNameAttribute(): string
    {
        return $this->unit ?? '';
    }

    /** Display unit price (price per display unit) */
    public function getDisplayUnitPriceAttribute(): float
    {
        // If the item has a conversion factor and unit is different from base unit
        if ($this->conversion_factor && $this->conversion_factor > 0 && $this->unit) {
            // Get the material to determine base unit
            $material = null;
            if ($this->raw_material_id) {
                $material = $this->rawMaterial;
            } elseif ($this->auxiliary_material_id) {
                $material = $this->auxiliaryMaterial;
            }
            
            if ($material && $this->unit !== $material->unit) {
                // Convert base unit price to display unit price
                return $this->unit_price / $this->conversion_factor;
            }
        }
        
        // Return base unit price if no conversion needed
        return $this->unit_price;
    }

    /** Display subtotal (subtotal for display quantity) */
    public function getDisplaySubtotalAttribute(): float
    {
        // Calculate based on display quantity and display unit price
        $displayQuantity = $this->display_quantity;
        $displayUnitPrice = $this->display_unit_price;
        
        return $displayQuantity * $displayUnitPrice;
    }

    // Auto-calculate subtotal
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->subtotal = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            $item->purchase->calculateTotals();
        });

        static::deleted(function ($item) {
            $item->purchase->calculateTotals();
        });
    }
}
