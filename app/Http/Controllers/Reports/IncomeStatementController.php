<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;

class IncomeStatementController extends Controller
{
    public function index(Request $request)
    {
        $year  = (int) ($request->integer('year') ?: now()->year);
        $month = (int) ($request->integer('month') ?: now()->month);

        // Get all revenue, COGS (HPP), and expense accounts
        $accounts = \App\Models\ChartOfAccount::where('is_active', true)
            ->whereIn('account_group_name', ['Pendapatan', 'Revenue', 'Beban', 'Expense'])
            ->orderBy('code')
            ->get();

        $sections = [
            'revenue' => ['total' => 0, 'accounts' => []],
            'cogs' => ['total' => 0, 'accounts' => []],  // HPP/COGS
            'expense' => ['total' => 0, 'accounts' => []],
        ];

        foreach ($accounts as $account) {
            $type = strtolower($account->account_group_name ?? '');
            
            // Determine section
            $section = null;
            if (in_array($type, ['pendapatan', 'revenue'])) {
                $section = 'revenue';
            } elseif (stripos($account->code, '59') === 0 || stripos($account->account_name, 'harga pokok') !== false) {
                // HPP/COGS - akun kode 59 atau nama mengandung "Harga Pokok"
                $section = 'cogs';
            } elseif (in_array($type, ['beban', 'expense'])) {
                $section = 'expense';
            }
            
            if (!$section) continue;

            // Get opening balance for the period
            $saldoAwal = $account->getOpeningBalanceForPeriod($month, $year);
            
            // Get period mutations
            $totals = \App\Models\JournalEntryItem::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', fn($q) => $q
                    ->where('status', 'posted')
                    ->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year))
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                ->first();
            
            $totalDebit = (float)($totals->total_debit ?? 0);
            $totalCredit = (float)($totals->total_credit ?? 0);
            
            // Calculate final balance based on section
            if ($section === 'revenue') {
                // Revenue: normal credit balance -> Saldo Awal + Credit - Debit
                $finalBalance = $saldoAwal + $totalCredit - $totalDebit;
            } else {
                // COGS & Expense: normal debit balance -> Saldo Awal + Debit - Credit
                $finalBalance = $saldoAwal + $totalDebit - $totalCredit;
            }
            
            // Only include accounts with non-zero balance
            if (abs($finalBalance) > 0.01) {
                $sections[$section]['accounts'][$account->id] = [
                    'code'   => $account->code,
                    'name'   => $account->account_name,
                    'amount' => abs($finalBalance),
                ];
                $sections[$section]['total'] += abs($finalBalance);
            }
        }

        $revenues = array_values($sections['revenue']['accounts']);
        $cogs = array_values($sections['cogs']['accounts']);
        $expenses = array_values($sections['expense']['accounts']);

        $totalRevenue = $sections['revenue']['total'];
        $totalCOGS = $sections['cogs']['total'];
        $totalExpense = $sections['expense']['total'];
        
        $grossProfit = $totalRevenue - $totalCOGS;
        $netIncome = $grossProfit - $totalExpense;

        return view('reports.income_statement', compact(
            'year', 'month',
            'revenues', 'cogs', 'expenses',
            'totalRevenue', 'totalCOGS', 'totalExpense',
            'grossProfit', 'netIncome'
        ));
    }

    public function pdf(Request $request)
    {
        $year  = (int) ($request->integer('year') ?: now()->year);
        $month = (int) ($request->integer('month') ?: now()->month);

        // Get all revenue, COGS (HPP), and expense accounts
        $accounts = \App\Models\ChartOfAccount::where('is_active', true)
            ->whereIn('account_group_name', ['Pendapatan', 'Revenue', 'Beban', 'Expense'])
            ->orderBy('code')
            ->get();

        $sections = [
            'revenue' => ['total' => 0, 'accounts' => []],
            'cogs' => ['total' => 0, 'accounts' => []],
            'expense' => ['total' => 0, 'accounts' => []],
        ];

        foreach ($accounts as $account) {
            $type = strtolower($account->account_group_name ?? '');
            
            // Determine section
            $section = null;
            if (in_array($type, ['pendapatan', 'revenue'])) {
                $section = 'revenue';
            } elseif (stripos($account->code, '59') === 0 || stripos($account->account_name, 'harga pokok') !== false) {
                $section = 'cogs';
            } elseif (in_array($type, ['beban', 'expense'])) {
                $section = 'expense';
            }
            
            if (!$section) continue;

            $saldoAwal = $account->getOpeningBalanceForPeriod($month, $year);
            
            $totals = \App\Models\JournalEntryItem::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', fn($q) => $q
                    ->where('status', 'posted')
                    ->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year))
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                ->first();
            
            $totalDebit = (float)($totals->total_debit ?? 0);
            $totalCredit = (float)($totals->total_credit ?? 0);
            
            if ($section === 'revenue') {
                $finalBalance = $saldoAwal + $totalCredit - $totalDebit;
            } else {
                $finalBalance = $saldoAwal + $totalDebit - $totalCredit;
            }
            
            if (abs($finalBalance) > 0.01) {
                $sections[$section]['accounts'][$account->id] = [
                    'code'   => $account->code,
                    'name'   => $account->account_name,
                    'amount' => abs($finalBalance),
                ];
                $sections[$section]['total'] += abs($finalBalance);
            }
        }

        $revenues = array_values($sections['revenue']['accounts']);
        $cogs = array_values($sections['cogs']['accounts']);
        $expenses = array_values($sections['expense']['accounts']);
        $totalRevenue = $sections['revenue']['total'];
        $totalCOGS = $sections['cogs']['total'];
        $totalExpense = $sections['expense']['total'];
        $grossProfit = $totalRevenue - $totalCOGS;
        $netIncome = $grossProfit - $totalExpense;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.income_statement_pdf', compact(
            'year', 'month', 'revenues', 'cogs', 'expenses', 
            'totalRevenue', 'totalCOGS', 'totalExpense', 'grossProfit', 'netIncome'
        ) + [
            'company' => auth()->user()->nama_perusahaan ?? 'Perusahaan',
            'address' => auth()->user()->alamat_perusahaan ?? '',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-laba-rugi-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.pdf');
    }
}