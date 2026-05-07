<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Masterdata\Asset;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Services\DepreciationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepreciationJournalController extends Controller
{
    public function __construct(private DepreciationService $depreciationService) {}

    public function index(Request $request)
    {
        $month = (int) $request->input('month', date('m'));
        $year  = (int) $request->input('year', date('Y'));

        $entries = $this->buildEntries($month, $year);

        $monthNames = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ];
        $periode = ($monthNames[$month] ?? $month) . ' ' . $year;

        $firstYear = Asset::min(\DB::raw('YEAR(tanggal_perolehan)')) ?? now()->year;
        $years = range($firstYear, now()->year + 3);

        $totalDebit  = collect($entries)->sum('beban');
        $totalCredit = $totalDebit;

        // Cek apakah sudah diposting bulan ini
        $isPosted = JournalEntry::where('source_type', 'depreciation_adjustment')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->exists();

        return view('reports.jurnal_penyesuaian', compact(
            'entries', 'month', 'year', 'periode', 'years',
            'totalDebit', 'totalCredit', 'isPosted'
        ));
    }

    /**
     * Posting jurnal penyesuaian ke tabel journal_entries
     */
    public function post(Request $request)
    {
        $month = (int) $request->input('month', date('m'));
        $year  = (int) $request->input('year', date('Y'));

        $entries = $this->buildEntries($month, $year);
        if (empty($entries)) {
            return back()->with('error', 'Tidak ada data penyusutan untuk periode ini.');
        }

        // Hapus posting lama jika ada
        JournalEntry::where('source_type', 'depreciation_adjustment')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->each(function ($je) {
                $je->items()->delete();
                $je->delete();
            });

        DB::transaction(function () use ($entries, $month, $year) {
            $date = Carbon::create($year, $month, 1)->endOfMonth();

            foreach ($entries as $entry) {
                $beban = $entry['beban'];
                if ($beban <= 0) continue;

                $je = JournalEntry::create([
                    'journal_number'   => 'JP-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $entry['asset']->id_assets,
                    'transaction_date' => $date,
                    'source_type'      => 'depreciation_adjustment',
                    'source_id'        => $entry['asset']->id_assets,
                    'description'      => 'Jurnal Penyesuaian Penyusutan - ' . $entry['asset']->nama_asset,
                    'total_debit'      => $beban,
                    'total_credit'     => $beban,
                    'status'           => 'posted',
                ]);

                // Debit: BOP Penyusutan
                if ($entry['bop_coa']) {
                    JournalEntryItem::create([
                        'journal_entry_id'    => $je->id,
                        'chart_of_account_id' => $entry['bop_coa']->id,
                        'debit'               => $beban,
                        'credit'              => 0,
                        'description'         => 'Beban penyusutan ' . $entry['asset']->nama_asset,
                    ]);
                }

                // Kredit: Akumulasi Penyusutan
                if ($entry['akum_coa']) {
                    JournalEntryItem::create([
                        'journal_entry_id'    => $je->id,
                        'chart_of_account_id' => $entry['akum_coa']->id,
                        'debit'               => 0,
                        'credit'              => $beban,
                        'description'         => 'Akumulasi penyusutan ' . $entry['asset']->nama_asset,
                    ]);
                }
            }
        });

        return back()->with('success', 'Jurnal penyesuaian berhasil diposting ke neraca saldo.');
    }

    public function pdf(Request $request)
    {
        $month = (int) $request->input('month', date('m'));
        $year  = (int) $request->input('year', date('Y'));

        $entries = $this->buildEntries($month, $year);

        $monthNames = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ];
        $periode = ($monthNames[$month] ?? $month) . ' ' . $year;

        $totalDebit  = collect($entries)->sum('beban');
        $totalCredit = $totalDebit;

        // Cek apakah sudah diposting bulan ini
        $isPosted = JournalEntry::where('source_type', 'depreciation_adjustment')
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->exists();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.jurnal_penyesuaian_pdf', compact(
            'entries', 'month', 'year', 'periode',
            'totalDebit', 'totalCredit', 'isPosted'
        ));

        return $pdf->stream('jurnal-penyesuaian-' . $month . '-' . $year . '.pdf');
    }

    private function buildEntries(int $month, int $year): array
    {
        $assets  = Asset::all();
        $entries = [];

        foreach ($assets as $asset) {
            $schedule = $this->depreciationService->calculate(
                $asset->harga_perolehan,
                $asset->nilai_sisa,
                $asset->masa_manfaat,
                $asset->tanggal_perolehan->format('Y-m-d'),
                $asset->depreciation_method,
                'bulanan'
            );

            $row = collect($schedule)->first(fn($r) => $r['bulan'] === $month && $r['tahun'] === $year);
            if (!$row || $row['beban_penyusutan'] <= 0) continue;

            $tipe  = $asset->tipe_asset ?? 'peralatan';
            $label = ucfirst($tipe);

            $bopCoa = ChartOfAccount::where('account_name', 'BOP-Penyusutan ' . $label . ' - ' . $asset->nama_asset)->first()
                   ?? ChartOfAccount::where('account_name', 'like', 'BOP-Penyusutan ' . $label . '%')->first();

            $akumCoa = ChartOfAccount::where('account_name', 'Akumulasi Penyusutan ' . $label . ' - ' . $asset->nama_asset)->first()
                    ?? ChartOfAccount::where('account_name', 'like', 'Akumulasi Penyusutan%' . $label . '%')->first();

            $entries[] = [
                'tanggal'  => Carbon::create($year, $month, 1),
                'asset'    => $asset,
                'beban'    => round($row['beban_penyusutan'], 2),
                'bop_coa'  => $bopCoa,
                'akum_coa' => $akumCoa,
            ];
        }

        return $entries;
    }
}
