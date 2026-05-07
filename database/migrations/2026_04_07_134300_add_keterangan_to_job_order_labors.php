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
        if (Schema::hasTable('job_order_labors')) {
            Schema::table('job_order_labors', function (Blueprint $table) {
                if (!Schema::hasColumn('job_order_labors', 'keterangan')) {
                    $table->text('keterangan')->nullable()->after('biaya_btkl');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_order_labors')) {
            Schema::table('job_order_labors', function (Blueprint $table) {
                if (Schema::hasColumn('job_order_labors', 'keterangan')) {
                    $table->dropColumn('keterangan');
                }
            });
        }
    }
};
