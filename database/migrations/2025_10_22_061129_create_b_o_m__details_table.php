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
         Schema::create('bom_details', function (Blueprint $table) {
            $table->id();
            $table->string('product_name'); // Nama produk, misal Brownies
            $table->foreignId('raw_material_id')->constrained('raw_materials')->cascadeOnDelete(); // bahan baku
            $table->decimal('quantity', 15, 2); // jumlah penggunaan bahan
            $table->string('unit')->nullable(); // satuan (gram, ml, dll)
            $table->decimal('price_per_unit', 15, 2); // harga per satuan bahan
            $table->decimal('total_material_cost', 15, 2)->default(0); // hasil perkalian quantity x harga per unit
            $table->decimal('btkl', 15, 2)->default(0); // biaya tenaga kerja langsung
            $table->decimal('bop', 15, 2)->default(0); // biaya overhead pabrik
            $table->decimal('total_hpp', 15, 2)->default(0); // total keseluruhan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_details');
    }
};
