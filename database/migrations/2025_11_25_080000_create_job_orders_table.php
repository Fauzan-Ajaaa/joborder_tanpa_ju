<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('job_orders')) {
            return;
        }

        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();

            $table->string('kode_job')->unique();

            $table->foreignId('produk_id')
                ->constrained('products')
                ->cascadeOnUpdate();

            $table->decimal('qty_order', 15, 4);
            $table->dateTime('tanggal_order')->nullable();

            // Optional: hubungkan ke pelanggan jika tabel customers sudah ada.
            // Untuk menghindari error FK di instalasi awal, sementara hanya disimpan sebagai kolom biasa.
            $table->unsignedBigInteger('customer_id')->nullable();

            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])
                ->default('draft');

            $table->dateTime('mulai_job_at')->nullable();
            $table->dateTime('selesai_job_at')->nullable();
            $table->integer('durasi_menit')->nullable();
            $table->decimal('durasi_jam', 10, 2)->nullable();

            // Link ke POR (Predetermined Overhead Rate). Untuk menghindari error FK awal,
            // sementara hanya disimpan sebagai kolom biasa.
            $table->unsignedBigInteger('por_id')->nullable();

            $table->decimal('total_bbb', 15, 2)->default(0);
            $table->decimal('total_btkl', 15, 2)->default(0);
            $table->decimal('total_bop', 15, 2)->default(0);
            $table->decimal('total_hpp', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};
