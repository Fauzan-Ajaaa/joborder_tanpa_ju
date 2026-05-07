<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class OverheadMonthly extends Model
{
    use HasCompany;

    protected $table = 'overhead_monthly';

    protected $fillable = [
        'periode',
        'total_biaya',
        'period_date',
        'category',
        'actual_amount',
        'company_id',
    ];

    protected $casts = [
        'total_biaya' => 'decimal:2',
    ];
}
