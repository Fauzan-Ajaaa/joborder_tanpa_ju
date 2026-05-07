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
        Schema::create('product_auxiliary_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->uuid('auxiliary_material_id');
            $table->decimal('quantity_needed', 10, 2)->default(0)->comment('Jumlah bahan penolong per unit produk');
            $table->string('unit', 50)->nullable();
            $table->timestamps();

            $table->foreign('auxiliary_material_id')->references('id')->on('auxiliary_materials')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_auxiliary_materials');
    }
};
