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
        Schema::table('assets', function (Blueprint $table) {
            // Add depreciation method field
            $table->enum('depreciation_method', [
                'straight_line',
                'double_declining',
                'single_declining',
                'sum_of_years_digits',
                'units_of_production'
            ])->default('straight_line')->after('tanggal_perolehan');

            // Add fields for Units of Production method
            $table->decimal('total_production_units', 15, 2)->nullable()->after('depreciation_method');
            $table->decimal('current_production_units', 15, 2)->default(0)->after('total_production_units');
            $table->decimal('production_rate', 15, 2)->nullable()->after('current_production_units');
            
            // Add start depreciation date
            $table->dateTimeTz('start_depreciation_date')->nullable()->after('production_rate');
            
            // Add status flag
            $table->boolean('is_fully_depreciated')->default(false)->after('start_depreciation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'depreciation_method',
                'total_production_units',
                'current_production_units',
                'production_rate',
                'start_depreciation_date',
                'is_fully_depreciated'
            ]);
        });
    }
};
