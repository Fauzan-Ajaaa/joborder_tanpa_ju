<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCompany;
use Illuminate\Support\Carbon;

class ChartOfAccount extends Model
{
    use HasCompany;

    protected $fillable = [
        'account_code',
        'code',
        'account_name',
        'account_type',
        'account_group_name',
        'description',
        'opening_balance',
        'normal_balance_position',
        'is_active',
        'parent_code',
        'company_id',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function generateAccountCode(string $group): string
    {
        $prefixes = [
            'asset' => '1',
            'liability' => '2', 
            'equity' => '3',
            'revenue' => '4',
            'expense' => '5'
        ];

        $prefix = $prefixes[$group] ?? '1';
        
        $lastAccount = self::where('code', 'like', "{$prefix}%")
            ->where('code', '!=', $prefix . '-0000')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastAccount) {
            $code = $lastAccount->code;
            if (strpos($code, '-') !== false) {
                $parts = explode('-', $code);
                $lastNumber = (int) end($parts);
                $newNumber = $lastNumber + 1;
                return sprintf('%s-%04d', $prefix, $newNumber);
            } else {
                $lastNumber = (int) substr($code, 1);
                $newNumber = $lastNumber + 1;
                return $prefix . $newNumber;
            }
        } else {
            return sprintf('%s-%04d', $prefix, 1000);
        }
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_code', 'code');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_code', 'code');
    }

    public function getCurrentBalance(): float
    {
        $totalDebit = (float)$this->journalEntryLines()->sum('debit');
        $totalCredit = (float)$this->journalEntryLines()->sum('credit');
        
        if ($this->normal_balance_position === 'debit') {
            return (float)((float)$this->opening_balance + $totalDebit - $totalCredit);
        } else {
            return (float)((float)$this->opening_balance + $totalCredit - $totalDebit);
        }
    }

    public function calculateBalance(): float
    {
        $journalSum = $this->journalEntryLines()
            ->selectRaw('SUM(debit - credit) as balance')
            ->first();
        
        $balance = (float)$this->opening_balance + (float)($journalSum->balance ?? 0);
        
        if ($this->normal_balance_position == 'credit') {
            return -$balance;
        }
        
        return $balance;
    }

    /**
     * Mendapatkan saldo awal untuk periode tertentu (bulan/tahun)
     * Prioritas:
     * 1. Saldo terposting di bulan itu sendiri (biasanya input manual 'Saldo Awal' dari COA)
     * 2. Saldo terposting dari bulan sebelumnya (flow saldo)
     * 3. Saldo terposting terakhir sebelum bulan itu
     * 4. Master opening balance COA + transaksi sistem awal
     */
    public function getOpeningBalanceForPeriod(int $month, int $year): float
    {
        $periodDate = Carbon::create($year, $month, 1)->startOfMonth();
        $prevPeriodDate = $periodDate->copy()->subMonth();

        // 1. Cek apakah bulan ini sudah punya entry (dari posting bulan sebelumnya)
        // Entry ini berisi saldo akhir bulan sebelumnya yang menjadi saldo awal bulan ini
        $currentPeriodEntry = AccountPeriodBalance::where('chart_of_account_id', $this->id)
            ->where('period', $periodDate->format('Y-m-d'))
            ->first();

        if ($currentPeriodEntry && $currentPeriodEntry->ending_balance !== null) {
            // Jika ada entry dan punya ending_balance, gunakan itu sebagai saldo awal
            // (ending_balance dari posting bulan sebelumnya = saldo awal bulan ini)
            return (float)$currentPeriodEntry->ending_balance;
        }

        // 2. Cek saldo akhir terposting pada bulan PERSIS sebelumnya
        $prevBalance = AccountPeriodBalance::where('chart_of_account_id', $this->id)
            ->where('period', $prevPeriodDate->format('Y-m-d'))
            ->where('is_posted', true)
            ->first();

        if ($prevBalance) {
            // Saldo akhir bulan lalu = saldo awal bulan ini
            return (float)$prevBalance->ending_balance;
        }

        // 3. Cari postingan TERBARU sebelum bulan ini
        $lastPosted = AccountPeriodBalance::where('chart_of_account_id', $this->id)
            ->where('period', '<', $periodDate->format('Y-m-d'))
            ->where('is_posted', true)
            ->orderBy('period', 'desc')
            ->first();

        if ($lastPosted) {
            // Saldo akhir posting terakhir = saldo awal bulan ini
            return (float)$lastPosted->ending_balance;
        }

        // 4. Jika tidak ada posting sama sekali, gunakan Master COA + Transaksi Sistem
        $openingBalance = (float)($this->opening_balance ?? 0);
        
        // Hitung mutasi transaksi sebelum periode
        $totals = JournalEntryItem::where('chart_of_account_id', $this->id)
            ->whereHas('journalEntry', fn($q) => $q
                ->where('status', 'posted')
                ->where('transaction_date', '<', $periodDate))
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();
        
        $totalDebit = (float)($totals->total_debit ?? 0);
        $totalCredit = (float)($totals->total_credit ?? 0);

        if ($this->normal_balance_position === 'debit') {
            // Akun Debit (1, 5, 6, 7, 8): Debit menambah, Kredit mengurangi
            return (float)($openingBalance + $totalDebit - $totalCredit);
        } else {
            // Akun Kredit (2, 3, 4): Kredit menambah, Debit mengurangi
            return (float)($openingBalance + $totalCredit - $totalDebit);
        }
    }

    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->getCurrentBalance(), 2, ',', '.');
    }

    public function getAllChildren()
    {
        $children = collect();
        foreach ($this->children as $child) {
            $children->push($child);
            $children = $children->merge($child->getAllChildren());
        }
        return $children;
    }

    public function isParent(): bool
    {
        return $this->children()->count() > 0;
    }

    public function isChild(): bool
    {
        return !is_null($this->parent_code);
    }

    public function rawMaterials(): HasMany
    {
        return $this->hasMany(\App\Models\RawMaterial::class, 'chart_of_account_id');
    }

    public function auxiliaryMaterials(): HasMany
    {
        return $this->hasMany(\App\Models\AuxiliaryMaterial::class, 'chart_of_account_id');
    }

    public static function createRawMaterialAccount(string $materialName): ?ChartOfAccount
    {
        return DB::transaction(function () use ($materialName) {
            $parentAccount = self::where('code', '114')->first();
            if (!$parentAccount) return null;

            $lastChild = self::where('code', 'like', '114%')
                ->where('code', '!=', '114')
                ->orderByRaw('CAST(code AS UNSIGNED) DESC')
                ->first();

            if ($lastChild) {
                $newCode = (int)$lastChild->code + 1;
            } else {
                $newCode = 1141;
            }

            while (self::where('code', (string)$newCode)->exists()) {
                $newCode++;
            }

            $data = [
                'code' => (string) $newCode,
                'account_name' => 'Persediaan Bahan Baku ' . $materialName,
                'description' => 'Akun persediaan untuk bahan baku: ' . $materialName,
                'opening_balance' => 0,
                'normal_balance_position' => 'debit',
                'is_active' => true,
                'parent_code' => '114',
            ];

            if (Schema::hasColumn('chart_of_accounts', 'account_code')) {
                $data['account_code'] = (string) $newCode;
            }

            if (Schema::hasColumn('chart_of_accounts', 'account_group_name')) {
                $data['account_group_name'] = $parentAccount->account_group_name;
            }

            return self::create($data);
        });
    }

    public static function createAuxiliaryMaterialAccount(string $materialName): ?ChartOfAccount
    {
        return DB::transaction(function () use ($materialName) {
            $parentAccount = self::where('code', '117')->first();
            if (!$parentAccount) return null;

            $lastChild = self::where('code', 'like', '117%')
                ->where('code', '!=', '117')
                ->orderByRaw('CAST(code AS UNSIGNED) DESC')
                ->first();

            if ($lastChild) {
                $newCode = (int)$lastChild->code + 1;
            } else {
                $newCode = 1171;
            }

            while (self::where('code', (string)$newCode)->exists()) {
                $newCode++;
            }

            $data = [
                'code' => (string) $newCode,
                'account_name' => 'Persediaan Bahan Penolong ' . $materialName,
                'description' => 'Akun persediaan untuk bahan penolong: ' . $materialName,
                'opening_balance' => 0,
                'normal_balance_position' => 'debit',
                'is_active' => true,
                'parent_code' => '117',
            ];

            if (Schema::hasColumn('chart_of_accounts', 'account_code')) {
                $data['account_code'] = (string) $newCode;
            }

            if (Schema::hasColumn('chart_of_accounts', 'account_group_name')) {
                $data['account_group_name'] = $parentAccount->account_group_name;
            }

            return self::create($data);
        });
    }

    public static function createAssetDepreciationAccounts(string $assetName, string $tipeAsset): array
    {
        $map = [
            'bangunan'  => [
                'akumulasi_parent' => '1112',
                'bop_parent'       => '5316',
                'label'            => 'Bangunan',
            ],
            'peralatan' => [
                'akumulasi_parent' => '1110',
                'bop_parent'       => '5313',
                'label'            => 'Peralatan',
            ],
            'mesin'     => [
                'akumulasi_parent' => '1114',
                'bop_parent'       => '5314',
                'label'            => 'Mesin',
            ],
            'kendaraan' => [
                'akumulasi_parent' => '1116',
                'bop_parent'       => '5315',
                'label'            => 'Kendaraan',
            ],
        ];

        $config = $map[$tipeAsset] ?? null;
        if (!$config) return [];

        $results = [];

        $results['akumulasi'] = self::createChildAccount(
            $config['akumulasi_parent'],
            'Akumulasi Penyusutan ' . $config['label'] . ' - ' . $assetName,
            'Akun akumulasi penyusutan untuk aset: ' . $assetName,
            'credit'
        );

        $results['bop'] = self::createChildAccount(
            $config['bop_parent'],
            'BOP-Penyusutan ' . $config['label'] . ' - ' . $assetName,
            'Akun beban penyusutan untuk aset: ' . $assetName,
            'debit'
        );

        return $results;
    }

    private static function createChildAccount(
        string $parentCode,
        string $accountName,
        string $description,
        string $normalBalance
    ): ?ChartOfAccount {
        return DB::transaction(function () use ($parentCode, $accountName, $description, $normalBalance) {
            $existing = self::where('account_name', $accountName)->first();
            if ($existing) return $existing;

            $parent = self::where('code', $parentCode)->first();
            if (!$parent) return null;

            $lastChild = self::where('code', 'like', $parentCode . '%')
                ->where('code', '!=', $parentCode)
                ->orderByRaw('LENGTH(code) DESC, code DESC')
                ->first();

            if ($lastChild) {
                $newCode = (int)$lastChild->code + 1;
            } else {
                $newCode = (int)($parentCode . '1');
            }

            while (self::where('code', (string)$newCode)->exists()) {
                $newCode++;
            }

            $data = [
                'code'                    => (string) $newCode,
                'account_name'            => $accountName,
                'account_type'            => $normalBalance === 'debit' ? 'expense' : 'asset',
                'description'             => $description,
                'opening_balance'         => 0,
                'normal_balance_position' => $normalBalance,
                'is_active'               => true,
                'parent_code'             => $parentCode,
            ];

            if (Schema::hasColumn('chart_of_accounts', 'account_code')) {
                $data['account_code'] = (string) $newCode;
            }
            if (Schema::hasColumn('chart_of_accounts', 'account_group_name')) {
                $data['account_group_name'] = $parent->account_group_name;
            }

            return self::create($data);
        });
    }

    public static function createSaleProductAccount(string $productName): ?ChartOfAccount
    {
        return DB::transaction(function () use ($productName) {
            $accountName = 'Penjualan - ' . $productName;
            $existing = self::where('account_name', $accountName)->first();
            if ($existing) return $existing;

            $parentAccount = self::where('code', '41')->first();
            if (!$parentAccount) return null;

            $lastChild = self::where('code', 'like', '41%')
                ->where('code', '!=', '41')
                ->orderByRaw('LENGTH(code) DESC, code DESC')
                ->first();

            if ($lastChild) {
                $newCode = (int)$lastChild->code + 1;
            } else {
                $newCode = 4101;
            }

            while (self::where('code', (string)$newCode)->exists()) {
                $newCode++;
            }

            $data = [
                'code'                    => (string) $newCode,
                'account_name'            => $accountName,
                'account_type'            => 'revenue',
                'description'             => 'Akun penjualan untuk produk: ' . $productName,
                'opening_balance'         => 0,
                'normal_balance_position' => 'credit',
                'is_active'               => true,
                'parent_code'             => '41',
            ];

            if (Schema::hasColumn('chart_of_accounts', 'account_code')) {
                $data['account_code'] = (string) $newCode;
            }
            if (Schema::hasColumn('chart_of_accounts', 'account_group_name')) {
                $data['account_group_name'] = $parentAccount->account_group_name;
            }

            return self::create($data);
        });
    }

    public static function createProductAccount(string $productName): ?ChartOfAccount
    {
        return DB::transaction(function () use ($productName) {
            $accountName = 'Pers. Barang Jadi - ' . $productName;
            $existing = self::where('account_name', $accountName)->first();
            if ($existing) return $existing;

            $parentAccount = self::where('code', '1105')->first();
            if (!$parentAccount) return null;

            $lastChild = self::where('code', 'like', '1105%')
                ->where('code', '!=', '1105')
                ->orderByRaw('LENGTH(code) DESC, code DESC')
                ->first();

            if ($lastChild) {
                $newCode = (int)$lastChild->code + 1;
            } else {
                $newCode = 110501;
            }

            while (self::where('code', (string)$newCode)->exists()) {
                $newCode++;
            }

            $data = [
                'code'                    => (string) $newCode,
                'account_name'            => $accountName,
                'account_type'            => 'asset',
                'description'             => 'Akun persediaan untuk produk: ' . $productName,
                'opening_balance'         => 0,
                'normal_balance_position' => 'debit',
                'is_active'               => true,
                'parent_code'             => '1105',
            ];

            if (Schema::hasColumn('chart_of_accounts', 'account_code')) {
                $data['account_code'] = (string) $newCode;
            }
            if (Schema::hasColumn('chart_of_accounts', 'account_group_name')) {
                $data['account_group_name'] = $parentAccount->account_group_name;
            }

            return self::create($data);
        });
    }

    /**
     * Ensure basic COA exists - auto-create missing basic accounts
     * Method ini akan dipanggil otomatis untuk memastikan COA dasar selalu ada
     * Data diambil langsung dari CorrectCoaSeeder
     */
    public static function ensureBasicCoaExists(): void
    {
        \Log::info("ensureBasicCoaExists() called - checking for missing COA");
        
        // Ambil data COA dari CorrectCoaSeeder
        $basicAccounts = self::getCoaDefinitionsFromSeeder();

        $createdCount = 0;
        foreach ($basicAccounts as $account) {
            // Cek apakah COA dengan kode ini sudah ada
            $existing = self::where('code', $account['code'])->first();
            
            if (!$existing) {
                try {
                    $data = [
                        'code'                    => $account['code'],
                        'account_name'            => $account['name'],
                        'account_type'            => $account['type'],
                        'account_group_name'      => $account['group'],
                        'description'             => 'Akun ' . $account['name'],
                        'opening_balance'         => 0,
                        'normal_balance_position' => self::getNormalBalancePosition($account['type']),
                        'is_active'               => true,
                        'parent_code'             => $account['parent'],
                    ];

                    // Add account_code if column exists
                    if (Schema::hasColumn('chart_of_accounts', 'account_code')) {
                        $data['account_code'] = $account['code'];
                    }

                    self::create($data);
                    $createdCount++;
                    \Log::info("Auto-created basic COA: {$account['code']} - {$account['name']}");
                } catch (\Exception $e) {
                    \Log::error("Failed to auto-create COA {$account['code']}: " . $e->getMessage());
                }
            }
        }
        
        \Log::info("ensureBasicCoaExists() completed - created {$createdCount} new COA accounts");
    }

    /**
     * Get COA definitions from CorrectCoaSeeder
     * Data yang sama persis dengan yang ada di seeder
     */
    private static function getCoaDefinitionsFromSeeder(): array
    {
        return [
            // ASSET
            ['code' => '11',   'name' => 'ASSET',                              'type' => 'asset',     'group' => 'Aset',       'parent' => null],
            ['code' => '1101',  'name' => 'Kas Bank',                           'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1102',  'name' => 'Kas',                                'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1103',  'name' => 'Kas Kecil',                          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1104',  'name' => 'Pers. Bahan Baku',                   'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1105',  'name' => 'Pers. Barang Jadi',                  'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1106',  'name' => 'Pers. Barang dalam Proses',          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '110601',  'name' => 'BDP - BBB',          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '110602',  'name' => 'BDP - BTKL',          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '110603',  'name' => 'BDP - BOP',          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1107',  'name' => 'Pers. Bahan Penolong',               'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1108',  'name' => 'Piutang',                            'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1109',  'name' => 'Peralatan',                          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1110', 'name' => 'Akumulasi Penyusutan Peralatan',     'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1111', 'name' => 'Gedung',                             'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1112', 'name' => 'Akumulasi Penyusutan Gedung',        'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1113', 'name' => 'Mesin',                              'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1114', 'name' => 'Akumulasi Penyusutan Mesin',         'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1115', 'name' => 'Kendaraan',                          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1116', 'name' => 'Akumulasi Penyusutan Kendaraan',     'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1117', 'name' => 'PPN Masukkan',                       'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1118', 'name' => 'Uang Muka Pembelian',                'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            
            // HUTANG
            ['code' => '21',  'name' => 'HUTANG',       'type' => 'liability', 'group' => 'Kewajiban', 'parent' => null],
            ['code' => '2101', 'name' => 'Hutang Usaha', 'type' => 'liability', 'group' => 'Kewajiban', 'parent' => '21'],
            ['code' => '2102', 'name' => 'Hutang Gaji',  'type' => 'liability', 'group' => 'Kewajiban', 'parent' => '21'],
            ['code' => '2103', 'name' => 'Hutang Lainnya','type' => 'liability','group' => 'Kewajiban', 'parent' => '21'],
            ['code' => '22',  'name' => 'PPN Keluaran',       'type' => 'liability', 'group' => 'Kewajiban', 'parent' => null],

            // MODAL
            ['code' => '31',  'name' => 'MODAL',        'type' => 'equity', 'group' => 'Ekuitas', 'parent' => null],
            ['code' => '3101', 'name' => 'Modal Usaha',  'type' => 'equity', 'group' => 'Ekuitas', 'parent' => '31'],
            ['code' => '3102', 'name' => 'Prive',        'type' => 'equity', 'group' => 'Ekuitas', 'parent' => '31'],

            // PENJUALAN
            ['code' => '41', 'name' => 'PENJUALAN', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => null],
            ['code' => '42', 'name' => 'Retur dan Potongan Penjualan', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => null],
            ['code' => '4201', 'name' => 'Retur Penjualan', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => '41'],
            ['code' => '4202', 'name' => 'Diskon Penjualan', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => '41'],
            ['code' => '43', 'name' => 'Pendapatan Lainnya', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => null],
            ['code' => '4301', 'name' => 'Beban Transport Penjualan', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => '41'],

            // BIAYA BAHAN BAKU
            ['code' => '51', 'name' => 'BBB-Biaya Bahan Baku', 'type' => 'expense', 'group' => 'Beban', 'parent' => null],

            // BIAYA TENAGA KERJA LANGSUNG
            ['code' => '52', 'name' => 'BTKL-Biaya Tenaga Kerja Langsung', 'type' => 'expense', 'group' => 'Beban', 'parent' => null],

            // BIAYA OVERHEAD PABRIK
            ['code' => '53',   'name' => 'BOP-Biaya Overhead Pabrik',              'type' => 'expense', 'group' => 'Beban', 'parent' => null],
            ['code' => '5301', 'name' => 'BOP-Iklan Produksi',                     'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5302', 'name' => 'BOP-Listrik Produksi',                   'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5303', 'name' => 'BOP-Air Produksi',                       'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5304', 'name' => 'BOP-Telepon & Internet Produksi',        'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5305', 'name' => 'BOP-Transport Produksi',                 'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5306', 'name' => 'BOP-Konsumsi Karyawan Produksi',         'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5307', 'name' => 'BOP-Asuransi Produksi',                  'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5308', 'name' => 'BOP-Pemeliharaan & Perawatan Produksi',  'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5309', 'name' => 'BOP-Perlengkapan Produksi',              'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5310', 'name' => 'BOP-Sewa Produksi',                      'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5311', 'name' => 'BOP-Penyusutan Produksi',                'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5312', 'name' => 'BOP-Pajak Produksi',                     'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5313', 'name' => 'BOP-Gas',                     'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5314', 'name' => 'BOP-Penyusutan Peralatan',               'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5315', 'name' => 'BOP-Penyusutan Mesin',                   'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5316', 'name' => 'BOP-Penyusutan Kendaraan',               'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5317', 'name' => 'BOP-Penyusutan Bangunan',               'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5318', 'name' => 'BOP-Dibebankan',        'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5319', 'name' => 'BOP-Lainnya',                            'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],

            // BIAYA TENAGA KERJA TIDAK LANGSUNG
            ['code' => '54', 'name' => 'BOP BTKTL-Biaya Tenaga Kerja Tidak Langsung', 'type' => 'expense', 'group' => 'Beban', 'parent' => null],
            
            // BAHAN PENOLONG
            ['code' => '55', 'name' => 'BOP BP-Bahan Penolong', 'type' => 'expense', 'group' => 'Beban', 'parent' => null],
            
            // BEBAN
            ['code' => '56', 'name' => 'Beban Transport Pembelian', 'type' => 'expense', 'group' => 'Beban', 'parent' => null],
            ['code' => '57', 'name' => 'Beban Gaji dan Upah ', 'type' => 'expense', 'group' => 'Beban', 'parent' => null],
            ['code' => '58', 'name' => 'Diskon Pembelian', 'type' => 'expense', 'group' => 'Beban', 'parent' => null],
            ['code' => '59', 'name' => 'Harga Pokok Penjualan', 'type' => 'expense', 'group' => 'Beban', 'parent' => null],
            
            // TAMBAHKAN COA BARU DI SINI SESUAI DENGAN YANG ADA DI CorrectCoaSeeder
            // Contoh jika Anda menambahkan COA baru di CorrectCoaSeeder:
            // ['code' => '60', 'name' => 'Beban Operasional', 'type' => 'expense', 'group' => 'Beban', 'parent' => null],
        ];
    }

    /**
     * Get normal balance position for account type
     */
    private static function getNormalBalancePosition(string $type): string
    {
        return match($type) {
            'asset', 'expense' => 'debit',
            'liability', 'equity', 'revenue' => 'credit',
            default => 'debit'
        };
    }
}
