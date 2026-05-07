<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuxiliaryMaterialCOASeeder extends Seeder
{
    public function run(): void
    {
        // Cek apakah parent COA "Persediaan Bahan Penolong" sudah ada
        $parentAccount = ChartOfAccount::where('code', '117')->first();
        
        if (!$parentAccount) {
            // Buat parent COA "Persediaan Bahan Penolong"
            $data = [
                'code' => '117',
                'account_name' => 'Persediaan Bahan Penolong',
                'description' => 'Akun induk untuk persediaan bahan penolong',
                'opening_balance' => 0,
                'normal_balance_position' => 'debit',
                'is_active' => true,
                'parent_code' => null,
            ];

            if (Schema::hasColumn('chart_of_accounts', 'account_code')) {
                $data['account_code'] = '117';
            }

            if (Schema::hasColumn('chart_of_accounts', 'account_group_name')) {
                $data['account_group_name'] = 'Current Assets';
            }

            ChartOfAccount::create($data);
            
            $this->command->info('✅ Parent COA "Persediaan Bahan Penolong" (117) berhasil dibuat');
        } else {
            $this->command->info('ℹ️ Parent COA "Persediaan Bahan Penolong" (117) sudah ada');
        }
        
        // Tampilkan child accounts yang sudah ada
        $childAccounts = ChartOfAccount::where('code', 'like', '117%')
            ->where('code', '!=', '117')
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
