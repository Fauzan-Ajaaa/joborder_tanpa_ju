<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCompany;

class CashBook extends Model
{
    use HasCompany;

    protected $fillable = [
        'transaction_date',
        'reference_number',
        'description',
        'amount',
        'type',
        'chart_of_account_id',
        'company_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
