<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class CleanupDuplicatesSeeder extends Seeder
{
    public function run(): void
    {
        $models = [Unit::class, ChartOfAccount::class];

        foreach ($models as $model) {
            $this->command->info("Cleaning up model: $model");
            
            $all = $model::withoutGlobalScopes()->get();
            $this->command->info("Total records: " . $all->count());
            
            $seen = [];
            $delCount = 0;
            
            foreach ($all as $record) {
                // Ensure we get the raw code/company_id
                $code = (string)$record->code ?: (string)$record->account_code;
                $company_id = (string)$record->company_id ?: 'NULL';
                
                $key = $company_id . '||' . $code;
                
                if (isset($seen[$key])) {
                    $this->command->warn("Deleting Duplicate: $key (ID: {$record->id})");
                    DB::table($record->getTable())->where('id', $record->id)->delete();
                    $delCount++;
                } else {
                    $seen[$key] = true;
                    $this->command->info("Seen: $key");
                }
            }
            $this->command->info("Total deleted for $model: $delCount");
        }
    }
}
