<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Masterdata\Asset;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Services\DepreciationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PostDepreciationJournal extends Command
{
    protected $signature = 'depreciation:post {--month= : Bulan (default: bulan lalu)} {--year= : Tahun}';
    protected $description = 'Posting jurnal penyesuaian penyusutan aset tetap otomatis';

    public function handle(DepreciationService $service): int
    {
        // Default: bulan lalu (penyusutan diposting di awal bulan berikutnya)
        $date  = Carbon::now()->subMonth()->startOfMonth();
        $month = (int) ($this->option('month') ?: $date->month);
        $year  = (int) ($this->option('year')  ?: $date->year);

        $this->info("Posting jurnal penyesuaian untuk periode: {$month}/{$year}");

        $assets = Asset::all();
        $posted = 0;

        // Hapus posting lama periode ini agar tidak duplikat
        JournalEntry::where('source_type', 'depreciation_adjustment')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->each(function ($je) {
                $je->items()->delete();
                $je->delete();
            });

        DB::transaction(function () use ($assets, $service, $month, $year, &$posted) {
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            foreach ($assets as $asset) {
                $schedule = $service->calculate(
                    $asset->harga_perolehan,
                    $asset->nilai_sisa,
                    $asset->masa_manfaat,
                    $asset->tanggal_perolehan->format('Y-m-d'),
                    $asset->depreciation_method,
                    'bulanan'
                );

                $row = collect($schedule)->first(fn($r) => $r['bulan'] === $month && $r['tahun'] === $year);
                if (!$row || $row['beban_penyusutan'] <= 0) continue;

                $beban = round($row['beban_penyusutan'], 2);
                $tipe  = $asset->tipe_asset ?? 'peralatan';
                $label = ucfirst($tipe);

                $bopCoa = ChartOfAccount::where('account_name', 'BOP-Penyusutan ' . $label . ' - ' . $asset->nama_asset)->first()
                       ?? ChartOfAccount::where('account_name', 'like', 'BOP-Penyusutan ' . $label . '%')->first();

                $akumCoa = ChartOfAccount::where('account_name', 'Akumulasi Penyusutan ' . $label . ' - ' . $asset->nama_asset)->first()
                        ?? ChartOfAccount::where('account_name', 'like', 'Akumulasi Penyusutan%' . $label . '%')->first();

                if (!$bopCoa || !$akumCoa) {
                    $this->warn("  COA tidak ditemukan untuk aset: {$asset->nama_asset}");
                    continue;
                }

                $je = JournalEntry::create([
                    'journal_number'   => 'JP-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . substr($asset->id_assets, 0, 8),
                    'transaction_date' => $endDate,
                    'source_type'      => 'depreciation_adjustment',
                    'source_id'        => $asset->id_assets,
                    'description'      => 'Jurnal Penyesuaian Penyusutan - ' . $asset->nama_asset,
                    'total_debit'      => $beban,
                    'total_credit'     => $beban,
                    'status'           => 'posted',
                ]);

                JournalEntryItem::create([
                    'journal_entry_id'    => $je->id,
                    'chart_of_account_id' => $bopCoa->id,
                    'debit'               => $beban,
                    'credit'              => 0,
                    'description'         => 'Beban penyusutan ' . $asset->nama_asset,
                ]);

                JournalEntryItem::create([
                    'journal_entry_id'    => $je->id,
                    'chart_of_account_id' => $akumCoa->id,
                    'debit'               => 0,
                    'credit'              => $beban,
                    'description'         => 'Akumulasi penyusutan ' . $asset->nama_asset,
                ]);

                $this->info("  OK: {$asset->nama_asset} → Rp " . number_format($beban, 0, ',', '.'));
                $posted++;
            }
        });

        $this->info("Selesai. {$posted} jurnal diposting.");
        return Command::SUCCESS;
    }
}
