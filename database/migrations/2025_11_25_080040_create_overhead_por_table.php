<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overhead_por', function (Blueprint $table) {
            $table->id();

            $table->string('periode', 7);

            $table->enum('dasar_alokasi', ['jam_tk_langsung', 'jam_mesin'])
                ->default('jam_tk_langsung');

            $table->decimal('total_overhead_bulanan', 15, 2);
            $table->decimal('total_jam_dasar_alokasi', 15, 2);
            $table->decimal('por_per_jam', 15, 4);

            $table->timestamps();

            $table->unique(['periode', 'dasar_alokasi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overhead_por');
    }
};
