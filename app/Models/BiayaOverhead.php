<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ChartOfAccount;
use App\Traits\HasCompany;

class BiayaOverhead extends Model
{
    use HasCompany;

    protected $table = 'biaya_overhead';
    protected $fillable = [
        'jenis_biaya',
        'kategori',
        'chart_of_account_id',
        'bop_code',
        'periode',
        'items',
        'total',
        'unit_produksi',
        'product_id',
        'status_pembayaran',
        'catatan',
    ];

    protected $casts = [
        'items' => 'array',
        'periode' => 'date',
        'total' => 'decimal:2',
        'unit_produksi' => 'integer',
        'kategori' => 'string',
    ];

        protected $appends = ['tarif_per_unit'];

    /**
     * Relasi ke produk terkait (opsional).
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    /**
     * Hitung tarif per unit otomatis (total / unit_produksi).
     */
    public function getTarifPerUnitAttribute()
    {
        return ($this->unit_produksi > 0)
            ? (float) $this->total / (int) $this->unit_produksi
            : 0;
    }

}

