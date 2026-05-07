<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class AccountPeriodBalance extends Model
{
    use HasCompany;

    protected $fillable = [
        'chart_of_account_id',
        'period',
        'ending_balance',
        'total_debit',
        'total_credit',
        'is_posted',
        'posted_at',
        'company_id',
    ];

    protected $casts = [
        'period' => 'date',
        'ending_balance' => 'decimal:2',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_posted' => 'boolean',
        'posted_at' => 'datetime',
    ];

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
