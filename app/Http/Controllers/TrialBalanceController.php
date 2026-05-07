<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\JournalEntryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialBalanceController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $type = $request->get('type', 'before_adjustment'); // before_adjustment, after_adjustment, final
        
        $trialBalance = $this->calculateTrialBalance($month, $year, $type);
        
        return view('trial-balance.index', compact('trialBalance', 'month', 'year', 'type'));
    }
    
    private function calculateTrialBalance(int $month, int $year, string $type)
    {
        $periodDate = \Carbon\Carbon::create($year, $month, 1);
        
        // Ambil semua akun aktif
        $accounts = ChartOfAccount::where('is_active', true)
            ->orderBy('code')
            ->get();
        
        $results = [];
        $totalDebit = 0;
        $totalCredit = 0;
        
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
            
            // Skip akun dengan saldo 0 dan tidak ada mutasi
            if (abs($finalBalance) < 0.01 && $totalDebitMutation == 0 && $totalCreditMutation == 0) {
                continue;
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
            
            $results[] = [
                'code' => $account->code,
                'name' => $account->account_name,
                'group' => $account->account_group_name,
                'normal_balance' => $account->normal_balance_position,
                'debit' => $debitAmount,
                'credit' => $creditAmount,
            ];
            
            $totalDebit += $debitAmount;
            $totalCredit += $creditAmount;
        }
        
        return [
            'accounts' => collect($results),
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01
        ];
    }
    
    public function print(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $type = $request->get('type', 'before_adjustment');
        
        $trialBalance = $this->calculateTrialBalance($month, $year, $type);
        
        return view('trial-balance.print', compact('trialBalance', 'month', 'year', 'type'));
    }
}
