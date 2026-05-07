<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'job_order_labors',
            'bill_of_material_auxiliaries'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    if (!Schema::hasColumn($tableBlueprint->getTable(), 'company_id')) {
                        $tableBlueprint->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'job_order_labors',
            'bill_of_material_auxiliaries'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    if (Schema::hasColumn($tableBlueprint->getTable(), 'company_id')) {
                        $tableBlueprint->dropConstrainedForeignId('company_id');
                    }
                });
            }
        }
    }
};
