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
        if (!Schema::hasTable('bill_of_material_auxiliaries')) {
            Schema::create('bill_of_material_auxiliaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bill_of_material_id')->constrained()->onDelete('cascade');
                $table->foreignUuid('auxiliary_material_id')->constrained()->onDelete('cascade');
                $table->decimal('quantity', 10, 4)->default(0);
                $table->string('unit', 20)->nullable();
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_material_auxiliaries');
    }
};
