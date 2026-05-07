<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        // Auto-ensure basic COA exists setiap kali halaman COA dibuka
        ChartOfAccount::ensureBasicCoaExists();
        
        $accounts = ChartOfAccount::orderBy('code')->get();
        
        // Kelompokkan akun berdasarkan header (2 digit)
        $groupedAccounts = $this->groupAccountsByHeader($accounts);
        
        // Filter berdasarkan header jika ada
        $selectedHeader = $request->get('header');
        
        return view('chart-of-accounts.index', [
            'accounts' => $accounts,
            'groupedAccounts' => $groupedAccounts,
            'selectedHeader' => $selectedHeader
        ]);
    }
    
    private function groupAccountsByHeader($accounts)
    {
        $grouped = [];
        
        foreach ($accounts as $account) {
            // Tentukan header berdasarkan 2 digit pertama
            $headerCode = substr($account->code, 0, 2);
            
            // Cari akun header
            $headerAccount = $accounts->where('code', $headerCode)->first();
            
            if ($headerAccount) {
                if (!isset($grouped[$headerCode])) {
                    $grouped[$headerCode] = [
                        'header' => $headerAccount,
                        'children' => []
                    ];
                }
                
                // Tambahkan akun ke grup jika bukan header itu sendiri
                if ($account->code !== $headerCode) {
                    $grouped[$headerCode]['children'][] = $account;
                }
            }
        }
        
        return $grouped;
    }

    public function create()
    {
        return view('chart-of-accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'description' => 'nullable|string|max:500',
            'balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['code'] = ChartOfAccount::generateAccountCode($validated['account_type']);
        $validated['opening_balance'] = $validated['balance'] ?? 0;
        
        // Determine normal balance position based on account type
        switch ($validated['account_type']) {
            case 'asset':
            case 'expense':
                $validated['normal_balance_position'] = 'debit';
                break;
            case 'liability':
            case 'equity':
            case 'revenue':
                $validated['normal_balance_position'] = 'credit';
                break;
            default:
                $validated['normal_balance_position'] = 'debit';
                break;
        }
        
        // Map account_type to account_group_name
        $accountGroupNames = [
            'asset' => 'Aset',
            'liability' => 'Kewajiban', 
            'equity' => 'Modal',
            'revenue' => 'Pendapatan',
            'expense' => 'Beban'
        ];
        $validated['account_group_name'] = $accountGroupNames[$validated['account_type']] ?? 'Lainnya';
        
        // Remove account_type and balance from validated as they're not in the database
        unset($validated['account_type']);
        unset($validated['balance']);

        ChartOfAccount::create($validated);

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function show(ChartOfAccount $chartOfAccount)
    {
        return view('chart-of-accounts.show', compact('chartOfAccount'));
    }

    public function edit(ChartOfAccount $chartOfAccount)
    {
        return view('chart-of-accounts.edit', compact('chartOfAccount'));
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {
        $validated = $request->validate([
            'normal_balance_position' => 'required|string|in:debit,credit',
            'opening_balance' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
        ]);

        // Pastikan opening_balance selalu diupdate
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;

        // Force update opening_balance secara langsung
        $chartOfAccount->opening_balance = $validated['opening_balance'];
        $chartOfAccount->normal_balance_position = $validated['normal_balance_position'];
        $chartOfAccount->description = $validated['description'] ?? null;
        $chartOfAccount->save();

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(ChartOfAccount $chartOfAccount)
    {
        // Check if account has journal entries
        if ($chartOfAccount->journalEntryLines()->exists()) {
            return back()->withErrors(['error' => 'Akun tidak bisa dihapus karena sudah digunakan dalam transaksi.']);
        }

        $chartOfAccount->delete();

        return redirect()->route('chart-of-accounts.index')
            ->with('success', 'Akun berhasil dihapus.');
    }

    public function manageBalance(ChartOfAccount $chartOfAccount)
    {
        // Get existing balances for this account
        $balances = \App\Models\AccountPeriodBalance::where('chart_of_account_id', $chartOfAccount->id)
            ->orderBy('period', 'desc')
            ->get();

        // Get current month/year
        $currentMonth = now()->month;
        $currentYear = now()->year;

        return view('chart-of-accounts.manage-balance', compact('chartOfAccount', 'balances', 'currentMonth', 'currentYear'));
    }

    public function storeBalance(Request $request, ChartOfAccount $chartOfAccount)
    {
        $validated = $request->validate([
            'period_month' => 'required|date_format:Y-m',
            'balance' => 'required|numeric|min:0',
        ]);

        // Parse the month input (format: YYYY-MM)
        $selectedDate = \Carbon\Carbon::createFromFormat('Y-m', $validated['period_month'])->startOfMonth();
        $currentDate = now()->startOfMonth();

        // Validate that the selected period is before current month
        if ($selectedDate->gte($currentDate)) {
            return back()->withErrors(['error' => 'Hanya bisa input saldo untuk bulan sebelum bulan ini.'])->withInput();
        }

        $balance = $validated['balance'];

        // Determine debit/credit based on normal balance position
        if ($chartOfAccount->normal_balance_position === 'debit') {
            $debitBalance = $balance;
            $creditBalance = 0;
        } else {
            $debitBalance = 0;
            $creditBalance = $balance;
        }

        // Simpan untuk periode yang dipilih sebagai saldo awal
        $periodDate = $selectedDate->format('Y-m-d');

        \App\Models\AccountPeriodBalance::updateOrCreate(
            [
                'chart_of_account_id' => $chartOfAccount->id,
                'period' => $periodDate,
                'company_id' => auth()->user()->company_id,
            ],
            [
                'ending_balance' => $balance,
                'total_debit' => 0, // Manual input, bukan dari transaksi
                'total_credit' => 0, // Manual input, bukan dari transaksi
                'is_posted' => true,
                'posted_at' => now(),
            ]
        );

        return redirect()->route('chart-of-accounts.manage-balance', $chartOfAccount)
            ->with('success', 'Saldo awal ' . $selectedDate->format('F Y') . ' berhasil disimpan.');
    }

    public function editBalance(ChartOfAccount $chartOfAccount, $balanceId)
    {
        $balance = \App\Models\AccountPeriodBalance::findOrFail($balanceId);
        
        // Verify this balance belongs to this account
        if ($balance->chart_of_account_id != $chartOfAccount->id) {
            abort(403);
        }

        return view('chart-of-accounts.edit-balance', compact('chartOfAccount', 'balance'));
    }

    public function updateBalance(Request $request, ChartOfAccount $chartOfAccount, $balanceId)
    {
        $balance = \App\Models\AccountPeriodBalance::findOrFail($balanceId);
        
        // Verify this balance belongs to this account
        if ($balance->chart_of_account_id != $chartOfAccount->id) {
            abort(403);
        }

        $validated = $request->validate([
            'balance' => 'required|numeric|min:0',
        ]);

        $newBalance = $validated['balance'];

        // Update balance
        $balance->ending_balance = $newBalance;
        $balance->save();

        return redirect()->route('chart-of-accounts.manage-balance', $chartOfAccount)
            ->with('success', 'Saldo berhasil diupdate.');
    }

    public function bulkBalance(Request $request)
    {
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        
        // Get selected period or default to last month
        $selectedPeriod = $request->input('period_month');
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Load existing balances if period is selected
        $existingBalances = [];
        if ($selectedPeriod) {
            $periodDate = \Carbon\Carbon::createFromFormat('Y-m', $selectedPeriod)->startOfMonth()->format('Y-m-d');
            
            $balances = \App\Models\AccountPeriodBalance::where('period', $periodDate)
                ->where('company_id', auth()->user()->company_id)
                ->get()
                ->keyBy('chart_of_account_id');
            
            foreach ($balances as $accountId => $balance) {
                $existingBalances[$accountId] = $balance->ending_balance;
            }
        }

        return view('chart-of-accounts.bulk-balance', compact('accounts', 'currentMonth', 'currentYear', 'selectedPeriod', 'existingBalances'));
    }

    public function storeBulkBalance(Request $request)
    {
        $validated = $request->validate([
            'period_month' => 'required|date_format:Y-m',
            'balances' => 'required|array',
            'balances.*' => 'nullable|numeric|min:0',
        ]);

        // Parse the month input (format: YYYY-MM)
        $selectedDate = \Carbon\Carbon::createFromFormat('Y-m', $validated['period_month'])->startOfMonth();
        $currentDate = now()->startOfMonth();

        // Validate that the selected period is before current month
        if ($selectedDate->gte($currentDate)) {
            return back()->withErrors(['error' => 'Hanya bisa input saldo untuk bulan sebelum bulan ini.'])->withInput();
        }

        $periodDate = $selectedDate->format('Y-m-d');
        $count = 0;

        foreach ($validated['balances'] as $accountId => $balance) {
            if ($balance === null || $balance === '') {
                continue; // Skip empty balances
            }

            $account = ChartOfAccount::find($accountId);
            if (!$account) {
                continue;
            }

            \App\Models\AccountPeriodBalance::updateOrCreate(
                [
                    'chart_of_account_id' => $accountId,
                    'period' => $periodDate,
                    'company_id' => auth()->user()->company_id,
                ],
                [
                    'ending_balance' => $balance,
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'is_posted' => true,
                    'posted_at' => now(),
                ]
            );

            $count++;
        }

        return redirect()->route('chart-of-accounts.bulk-balance')
            ->with('success', "Berhasil menyimpan saldo awal untuk {$count} akun pada periode " . $selectedDate->format('F Y') . '.');
    }
}
