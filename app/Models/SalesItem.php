<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class SalesItem extends Model
{
    use HasCompany;

    protected $table = 'sales_items';

    protected $fillable = [
        'sales_transaction_id',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'sales_transaction_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            if ($item->quantity !== null && $item->unit_price !== null) {
                $item->subtotal = (float) $item->quantity * (float) $item->unit_price;
            }
        });

        static::saved(function ($item) {
            if ($item->transaction) {
                $item->transaction->recalculateTotals();
            }
        });

        static::deleted(function ($item) {
            if ($item->transaction) {
                $item->transaction->recalculateTotals();
            }
        });
    }
}
