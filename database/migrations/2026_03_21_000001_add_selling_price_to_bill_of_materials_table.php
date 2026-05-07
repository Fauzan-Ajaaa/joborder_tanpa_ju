<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bill_of_materials')) {
            Schema::table('bill_of_materials', function (Blueprint $table) {
                if (!Schema::hasColumn('bill_of_materials', 'selling_price')) {
                    $table->decimal('selling_price', 15, 2)->default(0)->after('bop_rate_per_hour');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('bill_of_materials', function (Blueprint $table) {
            if (Schema::hasColumn('bill_of_materials', 'selling_price')) {
                $table->dropColumn('selling_price');
            }
        });
    }
};
