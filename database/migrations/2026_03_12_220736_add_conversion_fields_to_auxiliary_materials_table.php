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
        Schema::table('auxiliary_materials', function (Blueprint $table) {
            $table->string('conversion_unit_name')->nullable()->after('unit');
            $table->decimal('conversion_unit', 15, 6)->nullable()->after('conversion_unit_name');
            $table->decimal('conversion_price', 15, 2)->nullable()->after('conversion_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auxiliary_materials', function (Blueprint $table) {
            $table->dropColumn(['conversion_unit_name', 'conversion_unit', 'conversion_price']);
        });
    }
};
