<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCompany;

class SalesTransaction extends Model
{
    use HasCompany;

    protected $table = 'sales_transactions';

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'job_order_id',
        'customer_id',
        'customer_name',
        'employee',
        'customer_address',
        'customer_phone',
        'delivery_notes',
        'notes',
        'subtotal',
        'discount_rate',
        'discount_amount',
        'fob_type',
        'fob_cost',
        'ppn_rate',
        'ppn_amount',
        'grand_total',
        'total_amount',
        'status',
        'payment_status',
        'payment_method',
        'payment_proof',
        'approval_status',
        'approval_notes',
        'approved_at',
        'approved_by',
        'company_id',
    ];

    protected $casts = [
        // Gunakan datetime supaya jam transaksi tidak hilang (bukan selalu 00:00)
        'transaction_date' => 'datetime',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'fob_cost' => 'decimal:2',
        'ppn_rate' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($trx) {
            if (empty($trx->transaction_number)) {
                // Use a more robust approach to prevent duplicates
                $prefix = 'SAL-' . date('Ymd');
                $maxNumber = static::where('transaction_number', 'like', $prefix . '%')
                    ->lockForUpdate()
                    ->max('transaction_number');
                
                if ($maxNumber) {
                    $lastNumber = (int)substr($maxNumber, -4);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }
                
                $trx->transaction_number = $prefix . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            }
            if (empty($trx->ppn_rate)) {
                $trx->ppn_rate = 11.00;
            }
            
            // Set default approval status untuk transaksi yang memerlukan approval
            if (empty($trx->approval_status) && in_array($trx->payment_method, ['transfer', 'ewallet'])) {
                $trx->approval_status = 'pending';
            }
        });

        static::saved(function ($trx) {
            $trx->recalculateTotals();
        });

        static::created(function ($trx) {
            // Journal creation is handled in controller ONLY
            // to prevent duplicate calls
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesItem::class, 'sales_transaction_id');
    }

    // Alias agar cocok dengan controller yang pakai 'salesItems'
    public function salesItems(): HasMany
    {
        return $this->items();
    }

    public function jobOrder(): BelongsTo
    {
        return $this->belongsTo(JobOrder::class, 'job_order_id', 'kode_job');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'sales_transaction_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'source_id')
            ->where('source_type', 'sales');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if payment method requires proof upload
     * Only for delivery (destination) with transfer/ewallet
     */
    public function requiresPaymentProof(): bool
    {
        return $this->fob_type === 'destination' 
            && in_array($this->payment_method, ['transfer', 'ewallet']);
    }

    /**
     * Check if transaction is approved
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if transaction is pending approval
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Check if transaction is rejected
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }


    public function recalculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $fobCost = (float)($this->fob_cost ?? 0);
        $ppnRate = (float)($this->ppn_rate ?? 11.00);

        $baseForPpn = $subtotal + $fobCost;
        $ppnAmount = round($baseForPpn * ($ppnRate / 100), 2);
        $grandTotal = $subtotal + $fobCost + $ppnAmount;

        $dirty = false;

        if ((float)$this->subtotal !== (float)$subtotal) {
            $this->subtotal = $subtotal;
            $dirty = true;
        }
        if ((float)$this->ppn_amount !== (float)$ppnAmount) {
            $this->ppn_amount = $ppnAmount;
            $dirty = true;
        }
        if ((float)$this->grand_total !== (float)$grandTotal) {
            $this->grand_total = $grandTotal;
            $dirty = true;
        }
        // total_amount lama: tetap diset agar kompatibel
        if ((float)$this->total_amount !== (float)$grandTotal) {
            $this->total_amount = $grandTotal;
            $dirty = true;
        }

        if ($dirty) {
            $this->saveQuietly();
        }
    }

    /**
     * Calculate totals including HPP from job order
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $discountRate = (float)($this->discount_rate ?? 0);
        $discountAmount = round($subtotal * ($discountRate / 100), 2);
        $fobCost = (float)($this->fob_cost ?? 0);
        $ppnRate = (float)($this->ppn_rate ?? 11.00);

        // Hitung subtotal setelah diskon
        $subtotalAfterDiscount = $subtotal - $discountAmount;
        
        // PPN dihitung hanya dari subtotal setelah diskon (ongkir tidak kena PPN)
        $baseForPpn = $subtotalAfterDiscount;
        $ppnAmount = round($baseForPpn * ($ppnRate / 100), 2);
        
        // Total akhir = subtotal setelah diskon + PPN + ongkir
        $grandTotal = $subtotalAfterDiscount + $fobCost + $ppnAmount;

        $dirty = false;
        
        if ((float)$this->subtotal !== (float)$subtotal) {
            $this->subtotal = $subtotal;
            $dirty = true;
        }
        if ((float)$this->discount_amount !== (float)$discountAmount) {
            $this->discount_amount = $discountAmount;
            $dirty = true;
        }
        if ((float)$this->ppn_amount !== (float)$ppnAmount) {
            $this->ppn_amount = $ppnAmount;
            $dirty = true;
        }
        if ((float)$this->grand_total !== (float)$grandTotal) {
            $this->grand_total = $grandTotal;
            $dirty = true;
        }
        
        // total_amount lama: tetap diset agar kompatibel
        if ((float)$this->total_amount !== (float)$grandTotal) {
            $this->total_amount = $grandTotal;
            $dirty = true;
        }

        if ($dirty) {
            $this->saveQuietly();
        }
    }

    /**
     * Create journal entry for sales transaction
     */
    public function createJournalEntry(): void
    {
        // Hapus jurnal lama jika ada
        $existingJournals = \App\Models\JournalEntry::where('source_type', 'sales')
            ->where('source_id', $this->id)
            ->get();
            
        foreach ($existingJournals as $existingJournal) {
            \App\Models\JournalEntryItem::where('journal_entry_id', $existingJournal->id)->delete();
            $existingJournal->delete();
            \Log::info('Jurnal penjualan lama dihapus', [
                'sales_id' => $this->id,
                'deleted_journal_id' => $existingJournal->id
            ]);
        }
        
        \Log::info('Membuat journal penjualan baru', [
            'sales_id' => $this->id,
            'transaction_number' => $this->transaction_number
        ]);
        
        // Gunakan JournalService untuk konsistensi
        $journalService = app(\App\Services\JournalService::class);
        $journal = $journalService->createJournalFromSales($this);
        \Log::info('Journal penjualan berhasil dibuat', [
            'sales_id' => $this->id,
            'journal_id' => $journal->id,
            'journal_number' => $journal->journal_number
        ]);
    }

    /**
     * Reverse journal entry (for returns/cancellations)
     */
    public function reverseJournalEntry(): void
    {
        $journalEntry = JournalEntry::where('source_type', 'sales') // Gunakan string, bukan class
            ->where('source_id', $this->id)
            ->first();

        if ($journalEntry) {
            // Reverse all lines
            foreach ($journalEntry->items as $line) {
                $reverseLine = JournalEntry::create([
                    'journal_number' => JournalEntry::generateJournalNumber('SAL'),
                    'transaction_date' => $this->transaction_date,
                    'source_type' => 'sales',
                    'source_id' => $this->id,
                    'description' => "Reverse: Penjualan {$this->transaction_number}",
                    'total_debit' => $line->credit,
                    'total_credit' => $line->debit,
                    'status' => 'posted',
                ]);

                $reverseLine->items()->create([
                    'chart_of_account_id' => $line->chart_of_account_id,
                    'debit' => $line->credit, // Reverse
                    'credit' => $line->debit, // Reverse
                    'description' => 'Reverse: ' . $line->description,
                ]);

                $journalEntry->delete();
            }
        }
    }
}