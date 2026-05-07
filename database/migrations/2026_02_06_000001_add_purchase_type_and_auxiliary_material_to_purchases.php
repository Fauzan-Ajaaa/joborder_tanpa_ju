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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('purchase_type', 30)->default('raw_material')->after('supplier_id')
                ->comment('raw_material = bahan baku, auxiliary_material = bahan penolong');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->uuid('auxiliary_material_id')->nullable()->after('purchase_id');
            $table->foreign('auxiliary_material_id')->references('id')->on('auxiliary_materials')->onDelete('restrict');
        });

        // Make raw_material_id nullable so item can be either raw_material or auxiliary_material
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->unsignedBigInteger('raw_material_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['auxiliary_material_id']);
            $table->unsignedBigInteger('raw_material_id')->nullable(false)->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('purchase_type');
        });
    }
};
