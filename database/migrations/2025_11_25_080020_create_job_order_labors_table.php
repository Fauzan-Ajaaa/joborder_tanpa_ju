<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_order_labors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('job_order_id')
                ->constrained('job_orders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate();

            $table->dateTime('mulai_job_at');
            $table->dateTime('selesai_job_at')->nullable();

            $table->integer('durasi_menit')->nullable();
            $table->decimal('durasi_jam', 10, 2)->nullable();

            $table->decimal('tarif_per_jam', 15, 4);
            $table->decimal('biaya_btkl', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_order_labors');
    }
};
