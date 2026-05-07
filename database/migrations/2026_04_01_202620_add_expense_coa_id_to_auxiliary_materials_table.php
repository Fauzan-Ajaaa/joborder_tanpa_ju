<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auxiliary_materials', function (Blueprint $table) {
            if (! Schema::hasColumn('auxiliary_materials', 'expense_coa_id')) {
                $table->unsignedBigInteger('expense_coa_id')->nullable()->after('chart_of_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('auxiliary_materials', function (Blueprint $table) {
            if (Schema::hasColumn('auxiliary_materials', 'expense_coa_id')) {
                $table->dropColumn('expense_coa_id');
            }
        });
    }
};
