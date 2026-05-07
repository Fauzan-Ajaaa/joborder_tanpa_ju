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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->date('periode_awal')->nullable()->after('opening_balance'); // Tanggal awal periode saldo
            $table->decimal('saldo_awal_periode', 15, 2)->default(0)->after('periode_awal'); // Saldo awal periode berjalan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn(['periode_awal', 'saldo_awal_periode']);
        });
    }
};
