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
        Schema::create('material_stock_balances', function (Blueprint $table) {
            $table->id();
            $table->string('material_type'); // 'RawMaterial' or 'AuxiliaryMaterial'
            $table->string('material_id');
            $table->date('period'); // First day of the month
            $table->decimal('beginning_qty', 15, 4)->default(0);
            $table->decimal('beginning_value', 15, 2)->default(0);
            $table->decimal('in_qty', 15, 4)->default(0);
            $table->decimal('in_value', 15, 2)->default(0);
            $table->decimal('out_qty', 15, 4)->default(0);
            $table->decimal('out_value', 15, 2)->default(0);
            $table->decimal('ending_qty', 15, 4)->default(0);
            $table->decimal('ending_value', 15, 2)->default(0);
            $table->boolean('is_posted')->default(false);
            $table->timestamps();

            $table->index(['material_type', 'material_id', 'period'], 'material_period_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_stock_balances');
    }
};
