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
        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->decimal('profit_margin_percentage', 8, 2)->nullable()->after('selling_price')->comment('Profit margin percentage for recommended selling price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->dropColumn('profit_margin_percentage');
        });
    }
};
