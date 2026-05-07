<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'chart_of_account_id')) {
                $table->unsignedBigInteger('chart_of_account_id')->nullable()->after('tarif_per_jam');
            }
            if (!Schema::hasColumn('employees', 'bdp_btkl_coa_id')) {
                $table->unsignedBigInteger('bdp_btkl_coa_id')->nullable()->after('chart_of_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['chart_of_account_id', 'bdp_btkl_coa_id']);
        });
    }
};
