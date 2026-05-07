<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_transactions', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('transaction_date');
            }
            if (!Schema::hasColumn('sales_transactions', 'employee')) {
                $table->string('employee')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('sales_transactions', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('employee');
            }
            if (!Schema::hasColumn('sales_transactions', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('notes');
            }
            if (!Schema::hasColumn('sales_transactions', 'fob_type')) {
                $table->enum('fob_type', ['shipping_point','destination'])->default('shipping_point')->after('subtotal');
            }
            if (!Schema::hasColumn('sales_transactions', 'fob_cost')) {
                $table->decimal('fob_cost', 15, 2)->default(0)->after('fob_type');
            }
            if (!Schema::hasColumn('sales_transactions', 'ppn_rate')) {
                $table->decimal('ppn_rate', 5, 2)->default(11.00)->after('fob_cost');
            }
            if (!Schema::hasColumn('sales_transactions', 'ppn_amount')) {
                $table->decimal('ppn_amount', 15, 2)->default(0)->after('ppn_rate');
            }
            if (!Schema::hasColumn('sales_transactions', 'grand_total')) {
                $table->decimal('grand_total', 15, 2)->default(0)->after('ppn_amount');
            }
            if (!Schema::hasColumn('sales_transactions', 'payment_status')) {
                $table->enum('payment_status', ['draft','unpaid','paid'])->default('unpaid')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('sales_transactions', 'customer_name')) $table->dropColumn('customer_name');
            if (Schema::hasColumn('sales_transactions', 'employee')) $table->dropColumn('employee');
            if (Schema::hasColumn('sales_transactions', 'customer_address')) $table->dropColumn('customer_address');
            if (Schema::hasColumn('sales_transactions', 'subtotal')) $table->dropColumn('subtotal');
            if (Schema::hasColumn('sales_transactions', 'fob_type')) $table->dropColumn('fob_type');
            if (Schema::hasColumn('sales_transactions', 'fob_cost')) $table->dropColumn('fob_cost');
            if (Schema::hasColumn('sales_transactions', 'ppn_rate')) $table->dropColumn('ppn_rate');
            if (Schema::hasColumn('sales_transactions', 'ppn_amount')) $table->dropColumn('ppn_amount');
            if (Schema::hasColumn('sales_transactions', 'grand_total')) $table->dropColumn('grand_total');
            if (Schema::hasColumn('sales_transactions', 'payment_status')) $table->dropColumn('payment_status');
        });
    }
};