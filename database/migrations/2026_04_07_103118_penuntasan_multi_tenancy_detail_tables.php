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
            'purchase_items',
            'purchase_payments',
            'purchase_returns',
            'purchase_return_items',
            'sales_items',
            'sales_returns',
            'sales_return_items',
            'transaction_items',
            'bom_details',
            'product_auxiliary_materials',
            'bill_of_material_items',
            'bill_of_material_processes',
            'bill_of_material_auxiliary_materials',
            'job_order_details',
            'job_order_items',
            'job_order_labours',
            'job_order_materials',
            'journal_entry_items',
            'journal_entry_lines',
            'raw_material_unit_conversions',
            'raw_material_usages',
            'expenses',
            'customer_discounts',
            'biaya_overhead',
            'overhead_monthly',
            'overhead_por',
            'account_period_balances',
            'auxiliary_material_unit_conversions'
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
            'purchase_items',
            'purchase_payments',
            'purchase_returns',
            'purchase_return_items',
            'sales_items',
            'sales_returns',
            'sales_return_items',
            'transaction_items',
            'bom_details',
            'product_auxiliary_materials',
            'bill_of_material_items',
            'bill_of_material_processes',
            'bill_of_material_auxiliary_materials',
            'job_order_details',
            'job_order_items',
            'job_order_labours',
            'job_order_materials',
            'journal_entry_items',
            'journal_entry_lines',
            'raw_material_unit_conversions',
            'raw_material_usages',
            'expenses',
            'customer_discounts',
            'biaya_overhead',
            'overhead_monthly',
            'overhead_por',
            'account_period_balances',
            'auxiliary_material_unit_conversions'
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
