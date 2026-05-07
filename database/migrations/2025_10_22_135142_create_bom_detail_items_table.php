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
        Schema::create('bom_detail_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_detail_id')->constrained('bom_details')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->string('unit')->nullable();
            $table->decimal('price_per_unit', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->timestamps();
        });

        // Make legacy single-material columns nullable to avoid constraint errors
        Schema::table('bom_details', function (Blueprint $table) {
            $table->foreignId('raw_material_id')->nullable()->change();
            $table->decimal('quantity', 15, 2)->nullable()->change();
            $table->string('unit')->nullable()->change();
            $table->decimal('price_per_unit', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_details', function (Blueprint $table) {
            $table->foreignId('raw_material_id')->nullable(false)->change();
            $table->decimal('quantity', 15, 2)->nullable(false)->change();
            $table->string('unit')->nullable(false)->change();
            $table->decimal('price_per_unit', 15, 2)->nullable(false)->change();
        });
        Schema::dropIfExists('bom_detail_items');
    }
};
