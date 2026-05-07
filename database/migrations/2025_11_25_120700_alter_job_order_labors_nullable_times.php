<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah kolom waktu di job_order_labors agar boleh NULL,
        // karena nilai akan diisi saat user menekan tombol "Mulai Job" / "Job Selesai".
        DB::statement('ALTER TABLE job_order_labors MODIFY mulai_job_at DATETIME NULL');
        DB::statement('ALTER TABLE job_order_labors MODIFY selesai_job_at DATETIME NULL');
    }

    public function down(): void
    {
        // Kembalikan menjadi NOT NULL (gunakan default CURRENT_TIMESTAMP untuk menghindari error)
        DB::statement("ALTER TABLE job_order_labors MODIFY mulai_job_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE job_order_labors MODIFY selesai_job_at DATETIME NULL");
    }
};
