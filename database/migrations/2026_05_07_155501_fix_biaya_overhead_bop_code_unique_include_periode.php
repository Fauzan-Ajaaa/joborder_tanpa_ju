<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biaya_overhead', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM biaya_overhead'))->pluck('Key_name')->unique();

            // Drop existing bop_code+company_id unique (or bop_code alone)
            foreach (['biaya_overhead_bop_code_company_id_unique', 'biaya_overhead_bop_code_unique'] as $idx) {
                if ($indexes->contains($idx)) {
                    $table->dropUnique($idx);
                }
            }

            // New constraint: bop_code + company_id + periode
            $table->unique(['bop_code', 'company_id', 'periode'], 'biaya_overhead_bop_code_company_id_periode_unique');
        });
    }

    public function down(): void
    {
        Schema::table('biaya_overhead', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM biaya_overhead'))->pluck('Key_name')->unique();

            if ($indexes->contains('biaya_overhead_bop_code_company_id_periode_unique')) {
                $table->dropUnique('biaya_overhead_bop_code_company_id_periode_unique');
            }

            $table->unique(['bop_code', 'company_id'], 'biaya_overhead_bop_code_company_id_unique');
        });
    }
};
