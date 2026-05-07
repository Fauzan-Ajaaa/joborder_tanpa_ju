<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCompany;

class BOM_Detail extends Model
{
    use HasFactory, HasCompany;

    protected $table = 'bom_details';

    protected $fillable = [
        'product_name',
        'raw_material_id',
        'quantity',
        'unit',
        'price_per_unit',
        'total_material_cost',
        'btkl',
        'bop',
        'total_hpp',
        'company_id',
    ];

    public function material()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomDetailItem::class, 'bom_detail_id');
    }

    // Hitung total otomatis (hpp + btkl + bop)
    public function getGrandTotalAttribute()
    {
        return $this->total_material_cost + $this->btkl + $this->bop;
    }
}
