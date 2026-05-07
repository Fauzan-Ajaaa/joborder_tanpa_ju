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
            'users',
            'raw_materials',
            'auxiliary_materials',
            'products',
            'suppliers',
            'customers',
            'transactions',
            'sales_transactions',
            'purchases',
            'job_orders',
            'bill_of_materials',
            'employees',
            'chart_of_accounts',
            'units',
            'journal_entries',
            'cash_books',
            'assets',
            'penggajian',
            'payrolls',
            'material_stock_balances'
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

    public function down(): void
    {
        $tables = [
            'users',
            'raw_materials',
            'auxiliary_materials',
            'products',
            'suppliers',
            'customers',
            'transactions',
            'sales_transactions',
            'purchases',
            'job_orders',
            'bill_of_materials',
            'employees',
            'chart_of_accounts',
            'units',
            'journal_entries',
            'cash_books',
            'assets',
            'penggajian',
            'payrolls',
            'material_stock_balances'
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
