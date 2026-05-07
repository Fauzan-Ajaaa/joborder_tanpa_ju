<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCompany;

class MaterialStockBalance extends Model
{
    use HasCompany;

    protected $fillable = [
        'material_type',
        'material_id',
        'period',
        'beginning_qty',
        'beginning_value',
        'beginning_fifo_layers',
        'in_qty',
        'in_value',
        'out_qty',
        'out_value',
        'ending_qty',
        'ending_value',
        'ending_fifo_layers',
        'company_id',
        'is_posted',
    ];

    protected $casts = [
        'period' => 'date',
        'beginning_qty' => 'decimal:4',
        'beginning_value' => 'decimal:2',
        'beginning_fifo_layers' => 'array',
        'in_qty' => 'decimal:4',
        'in_value' => 'decimal:2',
        'out_qty' => 'decimal:4',
        'out_value' => 'decimal:2',
        'ending_qty' => 'decimal:4',
        'ending_value' => 'decimal:2',
        'ending_fifo_layers' => 'array',
        'is_posted' => 'boolean',
    ];

    public function material()
    {
        return $this->morphTo();
    }
}
