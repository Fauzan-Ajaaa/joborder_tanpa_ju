<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Traits\HasCompany;

class Purchase extends Model
{
    use HasCompany;

    public const TYPE_RAW_MATERIAL = 'raw_material';
    public const TYPE_AUXILIARY_MATERIAL = 'auxiliary_material';

    protected $fillable = [
        'purchase_number',
        'purchase_date',
        'supplier_id',
        'purchase_type',
        'fob_type',
        'subtotal',
        'fob_cost',
        'ppn_rate',
        'ppn_amount',
        'discount_rate',
        'discount_amount',
        'total_amount',
        'payment_method',
        'down_payment',
        'due_date',
        'payment_status',
        'paid_amount',
        'remaining_amount',
        'status',
        'notes',
        'received_by_employee_id',
        'receipt_document_path',
        'invoice_number',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'fob_cost' => 'decimal:2',
        'ppn_rate' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    // Relasi
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'received_by_employee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    // Accessors
    public function getRemainingAmountAttribute()
    {
        $remaining = $this->total_amount - $this->paid_amount;
        return max(0, round($remaining)); // Bulatkan dan pastikan tidak negatif
    }

    public function getTotalQuantityWithUnitAttribute(): string
    {
        $total = $this->items->sum('quantity');
        $unit = $this->items->first()?->unit ?? '';
        $formatted = number_format($total, floor($total) == $total ? 0 : 2, ',', '.');
        return $formatted . ($unit ? ' ' . $unit : '');
    }

    public function getAverageUnitPriceAttribute(): float
    {
        $totalQty = $this->items->sum('quantity');
        if ($totalQty == 0) return 0;
        return $this->items->sum('subtotal') / $totalQty;
    }

    // Auto-generate purchase number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchase) {
            if (empty($purchase->purchase_number)) {
                $date = now()->format('Ymd');
                
                // Use DB transaction to ensure unique number
                $purchaseNumber = DB::transaction(function() use ($date) {
                    // Get the highest number for today
                    $lastNumber = static::where('purchase_number', 'like', 'PO-' . $date . '-%')
                        ->lockForUpdate()
                        ->orderByRaw('CAST(SUBSTRING(purchase_number, 13) AS UNSIGNED) DESC')
                        ->value('purchase_number');
                    
                    $count = 1;
                    if ($lastNumber) {
                        // Extract number from format PO-YYYYMMDD-XXXX
                        $numericPart = substr($lastNumber, -4);
                        if (ctype_digit($numericPart)) {
                            $count = (int) $numericPart + 1;
                        }
                    }
                    
                    return 'PO-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
                });
                
                $purchase->purchase_number = $purchaseNumber;
            }
        });

        static::created(function ($purchase) {
            // Journal entry will be created manually after totals are calculated
        });
    }

    // Calculate totals using Metode A
    public function calculateTotals()
    {
        $this->subtotal = $this->items()->sum('subtotal');
        
        // Metode A: Diskon dari DPP Asli
        // 1. Hitung Total Harga Termasuk PPN (dari after_tax items) + Harga Sebelum PPN (dari before_tax items)
        $taxableSubtotal = 0; // before_tax items
        $nonTaxableSubtotal = 0; // after_tax items (sudah termasuk PPN)
        
        foreach ($this->items as $item) {
            if ($item->tax_type === 'after_tax') {
                $nonTaxableSubtotal += $item->subtotal;
            } else {
                $taxableSubtotal += $item->subtotal;
            }
        }
        
        // 2. Hitung Total Harga Termasuk PPN
        $totalHargaTermasukPpn = $taxableSubtotal + $nonTaxableSubtotal;
        
        // 3. Hitung DPP Asli
        // Untuk before_tax items: sudah DPP, tidak perlu dibagi 1.11
        // Untuk after_tax items: perlu dibagi 1.11 untuk dapat DPP
        $dppAsli = $taxableSubtotal + ($nonTaxableSubtotal / (1 + ($this->ppn_rate / 100)));
        
        // 4. Hitung Diskon dari DPP Asli
        $this->discount_amount = $dppAsli * ($this->discount_rate / 100);
        
        // 5. Hitung DPP Baru setelah diskon
        $dppBaru = $dppAsli - $this->discount_amount;
        
        // 6. Hitung PPN: PPN = 11% x DPP Baru
        $this->ppn_amount = $dppBaru * ($this->ppn_rate / 100);
        
        // 7. Total amount = DPP Baru + PPN + FOB
        $this->total_amount = $dppBaru + $this->ppn_amount + $this->fob_cost;
        $this->save();
        
        // Create journal entry after totals are calculated
        // Journal entry akan dibuat oleh JournalService di controller, bukan di sini
        // $this->createJournalEntry();
    }

    /**
     * Create journal entry for purchase transaction
     */
    public function createJournalEntry(): void
    {
        DB::transaction(function () {
            // Get accounts for purchase
            $rawMaterialAccount = ChartOfAccount::where('account_type', 'asset')->where('account_name', 'like', '%Raw Material%')->first();
            $cashAccount = ChartOfAccount::where('account_type', 'asset')->where('account_name', 'like', '%Kas%')->first();

            if (!$rawMaterialAccount || !$cashAccount) {
                return; // Skip if required accounts not found
            }

            $journalEntry = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('PUR'),
                'transaction_date' => $this->purchase_date,
                'source_type' => Purchase::class,
                'source_id' => $this->id,
                'description' => "Pembelian bahan baku {$this->purchase_number}",
                'total_debit' => $this->total_amount,
                'total_credit' => $this->total_amount,
                'status' => 'posted',
            ]);

            $lines = [];

            // Debit: Raw Materials Inventory (total amount)
            $lines[] = [
                'chart_of_account_id' => $rawMaterialAccount->id,
                'debit' => $this->total_amount,
                'credit' => 0,
                'description' => "Pembelian bahan baku {$this->purchase_number}",
            ];

            // Credit: Kas (Cash) - treat as cash purchase, not debt
            $lines[] = [
                'chart_of_account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $this->total_amount,
                'description' => "Pembayaran kas pembelian {$this->purchase_number}",
            ];

            foreach ($lines as $line) {
                $journalEntry->items()->create($line);
            }

            // Update account balances
            foreach ($lines as $line) {
                $account = ChartOfAccount::find($line['chart_of_account_id']);
                if ($account) {
                    $balanceChange = $line['debit'] - $line['credit'];
                    $account->balance += $balanceChange;
                    $account->save();
                }
            }
        });
    }

    /**
     * Reverse journal entry (for returns/cancellations)
     */
    public function reverseJournalEntry(): void
    {
        $journalEntry = JournalEntry::where('source_type', Purchase::class)
            ->where('source_id', $this->id)
            ->first();

        if ($journalEntry) {
            // Reverse all lines
            foreach ($journalEntry->items as $line) {
                $account = ChartOfAccount::find($line->chart_of_account_id);
                if ($account) {
                    $balanceChange = $line->credit - $line->debit; // Reverse
                    $account->balance += $balanceChange;
                    $account->save();
                }
            }

            $journalEntry->delete();
        }
    }

    /**
     * Update payment status based on paid amount
     */
    public function updatePaymentStatus()
    {
        // Total paid amount = down payment + other payments
        $this->paid_amount = $this->down_payment + $this->payments()->sum('amount');
        
        if ($this->paid_amount >= $this->total_amount) {
            $this->payment_status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'pending';
        }
        
        $this->save();
    }
}
