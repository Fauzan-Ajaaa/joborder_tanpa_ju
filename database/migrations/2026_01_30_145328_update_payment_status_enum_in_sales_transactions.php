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
        Schema::table('sales_transactions', function (Blueprint $table) {
            // Pertama, ubah ke string nullable untuk menghapus constraint ENUM
            $table->string('payment_status')->nullable()->change();
        });
        
        // Update data yang ada
        DB::table('sales_transactions')->where('payment_status', 'unpaid')->update(['payment_status' => 'cash']);
        DB::table('sales_transactions')->where('payment_status', 'paid')->update(['payment_status' => 'cash']);
        DB::table('sales_transactions')->where('payment_status', 'draft')->update(['payment_status' => 'cash']);
        DB::table('sales_transactions')->whereNull('payment_status')->update(['payment_status' => 'cash']);
        
        Schema::table('sales_transactions', function (Blueprint $table) {
            // Kemudian ubah ke ENUM baru
            $table->enum('payment_status', ['cash', 'transfer', 'ewallet'])->default('cash')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->enum('payment_status', ['draft', 'unpaid', 'paid'])->default('unpaid')->change();
        });
    }
};
