<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class SalesReturnItem extends Model
{
    use HasCompany;

    protected $fillable = [
        'sales_return_id',
        'sales_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'company_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesItem(): BelongsTo
    {
        return $this->belongsTo(SalesItem::class, 'sales_item_id');
    }
}