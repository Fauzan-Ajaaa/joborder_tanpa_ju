<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntryItem;
use App\Models\AccountPeriodBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        $accountId = $request->input('account_id');

        $accounts = ChartOfAccount::orderBy('code')->get();
        $journalItems = collect();
        $selectedAccount = null;
        $saldoAwal = 0;

        $periodDate = Carbon::create($year, $month, 1)->startOfDay();
        $prevPeriodDate = $periodDate->copy()->subMonth();

        // Cek apakah periode ini sudah diposting
        $isPosted = false;
        if ($accountId) {
            $isPosted = AccountPeriodBalance::where('chart_of_account_id', $accountId)
                ->where('period', $periodDate->format('Y-m-d'))
                ->where('is_posted', true)
                ->exists();
        }

        // Cek apakah periode sebelumnya sudah diposting (untuk saldo awal)
        $prevPosted = AccountPeriodBalance::where('period', $prevPeriodDate->format('Y-m-d'))
            ->where('is_posted', true)
            ->exists();

        if ($accountId) {
            $selectedAccount = ChartOfAccount::find($accountId);
            $saldoAwal = $selectedAccount->getOpeningBalanceForPeriod($month, $year);

            // Query transaksi dalam periode
            $journalItems = JournalEntryItem::with(['journalEntry', 'chartOfAccount'])
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_items.journal_entry_id')
                ->where('journal_entry_items.chart_of_account_id', $accountId)
                ->whereMonth('journal_entries.transaction_date', $month)
                ->whereYear('journal_entries.transaction_date', $year)
                ->where('journal_entries.status', 'posted')
                ->select('journal_entry_items.*')
                ->orderBy('journal_entries.transaction_date')
                ->orderBy('journal_entries.id')
                ->get();

            // Hitung running balance
            $runningBalance = $saldoAwal;
            foreach ($journalItems as $item) {
                $runningBalance += $item->debit - $item->credit;
                $item->running_balance = $runningBalance;
            }
        }

        return view('reports.ledger', compact(
            'journalItems', 'accounts', 'accountId', 'month', 'year',
            'selectedAccount', 'saldoAwal', 'isPosted', 'prevPosted'
        ));
    }

    /**
     * Posting saldo akhir semua akun untuk periode tertentu
     */
    public function post(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        $periodDate = Carbon::create($year, $month, 1)->startOfDay();
        $endOfPeriod = $periodDate->copy()->endOfMonth();
        $prevPeriodDate = $periodDate->copy()->subMonth();
        $nextPeriodDate = $periodDate->copy()->addMonth();

        DB::transaction(function () use ($periodDate, $nextPeriodDate, $month, $year) {
            $accounts = ChartOfAccount::where('is_active', true)->get();

            foreach ($accounts as $account) {
                // Gunakan method baru untuk hitung saldo awal (lebih robust)
                $saldoAwal = $account->getOpeningBalanceForPeriod($month, $year);

                // Hitung total debit/kredit periode ini
                $totals = JournalEntryItem::where('chart_of_account_id', $account->id)
                    ->whereHas('journalEntry', fn($q) => $q
                        ->where('status', 'posted')
                        ->whereMonth('transaction_date', $month)
                        ->whereYear('transaction_date', $year))
                    ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                    ->first();

                $totalDebit = (float)($totals->total_debit ?? 0);
                $totalCredit = (float)($totals->total_credit ?? 0);
                
                // Hitung ending balance berdasarkan normal balance position
                if ($account->normal_balance_position === 'debit') {
                    $endingBalance = $saldoAwal + $totalDebit - $totalCredit;
                } else {
                    $endingBalance = $saldoAwal + $totalCredit - $totalDebit;
                }

                // Post saldo akhir periode ini
                AccountPeriodBalance::updateOrCreate(
                    ['chart_of_account_id' => $account->id, 'period' => $periodDate->format('Y-m-d')],
                    [
                        'ending_balance' => $endingBalance,
                        'total_debit' => $totalDebit,
                        'total_credit' => $totalCredit,
                        'is_posted' => true,
                        'posted_at' => now(),
                    ]
                );

                // PENTING: Pindahkan HANYA saldo akhir (line terakhir) ke saldo awal bulan berikutnya
                // Bukan saldo awal, tapi saldo setelah semua mutasi (saldo akhir)
                AccountPeriodBalance::updateOrCreate(
                    ['chart_of_account_id' => $account->id, 'period' => $nextPeriodDate->format('Y-m-d')],
                    [
                        'ending_balance' => $endingBalance, // Saldo akhir bulan ini = saldo awal bulan depan
                        'is_posted' => false, // Belum diposting untuk bulan depan
                    ]
                );
            }
        });

        return redirect()->route('reports.buku-besar', ['month' => $month, 'year' => $year])
            ->with('success', "Buku besar periode {$month}/{$year} berhasil diposting. Saldo akhir akan menjadi saldo awal bulan berikutnya.");
    }

    public function checkPosted(Request $request)
    {
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        
        $periodDate = Carbon::create($year, $month, 1)->format('Y-m-d');
        
        $isPosted = AccountPeriodBalance::where('period', $periodDate)
            ->where('is_posted', true)
            ->where('company_id', auth()->user()->company_id)
            ->exists();
        
        return response()->json(['is_posted' => $isPosted]);
    }

    public function postManual(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:chart_of_accounts,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2000,2099',
            'balance' => 'required|numeric',
        ]);

        $accountId = $request->account_id;
        $month = $request->month;
        $year = $request->year;
        $endingBalance = $request->balance;

        $periodDate = Carbon::create($year, $month, 1)->startOfDay();
        $nextPeriodDate = $periodDate->copy()->addMonth();

        DB::transaction(function () use ($accountId, $periodDate, $nextPeriodDate, $endingBalance, $month, $year) {
            // Ambil mutasi asli jika ada untuk kelengkapan data
            $totals = JournalEntryItem::where('chart_of_account_id', $accountId)
                ->whereHas('journalEntry', fn($q) => $q
                    ->where('status', 'posted')
                    ->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year))
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                ->first();

            // Post saldo akhir periode ini
            AccountPeriodBalance::updateOrCreate(
                ['chart_of_account_id' => $accountId, 'period' => $periodDate->format('Y-m-d')],
                [
                    'ending_balance' => $endingBalance,
                    'total_debit' => (float)($totals->total_debit ?? 0),
                    'total_credit' => (float)($totals->total_credit ?? 0),
                    'is_posted' => true,
                    'posted_at' => now(),
                ]
            );

            // PENTING: Pindahkan HANYA saldo akhir (line terakhir) ke saldo awal bulan berikutnya
            // Bukan saldo awal, tapi saldo setelah semua mutasi (saldo akhir)
            AccountPeriodBalance::updateOrCreate(
                ['chart_of_account_id' => $accountId, 'period' => $nextPeriodDate->format('Y-m-d')],
                [
                    'ending_balance' => $endingBalance, // Saldo akhir bulan ini = saldo awal bulan depan
                    'is_posted' => false, // Belum diposting untuk bulan depan
                ]
            );
        });

        return redirect()->back()->with('success', 'Posting manual saldo akhir berhasil disimpan.');
    }
}