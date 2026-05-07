<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Tambah customer_name jika belum ada
            if (!Schema::hasColumn('job_orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            if (Schema::hasColumn('job_orders', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
        });
    }
};
