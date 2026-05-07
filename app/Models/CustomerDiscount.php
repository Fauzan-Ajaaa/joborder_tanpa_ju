<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCompany;

class CustomerDiscount extends Model
{
    use HasFactory, HasCompany;

    protected $table = 'customer_discounts';

    protected $fillable = [
        'customer_id',
        'discount_type',
        'discount_amount',
        'is_active',
        'company_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'percentage' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
