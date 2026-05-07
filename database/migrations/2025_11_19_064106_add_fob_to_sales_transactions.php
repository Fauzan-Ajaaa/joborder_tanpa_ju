<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_transactions', 'fob_type')) {
                $table->enum('fob_type', ['shipping_point','destination'])
                      ->default('shipping_point')
                      ->after('subtotal');
            }
            if (!Schema::hasColumn('sales_transactions', 'fob_cost')) {
                $table->decimal('fob_cost', 15, 2)
                      ->default(0)
                      ->after('fob_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('sales_transactions', 'fob_type')) {
                $table->dropColumn('fob_type');
            }
            if (Schema::hasColumn('sales_transactions', 'fob_cost')) {
                $table->dropColumn('fob_cost');
            }
        });
    }
};