<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetAllTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:all-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus semua transaksi dan reset laporan ke kondisi awal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai reset semua transaksi...');
        
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        try {
            // Hapus semua data transaksi
            $tables = [
                'journal_entry_items',
                'journal_entries',
                'sales_transactions',
                'sales_transaction_items',
                'sales_returns',
                'sales_return_items',
                'job_orders',
                'job_order_items',
                'purchase_orders',
                'purchase_order_items',
                'purchases',
                'purchase_items',
                'payrolls',
                'payroll_items',
                'overhead_costs',
                'overhead_cost_items',
            ];
            
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    if ($count > 0) {
                        DB::table($table)->truncate();
                        $this->info("✅ Berhasil mengosongkan tabel: {$table} ({$count} record)");
                    } else {
                        $this->info("ℹ️  Tabel {$table} sudah kosong");
                    }
                }
            }
            
            // Reset saldo semua akun menjadi 0
            if (Schema::hasTable('chart_of_accounts')) {
                DB::table('chart_of_accounts')->update([
                    'balance' => 0, 
                    'opening_balance' => 0, 
                    'saldo_awal_periode' => 0
                ]);
                $this->info("✅ Reset semua field saldo (balance, opening_balance, saldo_awal_periode) menjadi 0");
            }
            
            // Reset auto increment
            $resetTables = [
                'journal_entries',
                'sales_transactions',
                'sales_returns',
                'job_orders',
                'purchase_orders',
                'purchases',
                'payrolls',
                'overhead_costs',
            ];
            
            foreach ($resetTables as $table) {
                if (Schema::hasTable($table)) {
                    DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
                    $this->info("🔄 Reset auto increment tabel: {$table}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return 1;
        }
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('✅ Semua transaksi berhasil dihapus!');
        $this->info('🎯 Laporan sekarang kosong seperti kondisi awal');
        $this->info('📊 Silakan cek laporan Neraca Saldo dan Laporan Posisi Keuangan');
        
        return 0;
    }
}
