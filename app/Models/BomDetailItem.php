<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class BomDetailItem extends Model
{
    use HasCompany;

    protected $fillable = [
        'bom_detail_id',
        'raw_material_id',
        'quantity',
        'unit',
        'price_per_unit',
        'line_total',
        'company_id',
    ];

    public function bomDetail(): BelongsTo
    {
        return $this->belongsTo(BOM_Detail::class, 'bom_detail_id');
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
