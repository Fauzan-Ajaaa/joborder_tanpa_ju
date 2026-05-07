<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class RawMaterialUsage extends Model
{
    use HasCompany;

    protected $fillable = [
        'transaction_id',
        'job_order_id',
        'raw_material_id',
        'auxiliary_material_id',
        'quantity_used',
        'unit',
        'unit_price',
        'cost',
        'company_id',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function auxiliaryMaterial(): BelongsTo
    {
        return $this->belongsTo(AuxiliaryMaterial::class);
    }

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    protected static function boot()
    {
        parent::boot();

        // catatan kami: Saat raw material usage dibuat
        static::created(function ($usage) {
            // catatan kami: Update total transaksi
            if ($usage->transaction) {
                $usage->transaction->calculateTotal();
            }
        });

        // catatan kami: Saat raw material usage diupdate
        static::updated(function ($usage) {
            // catatan kami: Update total transaksi
            if ($usage->transaction) {
                $usage->transaction->calculateTotal();
            }
        });

        // catatan kami: Saat raw material usage dihapus
        static::deleted(function ($usage) {
            // catatan kami: Update total transaksi
            if ($usage->transaction) {
                $usage->transaction->calculateTotal();
            }
        });
    }
}
