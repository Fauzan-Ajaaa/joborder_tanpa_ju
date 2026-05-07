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
        Schema::table('units', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM units'))->pluck('Key_name');
            if ($indexes->contains('units_code_unique')) {
                $table->dropUnique(['code']);
            }
            if (!$indexes->contains('units_code_company_id_unique')) {
                $table->unique(['code', 'company_id']);
            }
        });

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM chart_of_accounts'))->pluck('Key_name');
            $fks = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'chart_of_accounts' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"))->pluck('CONSTRAINT_NAME');

            // Drop FK first if it exists
            if ($fks->contains('chart_of_accounts_parent_code_foreign')) {
                $table->dropForeign('chart_of_accounts_parent_code_foreign');
            }

            if ($indexes->contains('chart_of_accounts_account_code_unique')) {
                $table->dropUnique('chart_of_accounts_account_code_unique');
            } elseif ($indexes->contains('chart_of_accounts_code_unique')) {
                $table->dropUnique('chart_of_accounts_code_unique');
            }

            if (!$indexes->contains('chart_of_accounts_code_company_id_unique')) {
                $table->unique(['code', 'company_id']);
            }

            // Re-add FK as composite to support multi-tenancy
            if (!$fks->contains('chart_of_accounts_parent_code_company_id_foreign')) {
                $table->foreign(['parent_code', 'company_id'])
                      ->references(['code', 'company_id'])
                      ->on('chart_of_accounts')
                      ->onDelete('cascade');
            }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropUnique(['code', 'company_id']);
            $table->unique('code');
        });

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('chart_of_accounts', 'code')) {
                $table->dropUnique(['code', 'company_id']);
                $table->unique('code');
            }
            if (Schema::hasColumn('chart_of_accounts', 'account_code')) {
                $table->dropUnique(['account_code', 'company_id']);
                $table->unique('account_code');
            }
        });
    }
};
