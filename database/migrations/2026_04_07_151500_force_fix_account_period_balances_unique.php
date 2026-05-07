<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('account_period_balances')) {
            $table = 'account_period_balances';
            $oldIndex = 'account_period_balances_chart_of_account_id_period_unique';
            
            // Check if company_id is present
            if (!Schema::hasColumn($table, 'company_id')) {
                return;
            }

            // Check if the old index still exists
            $indexes = Schema::getIndexes($table);
            $hasOldIndex = false;
            $hasNewIndex = false;
            
            foreach ($indexes as $index) {
                if ($index['name'] === $oldIndex) {
                    $columns = $index['columns'];
                    if (!in_array('company_id', $columns)) {
                        $hasOldIndex = true;
                    } else {
                        $hasNewIndex = true;
                    }
                }
            }

            if ($hasOldIndex && !$hasNewIndex) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    // 1. Create a temporary index to satisfied foreign key requirement
                    $tableBlueprint->index('chart_of_account_id', 'temp_fk_index');
                    
                    // 2. Drop the old unique index
                    $tableBlueprint->dropUnique('account_period_balances_chart_of_account_id_period_unique');
                    
                    // 3. Create the new unique index including company_id
                    $tableBlueprint->unique(['chart_of_account_id', 'period', 'company_id'], 'account_period_balances_coa_period_company_unique');
                    
                    // 4. Drop the temporary index
                    $tableBlueprint->dropIndex('temp_fk_index');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('account_period_balances')) {
            Schema::table('account_period_balances', function (Blueprint $tableBlueprint) {
                if (Schema::hasIndex('account_period_balances', 'account_period_balances_coa_period_company_unique')) {
                    $tableBlueprint->index('chart_of_account_id', 'temp_fk_index');
                    $tableBlueprint->dropUnique('account_period_balances_coa_period_company_unique');
                    $tableBlueprint->unique(['chart_of_account_id', 'period'], 'account_period_balances_chart_of_account_id_period_unique');
                    $tableBlueprint->dropIndex('temp_fk_index');
                }
            });
        }
    }
};
