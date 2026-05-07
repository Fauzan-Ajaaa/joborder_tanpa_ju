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
        Schema::create('auxiliary_material_unit_conversions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('auxiliary_material_id');
            $table->string('from_unit');
            $table->string('to_unit');
            $table->decimal('factor', 15, 6);
            $table->timestamps();
            
            $table->index(['auxiliary_material_id', 'from_unit', 'to_unit'], 'aux_mat_conv_idx');
            
            // Foreign key constraint
            $table->foreign('auxiliary_material_id', 'aux_mat_conv_fk')->references('id')->on('auxiliary_materials')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auxiliary_material_unit_conversions');
    }
};
