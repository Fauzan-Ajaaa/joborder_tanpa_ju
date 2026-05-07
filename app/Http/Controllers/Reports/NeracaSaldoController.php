<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JournalEntryItem;
use App\Models\ChartOfAccount;
use Illuminate\Support\Carbon;

class NeracaSaldoController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->integer('year') ?: now()->year);
        $month = (int) ($request->integer('month') ?: now()->month);
        [$trialBalance, $isBalanced, $totalDebit, $totalCredit] = $this->buildTrialBalance($year, $month);

        return view('reports.neraca_saldo', [
            'year' => $year,
            'month' => $month,
            'period' => [
                'year' => $year,
                'month' => $month,
                'end_date' => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d'),
                'formatted' => Carbon::create($year, $month)->translatedFormat('F Y')
            ],
            'trialBalance' => $trialBalance,
            'isBalanced' => $isBalanced,
            'difference' => $totalDebit - $totalCredit
        ]);
    }

    public function print(Request $request)
    {
        $year = (int) ($request->integer('year') ?: now()->year);
        $month = (int) ($request->integer('month') ?: now()->month);
        [$trialBalance, $isBalanced, $totalDebit, $totalCredit] = $this->buildTrialBalance($year, $month);

        return view('reports.neraca_saldo_print', [
            'year'       => $year,
            'month'      => $month,
            'period'     => [
                'year'      => $year,
                'month'     => $month,
                'end_date'  => Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d'),
                'formatted' => Carbon::create($year, $month)->translatedFormat('F Y')
            ],
            'trialBalance' => $trialBalance,
            'isBalanced'   => $isBalanced,
            'difference'   => $totalDebit - $totalCredit,
            'appName'      => auth()->user()->nama_perusahaan ?? config('app.name', 'Perusahaan'),
            'appAddress'   => auth()->user()->alamat_perusahaan ?? '',
        ]);
    }

    private function buildTrialBalance(int $year, int $month): array
    {
        $periodDate = Carbon::create($year, $month, 1);
        
        // Ambil SEMUA akun aktif sesuai urutan kode
        $accounts = ChartOfAccount::where('is_active', true)
            ->orderBy('code')
            ->get();

        $accountBalances = [];
        
        foreach ($accounts as $account) {
            $saldoAwal = $account->getOpeningBalanceForPeriod($month, $year);
            
            // Hitung mutasi periode ini
            $totals = JournalEntryItem::where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', fn($q) => $q
                    ->where('status', 'posted')
                    ->whereMonth('transaction_date', $month)
                    ->whereYear('transaction_date', $year))
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                ->first();
            
            $totalDebitMutation = (float)($totals->total_debit ?? 0);
            $totalCreditMutation = (float)($totals->total_credit ?? 0);
            
            // Hitung saldo akhir
            if ($account->normal_balance_position === 'debit') {
                $finalBalance = $saldoAwal + $totalDebitMutation - $totalCreditMutation;
            } else {
                $finalBalance = $saldoAwal + $totalCreditMutation - $totalDebitMutation;
            }
            
            // Tentukan posisi debit/kredit di neraca saldo
            $debitAmount = 0;
            $creditAmount = 0;
            
            // Cek apakah akun Akumulasi Penyusutan (contra account)
            $isContraAccount = stripos($account->account_name, 'akumulasi') !== false;
            
            if ($isContraAccount) {
                // Akumulasi Penyusutan selalu di kredit
                if ($finalBalance != 0) {
                    $creditAmount = abs($finalBalance);
                }
            } else {
                // Akun normal sesuai normal_balance_position
                if ($account->normal_balance_position === 'debit') {
                    if ($finalBalance >= 0) {
                        $debitAmount = $finalBalance;
                    } else {
                        $creditAmount = abs($finalBalance);
                    }
                } else {
                    if ($finalBalance >= 0) {
                        $creditAmount = $finalBalance;
                    } else {
                        $debitAmount = abs($finalBalance);
                    }
                }
            }
            
            // SELALU tampilkan semua akun, termasuk yang saldo 0
            $accountBalances[] = [
                'code'   => $account->code,
                'name'   => $account->account_name,
                'debit'  => $debitAmount,
                'credit' => $creditAmount,
            ];
        }

        $totalDebit = $totalCredit = 0;
        foreach ($accountBalances as $balance) {
            $totalDebit  += $balance['debit'];
            $totalCredit += $balance['credit'];
        }

        $trialBalance = [
            'accounts'     => $accountBalances,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
        ];

        return [$trialBalance, abs($totalDebit - $totalCredit) < 0.5, $totalDebit, $totalCredit];
    }
}
