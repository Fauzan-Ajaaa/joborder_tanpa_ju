<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Model;

class OverheadPor extends Model
{
    use HasCompany;

    protected $table = 'overhead_por';

    protected $fillable = [
        'periode',
        'dasar_alokasi',
        'total_overhead_bulanan',
        'total_jam_dasar_alokasi',
        'por_per_jam',
        'company_id',
    ];

    protected $casts = [
        'total_overhead_bulanan' => 'decimal:2',
        'total_jam_dasar_alokasi' => 'decimal:2',
        'por_per_jam' => 'decimal:4',
    ];
}
