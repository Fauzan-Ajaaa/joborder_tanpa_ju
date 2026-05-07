<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('bill_of_material_auxiliaries')) {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME 
                 FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'bill_of_material_auxiliaries' 
                 AND CONSTRAINT_NAME = 'bill_of_material_auxiliaries_auxiliary_material_id_foreign'"
            );

            if (count($foreignKeys) > 0) {
                Schema::table('bill_of_material_auxiliaries', function (Blueprint $table) {
                    $table->dropForeign('bill_of_material_auxiliaries_auxiliary_material_id_foreign');
                });
            }

            Schema::table('bill_of_material_auxiliaries', function (Blueprint $table) {
                $table->char('auxiliary_material_id', 36)->change();
            });

            Schema::table('bill_of_material_auxiliaries', function (Blueprint $table) {
                // Add the missing foreign key
                $table->foreign('auxiliary_material_id')
                      ->references('id')
                      ->on('auxiliary_materials')
                      ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bill_of_material_auxiliaries')) {
            Schema::table('bill_of_material_auxiliaries', function (Blueprint $table) {
                $table->dropForeign(['auxiliary_material_id']);
                $table->unsignedBigInteger('auxiliary_material_id')->change();
            });
        }
    }
};
