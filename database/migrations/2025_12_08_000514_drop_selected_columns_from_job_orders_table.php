<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            if (Schema::hasColumn('job_orders', 'produk_id')) {
                $table->dropColumn('produk_id');
            }

            if (Schema::hasColumn('job_orders', 'qty_order')) {
                $table->dropColumn('qty_order');
            }

            if (Schema::hasColumn('job_orders', 'tanggal_order')) {
                $table->dropColumn('tanggal_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Tambahkan kembali kolom jika rollback
            $table->unsignedBigInteger('produk_id')->nullable();
            $table->decimal('qty_order', 10, 4)->nullable();
            $table->date('tanggal_order')->nullable();
        });
    }
};
