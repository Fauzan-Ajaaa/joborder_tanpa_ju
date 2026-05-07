<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'inventory_coa_id')) {
                $table->unsignedBigInteger('inventory_coa_id')->nullable()->after('bom_detail_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'inventory_coa_id')) {
                $table->dropColumn('inventory_coa_id');
            }
        });
    }
};
