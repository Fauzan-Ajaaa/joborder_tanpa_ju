<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuxiliaryMaterialUnitConversion extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'auxiliary_material_id',
        'from_unit',
        'to_unit',
        'factor',
    ];

    protected $casts = [
        'factor' => 'decimal:6',
    ];

    public function auxiliaryMaterial(): BelongsTo
    {
        return $this->belongsTo(AuxiliaryMaterial::class);
    }
}
