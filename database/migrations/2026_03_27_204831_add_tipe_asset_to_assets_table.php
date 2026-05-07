<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->enum('tipe_asset', ['bangunan', 'kendaraan', 'mesin', 'peralatan'])
                  ->default('peralatan')
                  ->after('nama_asset');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('tipe_asset');
        });
    }
};
