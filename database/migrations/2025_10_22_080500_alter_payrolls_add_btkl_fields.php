<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'pegawai_id')) {
                $table->unsignedBigInteger('pegawai_id')->nullable()->after('id');
                $table->foreign('pegawai_id')->references('id')->on('employees')->onDelete('cascade');
            }
            if (!Schema::hasColumn('payrolls', 'nama')) {
                $table->string('nama')->nullable()->after('pegawai_id');
            }
            if (!Schema::hasColumn('payrolls', 'kode_penggajian')) {
                $table->string('kode_penggajian')->unique()->nullable()->after('nama');
            }
            if (!Schema::hasColumn('payrolls', 'total_jam_kerja')) {
                $table->decimal('total_jam_kerja', 8, 2)->default(0)->after('kode_penggajian');
            }
            if (!Schema::hasColumn('payrolls', 'gaji_per_jam')) {
                $table->decimal('gaji_per_jam', 12, 2)->default(0)->after('total_jam_kerja');
            }
            if (!Schema::hasColumn('payrolls', 'bonus')) {
                $table->decimal('bonus', 12, 2)->nullable()->after('gaji_per_jam');
            }
            if (!Schema::hasColumn('payrolls', 'potongan')) {
                $table->decimal('potongan', 12, 2)->nullable()->after('bonus');
            }
            if (!Schema::hasColumn('payrolls', 'pajak')) {
                $table->decimal('pajak', 5, 2)->nullable()->after('potongan');
            }
            if (!Schema::hasColumn('payrolls', 'total_gaji_perhari')) {
                $table->decimal('total_gaji_perhari', 12, 2)->default(0)->after('pajak');
            }
            if (!Schema::hasColumn('payrolls', 'rata_rata_terjual_perhari')) {
                $table->decimal('rata_rata_terjual_perhari', 12, 2)->nullable()->after('total_gaji_perhari');
            }
            if (!Schema::hasColumn('payrolls', 'total_btkl')) {
                $table->decimal('total_btkl', 12, 2)->default(0)->after('rata_rata_terjual_perhari');
            }
        });

        // Optional data migration: set pegawai_id from employee_id if exists
        if (Schema::hasColumn('payrolls', 'employee_id') && Schema::hasColumn('payrolls', 'pegawai_id')) {
            DB::statement('UPDATE payrolls SET pegawai_id = employee_id WHERE pegawai_id IS NULL');
        }
        // Optional: set nama from employees.name if possible
        if (Schema::hasColumn('payrolls', 'pegawai_id') && Schema::hasColumn('payrolls', 'nama')) {
            DB::statement('UPDATE payrolls p JOIN employees e ON e.id = p.pegawai_id SET p.nama = COALESCE(p.nama, e.name)');
        }
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            if (Schema::hasColumn('payrolls', 'total_btkl')) {
                $table->dropColumn('total_btkl');
            }
            if (Schema::hasColumn('payrolls', 'rata_rata_terjual_perhari')) {
                $table->dropColumn('rata_rata_terjual_perhari');
            }
            if (Schema::hasColumn('payrolls', 'total_gaji_perhari')) {
                $table->dropColumn('total_gaji_perhari');
            }
            if (Schema::hasColumn('payrolls', 'pajak')) {
                $table->dropColumn('pajak');
            }
            if (Schema::hasColumn('payrolls', 'potongan')) {
                $table->dropColumn('potongan');
            }
            if (Schema::hasColumn('payrolls', 'bonus')) {
                $table->dropColumn('bonus');
            }
            if (Schema::hasColumn('payrolls', 'gaji_per_jam')) {
                $table->dropColumn('gaji_per_jam');
            }
            if (Schema::hasColumn('payrolls', 'total_jam_kerja')) {
                $table->dropColumn('total_jam_kerja');
            }
            if (Schema::hasColumn('payrolls', 'kode_penggajian')) {
                $table->dropUnique('payrolls_kode_penggajian_unique');
                $table->dropColumn('kode_penggajian');
            }
            if (Schema::hasColumn('payrolls', 'nama')) {
                $table->dropColumn('nama');
            }
            if (Schema::hasColumn('payrolls', 'pegawai_id')) {
                $table->dropForeign(['pegawai_id']);
                $table->dropColumn('pegawai_id');
            }
        });
    }
};
