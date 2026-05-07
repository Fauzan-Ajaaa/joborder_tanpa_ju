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
        if (!Schema::hasTable('bill_of_materials')) {
            Schema::create('bill_of_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('btkl_rate_per_hour', 15, 2)->default(0);
                $table->decimal('bop_rate_per_hour', 15, 2)->default(0);
                $table->decimal('selling_price', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bill_of_material_items')) {
            Schema::create('bill_of_material_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bill_of_material_id')->constrained('bill_of_materials')->onDelete('cascade');
                $table->foreignId('raw_material_id')->constrained('raw_materials')->onDelete('cascade');
                $table->decimal('quantity', 15, 4);
                $table->string('unit', 50)->nullable();
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bill_of_material_auxiliaries')) {
            Schema::create('bill_of_material_auxiliaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bill_of_material_id')->constrained('bill_of_materials')->onDelete('cascade');
                $table->uuid('auxiliary_material_id');
                $table->decimal('quantity', 15, 4);
                $table->string('unit', 50)->nullable();
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->timestamps();

                $table->foreign('auxiliary_material_id')->references('id')->on('auxiliary_materials')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_material_auxiliaries');
        Schema::dropIfExists('bill_of_material_items');
        Schema::dropIfExists('bill_of_materials');
    }
};
