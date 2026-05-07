<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'jam_kerja_per_bulan')) {
                $table->decimal('jam_kerja_per_bulan', 8, 2)->default(173)->after('base_salary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'jam_kerja_per_bulan')) {
                $table->dropColumn('jam_kerja_per_bulan');
            }
        });
    }
};
