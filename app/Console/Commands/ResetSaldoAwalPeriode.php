<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccount;
use App\Models\JournalEntryItem;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ResetSaldoAwalPeriode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saldo:reset-periode {--bulan= : Bulan (1-12)} {--tahun= : Tahun (YYYY)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset saldo awal akun untuk periode baru (bulan/tahun)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bulan = $this->option('bulan') ?? date('m');
        $tahun = $this->option('tahun') ?? date('Y');
        
        $this->info("Memproses reset saldo awal untuk periode: {$bulan}/{$tahun}");
        
        // Validasi input
        if ($bulan < 1 || $bulan > 12) {
            $this->error('Bulan harus antara 1-12');
            return 1;
        }
        
        if ($tahun < 2000 || $tahun > 2099) {
            $this->error('Tahun harus antara 2000-2099');
            return 1;
        }
        
        // Konfirmasi
        if (!$this->confirm('Apakah Anda yakin ingin mereset saldo awal untuk periode baru? Tindakan ini akan menghitung ulang saldo awal berdasarkan transaksi sebelumnya.')) {
            $this->info('Dibatalkan');
            return 0;
        }
        
        DB::transaction(function () use ($bulan, $tahun) {
            $periodeAwal = Carbon::create($tahun, $bulan, 1);
            $sebelumPeriode = $periodeAwal->copy()->subDay();
            
            $accounts = ChartOfAccount::all();
            $totalProcessed = 0;
            
            $this->withProgressBar($accounts, function ($account) use ($periodeAwal, $sebelumPeriode, &$totalProcessed) {
                // Hitung saldo akhir dari periode sebelumnya
                $saldoAkhirSebelumnya = JournalEntryItem::where('chart_of_account_id', $account->id)
                    ->whereHas('journalEntry', function ($query) use ($sebelumPeriode) {
                        $query->where('status', 'posted')
                              ->whereDate('transaction_date', '<=', $sebelumPeriode);
                    })
                    ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) AS saldo')
                    ->value('saldo') ?? 0;
                
                // Update saldo awal periode baru
                $account->update([
                    'periode_awal' => $periodeAwal->toDateString(),
                    'saldo_awal_periode' => $saldoAkhirSebelumnya,
                    'opening_balance' => $saldoAkhirSebelumnya, // Update opening_balance juga
                ]);
                
                $totalProcessed++;
            });
            
            $this->newLine();
            $this->info("✅ Berhasil mereset {$totalProcessed} akun untuk periode {$bulan}/{$tahun}");
            $this->info("📅 Periode awal: {$periodeAwal->format('d F Y')}");
            $this->info("💰 Saldo awal dihitung dari transaksi hingga {$sebelumPeriode->format('d F Y')}");
        });
        
        return 0;
    }
}
