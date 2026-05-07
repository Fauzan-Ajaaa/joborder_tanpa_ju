<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class BillOfMaterialProcess extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'bill_of_material_id',
        'process_name',
        'duration_minutes',
        'btkl_cost',
        'bop_cost',
        'total_process_cost',
        'sequence_order',
        'company_id',
    ];

    protected $casts = [
        'duration_minutes' => 'decimal:2',
        'btkl_cost' => 'decimal:2',
        'bop_cost' => 'decimal:2',
        'total_process_cost' => 'decimal:2',
        'sequence_order' => 'integer',
    ];

    public function billOfMaterial()
    {
        return $this->belongsTo(BillOfMaterial::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($process) {
            $bom = $process->billOfMaterial;
            $duration_hours = $process->duration_minutes / 60;
            $process->btkl_cost = $duration_hours * $bom->btkl_rate_per_hour;
            $process->bop_cost = $duration_hours * $bom->bop_rate_per_hour;
            $process->total_process_cost = $process->btkl_cost + $process->bop_cost;
        });
    }
}
