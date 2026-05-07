<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
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
        
        // Get all accounts for filter, ordered by code (same as master COA)
        $accounts = ChartOfAccount::where(function ($q) {
                // Hanya akun aktif (kalau ada kolom is_active)
                $q->whereNull('is_active')
                  ->orWhere('is_active', true);
            })
            ->orderBy('code')
            ->get();

        // Tahun dinamis
        $firstDate = JournalEntry::min('transaction_date');
        $startYear = $firstDate ? Carbon::parse($firstDate)->year : now()->year - 10;
        $endYear   = now()->year + 3;
        $years     = range($startYear, $endYear);

        $monthNames = [
            1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
            7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
        ];
        $periode = ($monthNames[(int)$month] ?? $month) . ' ' . $year;

        // Calculate totals for IAI SAK compliance
        $totalDebit = $items->sum('debit');
        $totalCredit = $items->sum('credit');

        return view('reports.jurnal_umum', compact('items','accounts','year','month','accountId','periode','years','totalDebit','totalCredit'));
    }
}