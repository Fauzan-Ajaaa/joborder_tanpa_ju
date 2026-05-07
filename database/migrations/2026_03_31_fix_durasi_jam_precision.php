<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->decimal('durasi_jam', 10, 4)->nullable()->change();
        });

        Schema::table('job_order_labors', function (Blueprint $table) {
            $table->decimal('durasi_jam', 10, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->decimal('durasi_jam', 10, 2)->nullable()->change();
        });

        Schema::table('job_order_labors', function (Blueprint $table) {
            $table->decimal('durasi_jam', 10, 2)->nullable()->change();
        });
    }
};
