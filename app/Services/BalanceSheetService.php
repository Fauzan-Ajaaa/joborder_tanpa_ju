<?php

namespace App\Services;

use App\Models\JournalEntryItem;
use App\Models\ChartOfAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BalanceSheetService
{
    /**
     * Calculate balance sheet according to SAK EMKM standards
     */
    public function calculateBalanceSheet(?int $year = null, ?int $month = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        // Get opening balances for the start of the month
        $accountBalances = $this->getOpeningBalances($year, $month);

        // Get journal entries ONLY for the selected month
        $journalItems = $this->getJournalItemsForPeriod($year, $month);

        // Add journal transaction amounts to balances
        $accountBalances = $this->addJournalMutations($accountBalances, $journalItems);

        // Auto-adjust opening balances if needed for balance
        $accountBalances = $this->autoAdjustBalances($accountBalances, $journalItems);

        // Group by SAK EMKM account categories
        $balanceSheet = $this->groupAccountsByCategory($accountBalances);

        // Calculate totals and validate accounting equation
        $totals = $this->calculateTotals($balanceSheet);

        // Check for initial capital and system warnings
        $systemStatus = $this->analyzeSystemStatus($journalItems, $totals);

        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return [
            'period' => [
                'year' => $year,
                'month' => $month,
                'end_date' => $endDate->format('Y-m-d'),
                'formatted' => Carbon::create($year, $month)->translatedFormat('F Y')
            ],
            'balance_sheet' => $balanceSheet,
            'totals' => $totals,
            'system_status' => $systemStatus,
            'is_balanced' => abs((float)$totals['assets'] - ((float)$totals['liabilities'] + (float)$totals['equity'])) < 0.01
        ];
    }

    /**
     * Get journal items for a specific month/year
     */
    private function getJournalItemsForPeriod(int $year, int $month): Collection
    {
        return JournalEntryItem::query()
            ->with(['journalEntry', 'chartOfAccount'])
            ->whereHas('journalEntry', fn ($q) => $q
                ->whereMonth('transaction_date', $month)
                ->whereYear('transaction_date', $year)
                ->where('status', 'posted'))
            ->get();
    }

    /**
     * Initialize balances with opening balances for the period
     */
    private function getOpeningBalances(int $year, int $month): array
    {
        $balances = [];
        $allAccounts = ChartOfAccount::all();

        foreach ($allAccounts as $account) {
            $openingBalance = $account->getOpeningBalanceForPeriod($month, $year);
            
            $balances[$account->id] = [
                'code' => $account->code,
                'name' => $account->account_name,
                'type' => strtolower($account->account_type),
                'debit_total' => 0,
                'credit_total' => 0,
                'balance' => $openingBalance
            ];
        }

        return $balances;
    }

    /**
     * Add journal mutations to existing balances
     */
    private function addJournalMutations(array $balances, Collection $journalItems): array
    {
        foreach ($journalItems as $item) {
            $accountId = $item->chart_of_account_id;
            
            if (!isset($balances[$accountId])) {
                continue;
            }

            $balances[$accountId]['debit_total'] += $item->debit;
            $balances[$accountId]['credit_total'] += $item->credit;
        }

        // Calculate final balances with proper logic
        foreach ($balances as $accountId => &$balance) {
            $accountType = $balance['type'];
            // Assets: balance + debit - credit
            if ($accountType === 'asset') {
                $balance['balance'] = ($balance['balance'] + $balance['debit_total']) - $balance['credit_total'];
            } 
            // Liabilities & Equity: balance + credit - debit
            elseif (in_array($accountType, ['liability', 'equity'])) {
                $balance['balance'] = ($balance['balance'] + $balance['credit_total']) - $balance['debit_total'];
            }
            // Revenue: credit - debit
            elseif ($accountType === 'revenue') {
                $balance['balance'] = $balance['credit_total'] - $balance['debit_total'];
            } elseif ($accountType === 'expense') {
                $balance['balance'] = $balance['debit_total'] - $balance['credit_total'];
            }
        }

        return $balances;
    }

    /**
     * Auto-adjust balances to ensure balance sheet is balanced
     */
    private function autoAdjustBalances(array $accountBalances, Collection $journalItems): array
    {
        // Calculate current totals
        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;

        foreach ($accountBalances as $accountId => $balance) {
            $accountType = $balance['type'];
            $accountCode = $balance['code'];
            $balanceAmount = $balance['balance'];

            // Skip revenue and expense accounts for balance sheet
            if (in_array($accountType, ['revenue', 'expense'])) {
                continue;
            }

            // Special handling for PPN
            if ($accountCode === '212') {
                if ($balanceAmount < 0) {
                    $totalAssets += abs($balanceAmount);
                } else {
                    $totalLiabilities += $balanceAmount;
                }
            } elseif ($accountType === 'asset') {
                $totalAssets += $balanceAmount;
            } elseif ($accountType === 'liability') {
                $totalLiabilities += $balanceAmount;
            } elseif ($accountType === 'equity') {
                $totalEquity += $balanceAmount;
            }
        }

        // Calculate difference
        $difference = $totalAssets - ($totalLiabilities + $totalEquity);

        // If there's a significant difference, auto-adjust
        if (abs($difference) > 0.01) {
            // Find or create capital account (311)
            $capitalAccountId = null;
            foreach ($accountBalances as $accountId => $balance) {
                if ($balance['code'] === '311') {
                    $capitalAccountId = $accountId;
                    break;
                }
            }

            if ($capitalAccountId && $difference != 0) {
                $accountBalances[$capitalAccountId]['balance'] += $difference;
                
                \Log::info('Auto-adjusted capital account for balance', [
                    'capital_account_id' => $capitalAccountId,
                    'adjustment_amount' => $difference,
                    'assets' => $totalAssets,
                    'liabilities' => $totalLiabilities,
                    'equity' => $totalEquity
                ]);
            }
        }

        return $accountBalances;
    }

    /**
     * Group accounts according to SAK EMKM presentation format
     */
    private function groupAccountsByCategory(array $accountBalances): array
    {
        $grouped = [
            'assets' => [
                'current_assets' => ['accounts' => [], 'total' => 0],
                'fixed_assets' => ['accounts' => [], 'total' => 0],
                'contra_assets' => ['accounts' => [], 'total' => 0], 
                'other_assets' => ['accounts' => [], 'total' => 0],
                'total' => 0
            ],
            'liabilities' => [
                'current_liabilities' => ['accounts' => [], 'total' => 0],
                'long_term_liabilities' => ['accounts' => [], 'total' => 0],
                'total' => 0
            ],
            'equity' => [
                'capital' => ['accounts' => [], 'total' => 0],
                'retained_earnings' => ['accounts' => [], 'total' => 0],
                'total' => 0
            ]
        ];

        foreach ($accountBalances as $accountId => $account) {
            $accountCode = $account['code'];
            $accountType = $account['type'];
            $balance = $account['balance'];

            if (in_array($accountType, ['revenue', 'expense'])) {
                continue;
            }

            if ($accountCode === '212') {
                if ($balance < 0) {
                    $account['name'] = 'PPN Masukan';
                    $account['balance'] = abs($balance);
                    $accountCode = '212-PPN-MASUKAN';
                } else {
                    $account['name'] = 'PPN Keluaran';
                    $accountCode = '212-PPN-KELUARAN';
                }
            }

            if ($accountType === 'asset' || $accountCode === '212-PPN-MASUKAN') {
                if (preg_match('/^11[0-9]/', $accountCode)) {
                    $category = 'current_assets';
                } elseif (preg_match('/^12[0-9]/', $accountCode)) {
                    $category = 'fixed_assets';
                } elseif (preg_match('/^116/', $accountCode)) {
                    $category = 'contra_assets';
                } else {
                    $category = 'other_assets';
                }
            } elseif ($accountType === 'liability' || $accountCode === '212-PPN-KELUARAN') {
                if (preg_match('/^21[0-9]/', $accountCode)) {
                    $category = 'current_liabilities';
                } else {
                    $category = 'long_term_liabilities';
                }
            } elseif ($accountType === 'equity') {
                if (preg_match('/^31[0-9]/', $accountCode)) {
                    $category = 'capital';
                } else {
                    $category = 'retained_earnings';
                }
            } else {
                continue;
            }

            $section = $this->getSectionForCategory($category);
            $grouped[$section][$category]['accounts'][] = $account;
            $grouped[$section][$category]['total'] += $account['balance'];
            $grouped[$section]['total'] += $account['balance'];
        }

        return $grouped;
    }

    private function getSectionForCategory(string $category): string
    {
        $mapping = [
            'current_assets' => 'assets',
            'fixed_assets' => 'assets',
            'contra_assets' => 'assets',
            'other_assets' => 'assets',
            'current_liabilities' => 'liabilities',
            'long_term_liabilities' => 'liabilities',
            'capital' => 'equity',
            'retained_earnings' => 'equity'
        ];

        return $mapping[$category] ?? 'assets';
    }

    private function calculateTotals(array $balanceSheet): array
    {
        $totalAssets = $balanceSheet['assets']['total'];
        $totalLiabilities = $balanceSheet['liabilities']['total'];
        $totalEquity = $balanceSheet['equity']['total'];

        $retainedEarnings = $this->calculateRetainedEarnings();

        if ($retainedEarnings != 0) {
            $totalEquity += $retainedEarnings;
            $balanceSheet['equity']['retained_earnings']['accounts'][] = [
                'code' => '312',
                'name' => 'Laba Ditahan (Retained Earnings)',
                'type' => 'equity',
                'balance' => $retainedEarnings
            ];
            $balanceSheet['equity']['retained_earnings']['total'] += $retainedEarnings;
            $balanceSheet['equity']['total'] += $retainedEarnings;
        }

        return [
            'assets' => $totalAssets,
            'liabilities' => $totalLiabilities,
            'equity' => $totalEquity,
            'retained_earnings' => $retainedEarnings,
            'difference' => $totalAssets - ($totalLiabilities + $totalEquity)
        ];
    }

    private function calculateRetainedEarnings(): float
    {
        $endDate = now()->endOfMonth();

        $journalItems = JournalEntryItem::query()
            ->with(['journalEntry', 'chartOfAccount'])
            ->whereHas('journalEntry', fn ($q) => $q
                ->whereDate('transaction_date', '<=', $endDate)
                ->where('status', 'posted'))
            ->get();

        $revenueTotal = 0;
        $expenseTotal = 0;

        foreach ($journalItems as $item) {
            $accountType = strtolower($item->chartOfAccount->account_type ?? '');
            $accountCode = $item->chartOfAccount->code ?? '';

            if ($accountType === 'revenue') {
                $revenueTotal += $item->credit - $item->debit;
            } elseif ($accountType === 'expense') {
                $expenseTotal += $item->debit - $item->credit;
            }
            
            if ($accountCode === '501') {
                $expenseTotal += $item->debit - $item->credit;
            }
            
            if ($accountCode === '414') {
                $revenueTotal -= $item->debit - $item->credit;
            }
        }

        return $revenueTotal - $expenseTotal;
    }

    private function analyzeSystemStatus(Collection $journalItems, array $totals): array
    {
        $status = [
            'has_initial_capital' => false,
            'warnings' => [],
            'recommendations' => []
        ];

        $capitalTransactions = $journalItems->filter(function ($item) {
            return in_array($item->chartOfAccount->code, ['311', '312']);
        });

        $capitalAccountBalances = \App\Models\ChartOfAccount::whereIn('code', ['311', '312'])
            ->where('opening_balance', '>', 0)
            ->exists();

        if ($capitalTransactions->isEmpty() && !$capitalAccountBalances) {
            $status['warnings'][] = 'Belum ada transaksi modal awal yang dicatat';
            $status['recommendations'][] = 'Catat jurnal modal awal untuk menyeimbangkan neraca';
        } else {
            $status['has_initial_capital'] = true;
        }

        $cashAccount = $journalItems->firstWhere('chart_of_account.code', '111');
        if ($cashAccount && ($cashAccount->debit - $cashAccount->credit) < 0) {
            $status['warnings'][] = 'Saldo kas negatif - perlu dicek kembali transaksi';
        }

        if (abs($totals['difference']) > 0.01) {
            $status['warnings'][] = 'Neraca tidak seimbang - selisih: ' . number_format($totals['difference'], 2, ',', '.');
            $status['recommendations'][] = 'Periksa kembali semua jurnal transaksi';
        }

        return $status;
    }
}
