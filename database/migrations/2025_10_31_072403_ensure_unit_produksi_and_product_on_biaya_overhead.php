<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureUnitProduksiAndProductOnBiayaOverhead extends Migration
{
    public function up(): void
    {
        Schema::table('biaya_overhead', function (Blueprint $table) {
            $table->integer('unit_produksi')->nullable();
            $table->integer('product_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('biaya_overhead', function (Blueprint $table) {
            $table->dropColumn('unit_produksi');
            $table->dropColumn('product_id');
        });
    }
}