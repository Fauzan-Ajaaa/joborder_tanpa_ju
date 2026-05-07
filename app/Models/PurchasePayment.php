<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCompany;

class PurchasePayment extends Model
{
    use HasCompany;

    protected $fillable = [
        'purchase_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'company_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
