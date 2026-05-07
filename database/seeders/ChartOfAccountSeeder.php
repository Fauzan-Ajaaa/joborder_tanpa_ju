<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Cek apakah parent COA "Persediaan Bahan Baku" sudah ada
        $parentAccount = ChartOfAccount::where('code', '114')->first();
        
        if (!$parentAccount) {
            // Buat parent COA "Persediaan Bahan Baku"
            ChartOfAccount::create([
                'code' => '114',
                'account_name' => 'Persediaan Bahan Baku',
                'account_group_name' => 'Current Assets',
                'description' => 'Akun induk untuk persediaan bahan baku',
                'opening_balance' => 0,
                'normal_balance_position' => 'debit',
                'is_active' => true,
                'parent_code' => null,
            ]);
            
            $this->command->info('✅ Parent COA "Persediaan Bahan Baku" (114) berhasil dibuat');
        } else {
            $this->command->info('ℹ️ Parent COA "Persediaan Bahan Baku" (114) sudah ada');
        }
        
        // Tampilkan child accounts yang sudah ada
        $childAccounts = ChartOfAccount::where('code', 'like', '114%')
            ->where('code', '!=', '114')
            ->orderBy('code')
            ->get();
            
        if ($childAccounts->count() > 0) {
            $this->command->info('📋 Child accounts yang sudah ada:');
            foreach ($childAccounts as $child) {
                $this->command->info("   - {$child->code}: {$child->account_name}");
            }
        } else {
            $this->command->info('📋 Belum ada child accounts');
        }
    }
}
