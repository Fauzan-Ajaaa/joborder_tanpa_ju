<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biaya_overhead', function (Blueprint $table) {
            $table->enum('status_pembayaran', ['debit', 'kredit'])->default('debit')->after('bop_code');
        });
    }

    public function down(): void
    {
        Schema::table('biaya_overhead', function (Blueprint $table) {
            $table->dropColumn('status_pembayaran');
        });
    }
};