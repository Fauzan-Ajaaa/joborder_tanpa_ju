<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Traits\HasCompany;

class Penggajian extends Model
{
    use HasFactory, HasCompany;

    protected $table = 'penggajian';
    protected $primaryKey = 'id_gaji';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_gaji',
        'employee_id',
        'tanggal_penggajian',
        'no_transaksi_gaji',
        'total_service',
        'bonus',
        'bonus_service',
        'total_kehadiran',
        'bonus_kehadiran',
        'total_bonus_kehadiran',
        'tunjangan_makan',
        'tunjangan_jabatan',
        'tunjangan_lainnya',
        'lembur',
        'potongan_gaji',
        'tarif',
        'total_gaji_bersih',
        'detail_potongan',
        'status',
        'journal_status',
        'recognition_journal_id',
        'distribution_journal_id',
        'company_id',
    ];

    protected $casts = [
        'tanggal_penggajian' => 'date',
        'bonus' => 'float',
        'bonus_service' => 'float',
        'total_bonus_kehadiran' => 'float',
        'tunjangan_makan' => 'float',
        'tunjangan_jabatan' => 'float',
        'tunjangan_lainnya' => 'float',
        'lembur' => 'float',
        'potongan_gaji' => 'float',
        'tarif' => 'float',
        'total_gaji_bersih' => 'float',
        'status' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Penggajian $penggajian) {
            if (empty($penggajian->id_gaji)) {
                $penggajian->id_gaji = Str::uuid()->toString();
            }
            if (empty($penggajian->no_transaksi_gaji)) {
                $date = $penggajian->tanggal_penggajian ?: now();
                $prefix = 'GJ-' . $date->format('Ym');

                // Cari sequence berikutnya yang belum dipakai
                $sequence = 1;
                do {
                    $candidate = $prefix . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
                    $exists = static::where('no_transaksi_gaji', $candidate)->exists();
                    if ($exists) $sequence++;
                } while ($exists);

                $penggajian->no_transaksi_gaji = $candidate;
            }
        });

        static::updated(function (Penggajian $penggajian) {
            // Jurnal pembayaran ditangani oleh PenggajianObserver
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Create journal entry for penggajian creation
     */
    public function createJournalEntry()
    {
        $journalService = app(\App\Services\JournalService::class);
        return $journalService->createJournalFromPenggajian($this);
    }

    /**
     * Create payment journal entry
     */
    public function createPaymentJournal()
    {
        $journalService = app(\App\Services\JournalService::class);
        return $journalService->createJournalFromPenggajianPayment($this);
    }

    /**
     * Create journal entry for payroll transaction
     */
    public function createJournalEntryForPayroll(): void
    {
        DB::transaction(function () {
            // Get accounts for payroll
            $expenseAccount = ChartOfAccount::where('account_type', 'expense')->where('account_name', 'like', '%Gaji Karyawan%')->first();
            $cashAccount = ChartOfAccount::where('account_type', 'asset')->where('account_name', 'like', '%Kas%')->first();

            if (!$expenseAccount || !$cashAccount) {
                return; // Skip if required accounts not found
            }

            $journalEntry = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('PAY'),
                'transaction_date' => $this->tanggal_penggajian,
                'source_type' => Penggajian::class,
                'source_id' => $this->id_gaji,
                'description' => "Penggajian {$this->no_transaksi_gaji} - " . ($this->employee->name ?? 'Unknown'),
                'total_debit' => $this->total_gaji_bersih,
                'total_credit' => $this->total_gaji_bersih,
                'status' => 'posted',
            ]);

            $lines = [];

            // Debit: Beban Gaji dan Upah (Salary Expense)
            $lines[] = [
                'chart_of_account_id' => $expenseAccount->id,
                'debit' => $this->total_gaji_bersih,
                'credit' => 0,
                'description' => "Gaji - " . ($this->employee->name ?? 'Unknown') . " ({$this->no_transaksi_gaji})",
            ];

            // Credit: Kas (Cash) - treat as cash payment
            $lines[] = [
                'chart_of_account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $this->total_gaji_bersih,
                'description' => "Pembayaran gaji - " . ($this->employee->name ?? 'Unknown'),
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
     * Reverse journal entry (for corrections)
     */
    public function reverseJournalEntry(): void
    {
        $journalEntry = JournalEntry::where('source_type', Penggajian::class)
            ->where('source_id', $this->id_gaji)
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
}
