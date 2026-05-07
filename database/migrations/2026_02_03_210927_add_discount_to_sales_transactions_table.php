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
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_transactions', 'discount_rate')) {
                $table->decimal('discount_rate', 5, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('sales_transactions', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('sales_transactions', 'discount_rate')) {
                $table->dropColumn('discount_rate');
            }
            if (Schema::hasColumn('sales_transactions', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
        });
    }
};
