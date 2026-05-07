<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('bbb_total', 15, 2)->nullable();
            $table->decimal('btkl_total', 15, 2)->nullable();
            $table->decimal('bop_total', 15, 2)->nullable();
            $table->decimal('hpp_total', 15, 2)->nullable();
            $table->decimal('hpp_per_unit', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_items');
    }
};
