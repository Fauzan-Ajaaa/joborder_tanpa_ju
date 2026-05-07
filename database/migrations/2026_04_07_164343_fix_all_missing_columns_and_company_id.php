<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. master_price_per_unit di raw_materials
        if (!Schema::hasColumn('raw_materials', 'master_price_per_unit')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                $table->decimal('master_price_per_unit', 15, 2)->default(0)->after('price_per_unit');
            });
        }

        // 2. master_price_per_unit di auxiliary_materials
        if (!Schema::hasColumn('auxiliary_materials', 'master_price_per_unit')) {
            Schema::table('auxiliary_materials', function (Blueprint $table) {
                $table->decimal('master_price_per_unit', 15, 2)->default(0)->after('price_per_unit');
            });
        }

        // 3. keterangan di job_order_labors
        if (!Schema::hasColumn('job_order_labors', 'keterangan')) {
            Schema::table('job_order_labors', function (Blueprint $table) {
                $table->string('keterangan')->nullable()->after('biaya_btkl');
            });
        }

        // 4. fob_type di sales_transactions ubah ke VARCHAR
        DB::statement('ALTER TABLE sales_transactions MODIFY fob_type VARCHAR(50) NULL');

        // 5. company_id ke semua tabel yang belum punya
        $tables = [
            'raw_material_unit_conversions',
            'auxiliary_material_unit_conversions',
            'raw_material_usages',
            'bill_of_material_items',
            'bill_of_material_processes',
            'job_order_details',
            'job_order_items',
            'job_order_materials',
            'job_order_labors',
            'sales_returns',
            'sales_return_items',
            'sales_items',
            'purchase_items',
            'purchase_payments',
            'purchase_return_items',
            'purchase_returns',
            'transaction_items',
            'product_auxiliary_materials',
            'bom_details',
            'bom_detail_items',
            'expenses',
            'customer_discounts',
            'overhead_por',
            'overhead_monthly',
            'biaya_overhead',
            'journal_entry_items',
            'journal_entry_lines',
            'account_period_balances',
        ];

        foreach ($tables as $t) {
            if (Schema::hasTable($t) && !Schema::hasColumn($t, 'company_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('id');
                });
            }
        }
    }

    public function down(): void
    {
        // Tidak perlu rollback kolom company_id karena bersifat additive
    }
};
