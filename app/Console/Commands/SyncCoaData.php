<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChartOfAccount;

class SyncCoaData extends Command
{
    protected $signature = 'coa:sync {--force : Force update existing accounts}';
    protected $description = 'Sync Chart of Accounts data from master data';

    public function handle(): int
    {
        $this->info('Syncing Chart of Accounts data...');
        
        $masterAccounts = [
            ['account_code' => '111',  'account_name' => 'Kas',                     'account_type' => 'asset',      'description' => 'Kas dan setara kas'],
            ['account_code' => '112',  'account_name' => 'Piutang Usaha',          'account_type' => 'asset',      'description' => 'Piutang dari penjualan kredit'],
            ['account_code' => '113',  'account_name' => 'Piutang Lain-lain',       'account_type' => 'asset',      'description' => 'Piutang non-usaha'],
            ['account_code' => '114',  'account_name' => 'Persediaan Barang Jadi', 'account_type' => 'asset',      'description' => 'Persediaan produk jadi'],
            ['account_code' => '115',  'account_name' => 'Persediaan Bahan Baku',  'account_type' => 'asset',      'description' => 'Persediaan bahan baku'],
            ['account_code' => '117',  'account_name' => 'Peralatan',               'account_type' => 'asset',      'description' => 'Peralatan kantor & produksi'],
            ['account_code' => '118',  'account_name' => 'Akumulasi Penyusutan',   'account_type' => 'asset',      'description' => 'Akumulasi penyusutan peralatan'],
            
            ['account_code' => '2100', 'account_name' => 'PPN Keluaran',           'account_type' => 'liability',  'description' => 'PPN yang terutang dari penjualan'],
            ['account_code' => '2101', 'account_name' => 'PPN Masukan',            'account_type' => 'liability',  'description' => 'PPN yang dapat dikreditkan'],
            ['account_code' => '2200', 'account_name' => 'Hutang Usaha',           'account_type' => 'liability',  'description' => 'Hutang kepada supplier'],
            ['account_code' => '2201', 'account_name' => 'Hutang Gaji',             'account_type' => 'liability',  'description' => 'Hutang gaji karyawan'],
            
            ['account_code' => '3100', 'account_name' => 'Modal Saham',            'account_type' => 'equity',     'description' => 'Modal pemegang saham'],
            ['account_code' => '3200', 'account_name' => 'Laba Ditahan',           'account_type' => 'equity',     'description' => 'Laba yang ditahan'],
            
            ['account_code' => '4000', 'account_name' => 'Pendapatan Penjualan',   'account_type' => 'revenue',    'description' => 'Pendapatan dari penjualan produk'],
            ['account_code' => '4100', 'account_name' => 'Pendapatan Lain-lain',   'account_type' => 'revenue',    'description' => 'Pendapatan non-operasional'],
            
            ['account_code' => '5100', 'account_name' => 'Harga Pokok Penjualan',  'account_type' => 'expense',    'description' => 'HPP produk terjual'],
            ['account_code' => '5200', 'account_name' => 'Biaya Bahan Baku',      'account_type' => 'expense',    'description' => 'Biaya pembelian bahan baku'],
            ['account_code' => '5300', 'account_name' => 'Biaya Tenaga Kerja',     'account_type' => 'expense',    'description' => 'Biaya gaji dan upah'],
            ['account_code' => '5400', 'account_name' => 'Biaya Overhead',         'account_type' => 'expense',    'description' => 'Biaya overhead pabrik'],
            ['account_code' => '5500', 'account_name' => 'Biaya Administrasi',     'account_type' => 'expense',    'description' => 'Biaya administrasi & umum'],
            ['account_code' => '5600', 'account_name' => 'Biaya Marketing',        'account_type' => 'expense',    'description' => 'Biaya pemasaran & penjualan'],
        ];

        $created = 0;
        $updated = 0;

        foreach ($masterAccounts as $account) {
            $existing = ChartOfAccount::where('code', $account['account_code'])->first();
            
            if ($existing) {
                if ($this->option('force')) {
                    $existing->update([
                        'account_name' => $account['account_name'],
                        'account_type' => $account['account_type'],
                        'description' => $account['description'],
                        'is_active' => true,
                    ]);
                    $updated++;
                    $this->line("✓ Updated: {$account['account_code']} - {$account['account_name']}");
                } else {
                    $this->line("- Exists: {$account['account_code']} - {$account['account_name']}");
                }
            } else {
                ChartOfAccount::create([
                    'account_code' => $account['account_code'],
                    'account_name' => $account['account_name'],
                    'account_type' => $account['account_type'],
                    'description' => $account['description'],
                    'is_active' => true,
                    'balance' => 0,
                ]);
                $created++;
                $this->line("+ Created: {$account['account_code']} - {$account['account_name']}");
            }
        }

        $this->newLine();
        $this->info("Sync completed!");
        $this->info("Created: {$created} accounts");
        $this->info("Updated: {$updated} accounts");
        
        return Command::SUCCESS;
    }
}
