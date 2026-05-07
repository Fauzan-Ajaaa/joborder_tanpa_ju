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
        Schema::dropIfExists('assets');

        Schema::create('assets', function (Blueprint $table) {
            $table->uuid('id_assets')->primary();
            $table->string('nama_asset');
            $table->decimal('harga_perolehan', 15, 2);
            $table->decimal('nilai_sisa', 15, 2);
            $table->integer('masa_manfaat');
            $table->dateTimeTz('tanggal_perolehan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
