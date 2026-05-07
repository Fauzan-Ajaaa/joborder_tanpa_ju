<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('biaya_overhead', function (Blueprint $table) {
            $table->string('jenis_biaya')->after('id');
            $table->string('bop_code')->unique()->after('jenis_biaya');
            $table->date('periode')->after('bop_code');
            $table->json('items')->nullable()->after('periode');
            $table->decimal('total', 18, 2)->default(0)->after('items');
            $table->text('catatan')->nullable()->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biaya_overhead', function (Blueprint $table) {
            $table->dropColumn(['jenis_biaya', 'bop_code', 'periode', 'items', 'total', 'catatan']);
        });
    }
};
