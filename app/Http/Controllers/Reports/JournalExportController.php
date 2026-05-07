<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class JournalExportController extends Controller
{
    public function jurnalUmumPdf(Request $request)
    {
        $year = (int) ($request->integer('year') ?: now()->year);
        $month = (int) ($request->integer('month') ?: now()->month);
        $accountId = $request->input('account_id');

        // Query for comprehensive journal showing all transactions (IAI SAK compliant)
        $query = JournalEntryItem::with(['journalEntry', 'chartOfAccount'])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_items.chart_of_account_id')
            ->whereMonth('journal_entries.transaction_date', $month)
            ->whereYear('journal_entries.transaction_date', $year)
            ->where('journal_entries.status', 'posted')
            ->select('journal_entry_items.*')
            ->orderBy('journal_entries.transaction_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_items.id')
            ->orderByRaw('CASE WHEN journal_entry_items.debit > 0 THEN 0 ELSE 1 END');

        if ($accountId) {
            $query->where('chart_of_account_id', $accountId);
        }

        $items = $query->get();

        $monthNames = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ];
        $periode = ($monthNames[$month] ?? $month) . ' ' . $year;
        $appName = auth()->user()->nama_perusahaan ?? config('app.name', 'Perusahaan');
        $appAddress = auth()->user()->alamat_perusahaan ?? '';

        // Calculate totals for IAI SAK compliance
        $totalDebit = $items->sum('debit');
        $totalCredit = $items->sum('credit');

        $pdf = Pdf::loadView('reports.jurnal_umum_pdf', [
            'items'       => $items,
            'periode'     => $periode,
            'appName'     => $appName,
            'appAddress'  => $appAddress,
            'totalDebit'  => $totalDebit,
            'totalCredit' => $totalCredit,
        ])->setPaper('a4', 'portrait');

        $filename = 'jurnal-umum-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.pdf';
        return $pdf->stream($filename);
    }
}
