<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class BillOfMaterialAuxiliary extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'bill_of_material_id',
        'auxiliary_material_id',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'company_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function billOfMaterial()
    {
        return $this->belongsTo(BillOfMaterial::class);
    }

    public function auxiliaryMaterial()
    {
        return $this->belongsTo(AuxiliaryMaterial::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_cost = $item->quantity * $item->unit_cost;
        });
    }
}
