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
        // Kolom account_group_name sudah ada sebagai string, jadi tidak perlu diubah lagi
        // Yang penting data sudah terisi dengan bahasa Indonesia dari migration sebelumnya
        
        // Migration ini tidak perlu melakukan apa-apa lagi karena kolom sudah string
        // dan data sudah terisi dengan benar
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus kolom account_group_name
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('account_group_name');
        });
    }
};
