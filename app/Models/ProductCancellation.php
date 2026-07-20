<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasCompany;

class ProductCancellation extends Model
{
    use HasCompany;

    protected $fillable = [
        'cancellation_number',
        'job_order_id',
        'product_id',
        'quantity_cancelled',
        'bbb_cost',
        'btkl_cost',
        'bop_cost',
        'total_cost',
        'reason',
        'status',
        'cancelled_at',
        'cancelled_by',
        'approved_by',
        'approved_at',
        'approval_notes',
        'company_id',
    ];

    protected $casts = [
        'quantity_cancelled' => 'decimal:4',
        'bbb_cost' => 'decimal:2',
        'btkl_cost' => 'decimal:2',
        'bop_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cancellation) {
            if (empty($cancellation->cancellation_number)) {
                $cancellation->cancellation_number = self::generateCancellationNumber();
            }
            if (empty($cancellation->cancelled_at)) {
                $cancellation->cancelled_at = now();
            }
            if (empty($cancellation->cancelled_by)) {
                $cancellation->cancelled_by = auth()->id();
            }
        });
    }

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'source_id')
            ->where('source_type', 'product_cancellation');
    }

    /**
     * Generate unique cancellation number
     */
    public static function generateCancellationNumber(): string
    {
        $prefix = 'CANCEL-' . date('Ymd');
        $companyId = auth()->user()?->company_id ?? 1;
        
        $maxNumber = static::where('cancellation_number', 'like', $prefix . '%')
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->max('cancellation_number');
        
        if ($maxNumber) {
            $lastNumber = (int)substr($maxNumber, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate costs based on job order progress
     * Menggunakan per-unit cost dari BOM untuk akurasi
     */
    public function calculateCosts(): void
    {
        $jobOrder = $this->jobOrder;
        if (!$jobOrder) return;

        $product = $this->product;
        if (!$product) return;

        // Ambil BOM untuk produk
        $bom = $product->billOfMaterials()->first();
        if (!$bom) return;

        // Hitung per-unit cost dari BOM
        $bbbPerUnit = (float)$bom->calculateTotalMaterialCost();
        
        $totalDurationMinutes = (float)$bom->calculateTotalDuration();
        $totalDurationHours = $totalDurationMinutes > 0 ? $totalDurationMinutes / 60 : 0;
        $btklRatePerHour = (float)($bom->btkl_rate_per_hour ?? 0);
        $btklPerUnit = $totalDurationHours * $btklRatePerHour;
        
        $bopRatePerHour = (float)($bom->bop_rate_per_hour ?? 0);
        $bopPerUnit = floor($totalDurationHours * $bopRatePerHour);

        // Kalikan dengan quantity yang dibatalkan
        $quantity = (float)$this->quantity_cancelled;
        
        $this->bbb_cost = round($bbbPerUnit * $quantity, 2);
        $this->btkl_cost = floor($btklPerUnit * $quantity);
        $this->bop_cost = floor($bopPerUnit * $quantity);
        $this->total_cost = round($this->bbb_cost + $this->btkl_cost + $this->bop_cost, 2);
    }

    /**
     * Create journal entries for product cancellation
     * Creates separate journals for BBB, BTKL, and BOP (matching job order finish structure)
     */
    public function createJournalEntry(): array
    {
        $journalService = app(\App\Services\JournalService::class);
        return $journalService->createJournalFromProductCancellation($this);
    }

    /**
     * Check if cancellation is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}