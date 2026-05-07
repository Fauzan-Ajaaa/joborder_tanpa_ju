<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('sales_transactions', function (Blueprint $table) {
        // Cek apakah kolom customer_id sudah ada
        if (!Schema::hasColumn('sales_transactions', 'customer_id')) {
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
        } else {
            // Jika sudah ada, tidak perlu melakukan apa-apa
            // Foreign key constraint mungkin sudah ada
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            //
        });
    }
};
