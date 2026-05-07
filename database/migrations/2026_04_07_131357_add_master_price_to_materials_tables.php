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
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->decimal('master_price_per_unit', 15, 2)->nullable();
        });

        Schema::table('auxiliary_materials', function (Blueprint $table) {
            $table->decimal('master_price_per_unit', 15, 2)->nullable();
        });

        DB::table('raw_materials')->update(['master_price_per_unit' => DB::raw('price_per_unit')]);
        DB::table('auxiliary_materials')->update(['master_price_per_unit' => DB::raw('price_per_unit')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropColumn('master_price_per_unit');
        });

        Schema::table('auxiliary_materials', function (Blueprint $table) {
            $table->dropColumn('master_price_per_unit');
        });
    }
};
