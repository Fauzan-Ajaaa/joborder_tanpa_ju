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
        Schema::create('auxiliary_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit');
            $table->string('recipe_unit')->nullable();
            $table->decimal('recipe_conversion_factor', 8, 6)->nullable();
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('price_per_unit', 12, 2)->default(0);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('chart_of_account_id')->nullable();
            $table->timestamps();
            
            $table->index('code');
            $table->index('name');
            $table->index('supplier_id');
            $table->index('chart_of_account_id');
            
            // Foreign key constraints
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auxiliary_materials');
    }
};
