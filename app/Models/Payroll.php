<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCompany;

class Payroll extends Model
{
    use HasCompany;

    protected $table = 'payrolls';

    protected $fillable = [
        'employee_id',
        'product_id',
        'work_date',
        'nama',
        'kode_penggajian',
        'total_jam_kerja',
        'gaji_per_jam',
        'bonus',
        'potongan',
        'pajak',
        'total_gaji_perhari',
        'rata_rata_terjual_perhari',
        'total_btkl',
        'company_id',
    ];

    protected $casts = [
        'work_date' => 'date',
        'total_jam_kerja' => 'decimal:2',
        'gaji_per_jam' => 'decimal:2',
        'bonus' => 'decimal:2',
        'potongan' => 'decimal:2',
        'pajak' => 'decimal:2',
        'total_gaji_perhari' => 'decimal:2',
        'rata_rata_terjual_perhari' => 'decimal:2',
        'total_btkl' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payroll $payroll): void {
            // Generate kode otomatis
            if (empty($payroll->kode_penggajian)) {
                // Short code: BTKL-xx (min 2 digits, grows beyond 99)
                $sequence = static::count() + 1;
                $payroll->kode_penggajian = 'BTKL-' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
            }

            // Hitung total gaji & BTKL
            $payroll->hitungTotalGaji();
            $payroll->hitungTotalBTKL();

            // Sync optional column if exists (for installs that added pegawai_id later)
            if (Schema::hasColumn('payrolls', 'pegawai_id')) {
                $payroll->pegawai_id = $payroll->employee_id;
            }
        });

        static::updating(function (Payroll $payroll): void {
            $payroll->hitungTotalGaji();
            $payroll->hitungTotalBTKL();

            // Sync optional column if exists (for installs that added pegawai_id later)
            if (Schema::hasColumn('payrolls', 'pegawai_id')) {
                $payroll->pegawai_id = $payroll->employee_id;
            }
        });

        static::created(function (Payroll $payroll): void {
            $payroll->createJournalEntry();
        });
    }

    /**
     * Hitung total gaji per hari
     */
    public function hitungTotalGaji(): void
    {
        $jamKerja = $this->total_jam_kerja ?? 0;
        $gajiPerJam = $this->gaji_per_jam ?? 0;
        $bonus = $this->bonus ?? 0;
        $potongan = $this->potongan ?? 0;
        $pajak = $this->pajak ?? 0;

        // total gaji dasar
        $btklDasar = $jamKerja * $gajiPerJam;

        // Total gaji kotor (sebelum pajak) = dasar + bonus - potongan
        $gaji = $btklDasar + $bonus - $potongan;

        // Pajak (jika ada) dihitung dari gaji kotor
        $pajakAmount = ($pajak > 0) ? ($gaji * $pajak / 100) : 0;

        // Total gaji per hari (hasil akhir)
        $this->total_gaji_perhari = max(0, $gaji - $pajakAmount); 
    }

    /**
     * Hitung total BTKL (berdasarkan gaji per hari dibagi rata-rata penjualan per pcs)
     */
    public function hitungTotalBTKL(): void
    {
        $totalGaji = $this->total_gaji_perhari ?? 0;
        $rataTerjual = $this->rata_rata_terjual_perhari ?? 0;

        // Hindari pembagian dengan nol
        $this->total_btkl = ($rataTerjual > 0)
            ? $totalGaji / $rataTerjual
            : 0;
    }

    /**
     * Create journal entry for payroll transaction
     */
    public function createJournalEntry(): void
    {
        DB::transaction(function () {
            // Get accounts for payroll
            $wipAccount = ChartOfAccount::where('account_type', 'asset')->where('account_name', 'like', '%Work in Process%')->first();
            $payableAccount = ChartOfAccount::where('account_type', 'liability')->where('account_name', 'like', '%Utang%')->first();
            $taxAccount = ChartOfAccount::where('account_type', 'liability')->where('account_name', 'like', '%Pajak%')->first();

            if (!$wipAccount || !$payableAccount) {
                return; // Skip if required accounts not found
            }

            $journalEntry = JournalEntry::create([
                'journal_number' => JournalEntry::generateJournalNumber('PAY'),
                'transaction_date' => $this->work_date,
                'source_type' => Payroll::class,
                'source_id' => $this->id,
                'description' => "Penggajian {$this->kode_penggajian} - {$this->nama}",
                'total_debit' => $this->total_gaji_perhari,
                'total_credit' => $this->total_gaji_perhari,
                'status' => 'posted',
            ]);

            $lines = [];

            // Debit: Work in Process (Direct Labor Cost)
            $lines[] = [
                'chart_of_account_id' => $wipAccount->id,
                'debit' => $this->total_gaji_perhari,
                'credit' => 0,
                'description' => "BTKL - {$this->nama} ({$this->kode_penggajian})",
            ];

            // Credit: Utang Gaji (Salaries Payable)
            $lines[] = [
                'chart_of_account_id' => $payableAccount->id,
                'debit' => 0,
                'credit' => $this->total_gaji_perhari,
                'description' => "Utang gaji - {$this->nama}",
            ];

            // Credit: Utang Pajak (Tax Payable)
            if ($taxAccount && $this->pajak > 0) {
                $taxAmount = ($this->total_gaji_perhari * $this->pajak / 100);
                $lines[] = [
                    'chart_of_account_id' => $taxAccount->id,
                    'debit' => 0,
                    'credit' => $taxAmount,
                    'description' => "Utang pajak - {$this->nama}",
                ];
            }

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
        $journalEntry = JournalEntry::where('source_type', Payroll::class)
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
}
