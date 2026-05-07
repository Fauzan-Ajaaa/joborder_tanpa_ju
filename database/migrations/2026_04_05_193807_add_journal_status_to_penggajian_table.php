<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            if (!Schema::hasColumn('penggajian', 'journal_status')) {
                $table->string('journal_status')->nullable()->after('status');
            }
            if (!Schema::hasColumn('penggajian', 'recognition_journal_id')) {
                $table->unsignedBigInteger('recognition_journal_id')->nullable()->after('journal_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            if (Schema::hasColumn('penggajian', 'journal_status')) {
                $table->dropColumn('journal_status');
            }
            if (Schema::hasColumn('penggajian', 'recognition_journal_id')) {
                $table->dropColumn('recognition_journal_id');
            }
        });
    }
};
