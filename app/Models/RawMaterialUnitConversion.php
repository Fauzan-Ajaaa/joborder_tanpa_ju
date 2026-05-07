<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCompany;

class RawMaterialUnitConversion extends Model
{
    use HasCompany;

    protected $fillable = [
        'raw_material_id',
        'from_unit',
        'to_unit',
        'factor',
        'company_id',
    ];

    protected $casts = [
        'factor' => 'decimal:6',
    ];

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
