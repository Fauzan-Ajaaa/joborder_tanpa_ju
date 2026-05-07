<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_order_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_order_id')
                ->constrained('job_orders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('bahan_baku_id')
                ->constrained('raw_materials')
                ->cascadeOnUpdate();

            $table->decimal('qty_per_unit', 15, 4);
            $table->decimal('qty_total', 15, 4);
            $table->decimal('harga_per_unit', 15, 4);
            $table->decimal('total_biaya', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_materials');
    }
};
