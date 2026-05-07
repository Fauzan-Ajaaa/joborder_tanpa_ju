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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Tambah kolom account_group_name jika belum ada
            if (!Schema::hasColumn('chart_of_accounts', 'account_group_name')) {
                $table->string('account_group_name')->after('account_type');
            }
        });
        
        // Update data berdasarkan account_type
        DB::statement("UPDATE chart_of_accounts SET account_group_name = 
            CASE account_type
                WHEN 'asset' THEN 'Aset'
                WHEN 'liability' THEN 'Kewajiban'
                WHEN 'equity' THEN 'Modal'
                WHEN 'revenue' THEN 'Pendapatan'
                WHEN 'expense' THEN 'Beban'
                ELSE 'Lainnya'
            END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('chart_of_accounts', 'account_group_name')) {
                $table->dropColumn('account_group_name');
            }
        });
    }
};
