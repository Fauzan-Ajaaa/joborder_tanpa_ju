<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCompany;

class PurchaseReturn extends Model
{
    use HasCompany;

    protected $fillable = [
        'return_number',
        'purchase_id',
        'return_date',
        'reason',
        'notes',
        'total_return_amount',
        'status',
        'company_id',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_return_amount' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            if (empty($return->return_number)) {
                $date = now()->format('Ymd');
                // Ambil nomor terakhir dengan retry untuk menghindari race condition
                $maxRetries = 5;
                $nextNumber = 1;
                
                for ($i = 0; $i < $maxRetries; $i++) {
                    $lastReturn = static::whereDate('created_at', today())
                        ->where('return_number', 'like', 'RTN-' . $date . '-%')
                        ->orderByDesc('return_number')
                        ->first();
                    
                    if ($lastReturn && preg_match('/RTN-' . $date . '-(\d+)/', $lastReturn->return_number, $matches)) {
                        $nextNumber = (int) $matches[1] + 1;
                    }
                    
                    $return->return_number = 'RTN-' . $date . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                    
                    // Cek apakah nomor sudah ada (untuk menghindari duplicate)
                    $exists = static::where('return_number', $return->return_number)->exists();
                    if (!$exists) {
                        break; // Nomor unik, keluar dari loop
                    }
                    
                    // Jika masih ada, increment dan coba lagi
                    $nextNumber++;
                    usleep(100000); // Tunggu 0.1 detik sebelum retry
                }
            }
        });
    }

    public function calculateTotals(): void
    {
        $this->total_return_amount = $this->items()->sum('subtotal');
        $this->save();
    }
}
