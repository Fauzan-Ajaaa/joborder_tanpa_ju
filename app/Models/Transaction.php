<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCompany;

class Transaction extends Model
{
    use HasCompany;

    protected $fillable = [
        'transaction_number',
        'type',
        'transaction_date',
        'notes',
        'total_amount',
        'status',
        'supplier_id',
        'company_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function rawMaterialUsages(): HasMany
    {
        return $this->hasMany(RawMaterialUsage::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = 'TRX-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
            // Default type to purchase when hidden field is not provided
            if (empty($transaction->type)) {
                $transaction->type = 'purchase';
            }
        });

        // Hitung total setelah items disimpan
        static::saved(function ($transaction) {
            $transaction->calculateTotal();
        });
    }

    /**
     * Hitung total amount dari items atau rawMaterialUsages
     */
    public function calculateTotal(): void
    {
        // Untuk penjualan, hitung dari items
        if ($this->type === 'sale') {
            $total = $this->items()->sum('subtotal');
        }
        // Untuk pembelian, hitung dari rawMaterialUsages
        elseif ($this->type === 'purchase') {
            $total = $this->rawMaterialUsages()->sum('cost');
        }
        else {
            $total = 0;
        }
        
        if ($this->total_amount != $total) {
            $this->total_amount = $total;
            $this->saveQuietly(); // Save tanpa trigger event lagi
        }
    }
}
