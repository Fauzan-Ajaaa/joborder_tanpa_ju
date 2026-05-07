<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Reset semua job orders ke status draft
        DB::statement('UPDATE job_orders SET status = "draft", mulai_job_at = NULL, selesai_job_at = NULL');
    }

    public function down(): void
    {
        // Tidak perlu rollback
    }
};
