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
        Schema::table('material_stock_balances', function (Blueprint $table) {
            // Tambah kolom untuk menyimpan FIFO layers sebagai JSON
            // Format: [{"qty": 3, "price": 31532}, {"qty": 2, "price": 31532}]
            $table->json('beginning_fifo_layers')->nullable()->after('beginning_value');
            $table->json('ending_fifo_layers')->nullable()->after('ending_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_stock_balances', function (Blueprint $table) {
            $table->dropColumn(['beginning_fifo_layers', 'ending_fifo_layers']);
        });
    }
};
