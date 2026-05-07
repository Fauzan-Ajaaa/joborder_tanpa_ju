<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class JobOrderItem extends Model
{
    use HasCompany;

    protected $fillable = [
        'job_order_id',
        'product_id',
        'quantity',
        'company_id',
        'bbb_total',
        'btkl_total',
        'bop_total',
        'hpp_total',
        'hpp_per_unit',
    ];

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
