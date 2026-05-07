<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCompany;

class JournalEntryItem extends Model
{
    use HasCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'journal_entry_id',
        'chart_of_account_id',
        'debit',
        'credit',
        'description',
        'company_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the journal entry that owns the journal entry item.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Get the chart of account that owns the journal entry item.
     */
    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    /**
     * Scope a query to only include items with debit amount.
     */
    public function scopeDebit($query)
    {
        return $query->where('debit', '>', 0);
    }

    /**
     * Scope a query to only include items with credit amount.
     */
    public function scopeCredit($query)
    {
        return $query->where('credit', '>', 0);
    }

    /**
     * Get the formatted debit amount.
     *
     * @return string
     */
    public function getFormattedDebitAttribute(): string
    {
        return number_format($this->debit, 2, ',', '.');
    }

    /**
     * Get the formatted credit amount.
     *
     * @return string
     */
    public function getFormattedCreditAttribute(): string
    {
        return number_format($this->credit, 2, ',', '.');
    }

    /**
     * Get the absolute amount (always positive).
     *
     * @return float
     */
    public function getAmountAttribute(): float
    {
        return $this->debit > 0 ? $this->debit : $this->credit;
    }

    /**
     * Get the formatted amount with currency.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Check if the item is a debit transaction.
     *
     * @return bool
     */
    public function isDebit(): bool
    {
        return $this->debit > 0;
    }

    /**
     * Check if the item is a credit transaction.
     *
     * @return bool
     */
    public function isCredit(): bool
    {
        return $this->credit > 0;
    }
}