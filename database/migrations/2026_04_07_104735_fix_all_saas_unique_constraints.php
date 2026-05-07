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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        $tables = Schema::getTables();
        
        foreach ($tables as $tableInfo) {
            $table = $tableInfo['name'];
            
            if (in_array($table, ['migrations', 'personal_access_tokens', 'failed_jobs', 'password_reset_tokens', 'companies', 'sessions', 'cache', 'job_batches', 'users'])) {
                continue;
            }

            if (!Schema::hasColumn($table, 'company_id')) {
                continue;
            }

            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $index) {
                if ($index['unique'] && !$index['primary']) {
                    $columns = $index['columns'];
                    
                    // Jika indeks unik belum menyertakan company_id
                    if (!in_array('company_id', $columns)) {
                        $indexName = $index['name'];
                        
                        try {
                            Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName, $columns) {
                                // 1. Hapus indeks unik lama
                                $tableBlueprint->dropUnique($indexName);
                                
                                // 2. Buat indeks unik baru dengan company_id
                                $newColumns = array_merge($columns, ['company_id']);
                                $tableBlueprint->unique($newColumns);
                                
                                echo "FIXED: Unique index '$indexName' on table '" . $tableBlueprint->getTable() . "' updated to include company_id.\n";
                            });
                        } catch (\Exception $e) {
                            echo "SKIPPING '$indexName' on '$table': " . $e->getMessage() . "\n";
                        }
                    }
                }
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback skipped for this specific fix migration
    }
};
