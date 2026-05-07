<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class JobOrderDetail extends Model
{
    use HasCompany;

    protected $fillable = [
        'job_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
        'raw_material_id',
        'planned_quantity',
        'actual_quantity',
        'company_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($detail) {
            if ($detail->quantity && $detail->unit_price) {
                $detail->total_price = $detail->quantity * $detail->unit_price;
            }
        });
    }
}
