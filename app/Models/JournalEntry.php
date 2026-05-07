<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use App\Traits\HasCompany;

class JournalEntry extends Model
{
    use HasCompany;

    protected $fillable = [
        'journal_number',
        'transaction_date',
        'source_type',
        'source_id',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'company_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    /**
     * Relasi ke detail jurnal (items)
     */
    public function items(): HasMany
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    /**
     * Relasi ke journal entry lines
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    /**
     * Relasi polymorphic ke sumber transaksi
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Generate nomor jurnal otomatis
     */
    public static function generateJournalNumber(string $prefix = 'JU'): string
    {
        $date = now()->format('Ymd');
        $lastJournal = self::where('journal_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('journal_number', 'desc')
            ->first();

        if ($lastJournal) {
            $lastNumber = (int) substr($lastJournal->journal_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $newNumber);
    }

    /**
     * Cek apakah jurnal balance (debit = credit)
     */
    public function isBalanced(): bool
    {
        return $this->total_debit == $this->total_credit;
    }
}
