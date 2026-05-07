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
        Schema::create('penggajian', function (Blueprint $table) {
            $table->uuid('id_gaji')->primary();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('tanggal_penggajian');
            $table->string('no_transaksi_gaji')->unique();
            $table->unsignedInteger('total_service')->default(0);
            $table->decimal('bonus', 8, 2)->default(0); // persentase bonus service
            $table->decimal('bonus_service', 15, 2)->default(0);
            $table->unsignedInteger('total_kehadiran')->default(0);
            $table->unsignedInteger('bonus_kehadiran')->default(0);
            $table->decimal('total_bonus_kehadiran', 15, 2)->default(0);
            $table->decimal('tunjangan_makan', 15, 2)->default(0);
            $table->decimal('tunjangan_jabatan', 15, 2)->default(0);
            $table->decimal('lembur', 15, 2)->default(0);
            $table->decimal('potongan_gaji', 15, 2)->default(0);
            $table->decimal('tarif', 15, 2)->default(0); // gaji pokok/jabatan
            $table->decimal('total_gaji_bersih', 15, 2)->default(0);
            $table->text('detail_potongan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggajian');
    }
};
