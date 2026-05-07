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
        Schema::table('raw_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_materials', 'min_stock')) {
                $table->decimal('min_stock', 15, 2)->default(0)->after('stock');
            }
            if (!Schema::hasColumn('raw_materials', 'min_stock_unit')) {
                $table->string('min_stock_unit')->nullable()->after('min_stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_materials', function (Blueprint $table) {
            if (Schema::hasColumn('raw_materials', 'min_stock')) {
                $table->dropColumn('min_stock');
            }
            if (Schema::hasColumn('raw_materials', 'min_stock_unit')) {
                $table->dropColumn('min_stock_unit');
            }
        });
    }
};
