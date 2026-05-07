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
        // Cek apakah tabel bill_of_materials ada sebelum membuat foreign key
        if (Schema::hasTable('bill_of_materials')) {
            Schema::create('bill_of_material_processes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bill_of_material_id')->constrained()->onDelete('cascade');
                $table->string('process_name');
                $table->decimal('duration_minutes', 8, 2);
                $table->decimal('btkl_cost', 12, 2)->default(0);
                $table->decimal('bop_cost', 12, 2)->default(0);
                $table->decimal('total_process_cost', 12, 2)->default(0);
                $table->integer('sequence_order')->default(1);
                $table->timestamps();
            });
        } else {
            // Buat tabel tanpa foreign key jika tabel parent tidak ada
            Schema::create('bill_of_material_processes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bill_of_material_id');
                $table->string('process_name');
                $table->decimal('duration_minutes', 8, 2);
                $table->decimal('btkl_cost', 12, 2)->default(0);
                $table->decimal('bop_cost', 12, 2)->default(0);
                $table->decimal('total_process_cost', 12, 2)->default(0);
                $table->integer('sequence_order')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_material_processes');
    }
};
