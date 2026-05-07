<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class SalesReturn extends Model
{
    use HasCompany;

    protected $fillable = [
        'sales_transaction_id',
        'return_date',
        'reason',
        'subtotal',
        'ppn_amount',
        'grand_total',
        'company_id',
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function salesTransaction(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function recalcTotals(float $ppnRate = 11.00): void
    {
        // Subtotal = harga setelah diskon (belum PPN)
        $subtotal  = $this->items()->sum('subtotal');
        $ppnAmount = round($subtotal * ($ppnRate / 100), 2);
        $grandTotal = $subtotal + $ppnAmount;

        $this->subtotal    = $subtotal;
        $this->ppn_amount  = $ppnAmount;
        $this->grand_total = $grandTotal;
        $this->saveQuietly();
    }
}