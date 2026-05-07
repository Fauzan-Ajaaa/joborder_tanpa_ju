<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class JobOrderMaterial extends Model
{
    use HasCompany;

    protected $fillable = [
        'job_order_id',
        'bahan_baku_id',
        'qty_per_unit',
        'qty_total',
        'unit',
        'harga_per_unit',
        'total_biaya',
        'company_id',
    ];

    protected $casts = [
        'qty_per_unit' => 'decimal:4',
        'qty_total' => 'decimal:4',
        'harga_per_unit' => 'decimal:4',
        'total_biaya' => 'decimal:2',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'bahan_baku_id');
    }
}
