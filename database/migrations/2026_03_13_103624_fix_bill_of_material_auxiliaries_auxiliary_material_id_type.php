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
        if (Schema::hasTable('bill_of_material_auxiliaries')) {
            Schema::table('bill_of_material_auxiliaries', function (Blueprint $table) {
                $table->char('auxiliary_material_id', 36)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bill_of_material_auxiliaries', function (Blueprint $table) {
            $table->unsignedBigInteger('auxiliary_material_id')->change();
        });
    }
};
