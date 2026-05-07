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
        Schema::table('biaya_overhead', function (Blueprint $table) {
            if (! Schema::hasColumn('biaya_overhead', 'kategori')) {
                $table->string('kategori', 50)->default('BOP')->after('jenis_biaya');
            }

            if (! Schema::hasColumn('biaya_overhead', 'chart_of_account_id')) {
                $table->unsignedBigInteger('chart_of_account_id')->nullable()->after('kategori');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biaya_overhead', function (Blueprint $table) {
            if (Schema::hasColumn('biaya_overhead', 'chart_of_account_id')) {
                $table->dropColumn('chart_of_account_id');
            }

            if (Schema::hasColumn('biaya_overhead', 'kategori')) {
                $table->dropColumn('kategori');
            }
        });
    }
};
