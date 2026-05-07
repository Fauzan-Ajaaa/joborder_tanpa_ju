<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'chart_of_account_id')) {
                $table->unsignedBigInteger('chart_of_account_id')->nullable()->after('tarif_per_jam');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'chart_of_account_id')) {
                $table->dropColumn('chart_of_account_id');
            }
        });
    }
};
