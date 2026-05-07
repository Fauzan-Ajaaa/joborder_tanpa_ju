<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LedgerExportController extends Controller
{
    public function ledgerPdf(Request $request)
    {
        $year      = (int) ($request->integer('year') ?: now()->year);
        $month     = (int) ($request->integer('month') ?: now()->month);
        $accountId = (int) $request->input('account_id');

        if (!$accountId) {
            return redirect()
                ->route('reports.buku-besar')
                ->with('error', 'Silakan pilih akun terlebih dahulu.');
        }

        $account      = ChartOfAccount::findOrFail($accountId);
        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();

        $openingBalance = JournalEntryItem::where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted')
                ->whereDate('transaction_date', '<', $startOfMonth))
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) AS saldo_awal')
            ->value('saldo_awal') ?? 0;

        $transactions = JournalEntryItem::with('journalEntry')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
            ->where('journal_entry_items.chart_of_account_id', $accountId)
            ->whereYear('journal_entries.transaction_date', $year)
            ->whereMonth('journal_entries.transaction_date', $month)
            ->where('journal_entries.status', 'posted')
            ->select('journal_entry_items.*')
            ->orderBy('journal_entries.transaction_date')
            ->orderBy('journal_entries.id')
            ->get();

        $monthNames = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ];
        $period  = ($monthNames[$month] ?? $month) . ' ' . $year;
        $appName    = auth()->user()->nama_perusahaan ?? config('app.company_name', config('app.name', 'Perusahaan'));
        $appAddress = auth()->user()->alamat_perusahaan ?? '';

        $pdf = Pdf::loadView('reports.ledger_pdf', [
            'transactions'   => $transactions,
            'openingBalance' => $openingBalance,
            'period'         => $period,
            'appName'        => $appName,
            'appAddress'     => $appAddress,
            'account'        => $account,
            'year'           => $year,
            'month'          => $month,
        ])->setPaper('a4', 'portrait');

        $suffix   = '-akun-' . preg_replace('/\W+/', '-', strtolower($account->code ?? $account->account_name));
        $filename = 'buku-besar' . $suffix . '-' . $year . '-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '.pdf';

        return $pdf->stream($filename);
    }
}