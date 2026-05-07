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

        $items = JournalEntryItem::query()
            ->with(['journalEntry', 'chartOfAccount'])
            ->whereHas('journalEntry', fn ($q) => $q
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->where('status', 'posted'))
            ->get();

        $sections = [
            'revenue' => ['total' => 0, 'accounts' => []],
            'expense' => ['total' => 0, 'accounts' => []],
        ];

        foreach ($items as $item) {
        $type = strtolower($item->chartOfAccount->account_group_name ?? '');
        
        // Map account group names to income statement sections
        $sectionMap = [
            'pendapatan' => 'revenue',
            'revenue' => 'revenue',
            'beban' => 'expense',
            'expense' => 'expense'
        ];
        
        $section = $sectionMap[$type] ?? null;
        if (!$section) continue;
        if (!isset($sections[$section])) continue;

            $accountId = $item->chart_of_account_id;
            $sections[$section]['accounts'][$accountId] ??= [
                'code'   => $item->chartOfAccount->code,
                'name'   => $item->chartOfAccount->account_name,
                'amount' => 0,
            ];

            $amount = $section === 'revenue'
                ? ($item->credit - $item->debit)
                : ($item->debit - $item->credit);

            $sections[$section]['accounts'][$accountId]['amount'] += $amount;
            $sections[$section]['total'] += $amount;
        }

        $revenues = array_values($sections['revenue']['accounts']);
        $expenses = array_values($sections['expense']['accounts']);

        $totalRevenue = $sections['revenue']['total'];
        $totalExpense = $sections['expense']['total'];
        $netIncome    = $totalRevenue - $totalExpense;

        return view('reports.income_statement', compact(
            'year', 'month',
            'revenues', 'expenses',
            'totalRevenue', 'totalExpense', 'netIncome'
        ));
    }

    public function pdf(Request $request)
    {
        $year  = (int) ($request->integer('year') ?: now()->year);
        $month = (int) ($request->integer('month') ?: now()->month);

        // Reuse same logic
        $items = JournalEntryItem::query()
            ->with(['journalEntry', 'chartOfAccount'])
            ->whereHas('journalEntry', fn ($q) => $q
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->where('status', 'posted'))
            ->get();

        $sections = ['revenue' => ['total' => 0, 'accounts' => []], 'expense' => ['total' => 0, 'accounts' => []]];
        $sectionMap = ['pendapatan' => 'revenue', 'revenue' => 'revenue', 'beban' => 'expense', 'expense' => 'expense'];

        foreach ($items as $item) {
            $type = strtolower($item->chartOfAccount->account_group_name ?? '');
            $section = $sectionMap[$type] ?? null;
            if (!$section) continue;
            $accountId = $item->chart_of_account_id;
            $sections[$section]['accounts'][$accountId] ??= ['code' => $item->chartOfAccount->code, 'name' => $item->chartOfAccount->account_name, 'amount' => 0];
            $amount = $section === 'revenue' ? ($item->credit - $item->debit) : ($item->debit - $item->credit);
            $sections[$section]['accounts'][$accountId]['amount'] += $amount;
            $sections[$section]['total'] += $amount;
        }

        $revenues     = array_values($sections['revenue']['accounts']);
        $expenses     = array_values($sections['expense']['accounts']);
        $totalRevenue = $sections['revenue']['total'];
        $totalExpense = $sections['expense']['total'];
        $netIncome    = $totalRevenue - $totalExpense;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.income_statement_pdf', compact(
            'year', 'month', 'revenues', 'expenses', 'totalRevenue', 'totalExpense', 'netIncome'
        ) + [
            'company' => auth()->user()->nama_perusahaan ?? 'Perusahaan',
            'address' => auth()->user()->alamat_perusahaan ?? '',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-laba-rugi-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.pdf');
    }
}