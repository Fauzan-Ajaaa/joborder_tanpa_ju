<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_order_materials', function (Blueprint $table) {
            if (! Schema::hasColumn('job_order_materials', 'unit')) {
                $table->string('unit')->nullable()->after('qty_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_order_materials', function (Blueprint $table) {
            if (Schema::hasColumn('job_order_materials', 'unit')) {
                $table->dropColumn('unit');
            }
        });
    }
};
