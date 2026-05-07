<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCompany;

class TransactionItem extends Model
{
    use HasCompany;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'company_id',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Saat item transaksi dibuat, hanya update total.
        // Pengurangan stok bahan baku untuk penjualan diproses saat transaksi berstatus completed di halaman Penjualan.
        static::created(function ($item) {
            if ($item->transaction) {
                $item->transaction->calculateTotal();
            }
        });

        // Saat item transaksi diupdate
        static::updated(function ($item) {
            // Update total transaksi
            if ($item->transaction) {
                $item->transaction->calculateTotal();
            }
        });

        // Saat item transaksi dihapus, kembalikan stok bahan baku
        static::deleted(function ($item) {
            $product = $item->product;
            $quantity = $item->quantity;

            // Kembalikan stok bahan baku
            foreach ($product->materials as $material) {
                $rawMaterial = $material->rawMaterial;
                $neededQuantity = $material->quantity_needed * $quantity;
                
                $rawMaterial->addStock($neededQuantity);
            }

            // Update total transaksi
            if ($item->transaction) {
                $item->transaction->calculateTotal();
            }
        });
    }
}
