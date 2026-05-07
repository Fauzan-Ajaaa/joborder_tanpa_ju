<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK raw_material_id hanya jika memang masih ada,
        // untuk menghindari error "Can't DROP FOREIGN KEY ... check that it exists".
        $rawMaterialFk = DB::selectOne(<<<SQL
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'purchase_return_items'
              AND COLUMN_NAME = 'raw_material_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        SQL);

        if ($rawMaterialFk) {
            Schema::table('purchase_return_items', function (Blueprint $table) {
                $table->dropForeign(['raw_material_id']);
            });
        }

        // Hanya tambahkan kolom auxiliary_material_id jika belum ada,
        // supaya migration ini tetap bisa dijalankan walaupun kolom sudah dibuat sebelumnya.
        if (! Schema::hasColumn('purchase_return_items', 'auxiliary_material_id')) {
            Schema::table('purchase_return_items', function (Blueprint $table) {
                $table->unsignedBigInteger('raw_material_id')->nullable()->change();
                $table->uuid('auxiliary_material_id')->nullable()->after('raw_material_id');
            });
        } else {
            // Kolom sudah ada: tetap pastikan raw_material_id boleh null
            Schema::table('purchase_return_items', function (Blueprint $table) {
                $table->unsignedBigInteger('raw_material_id')->nullable()->change();
            });
        }

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreign('raw_material_id')->references('id')->on('raw_materials')->onDelete('restrict');

            if (Schema::hasColumn('purchase_return_items', 'auxiliary_material_id')) {
                // Hanya buat FK auxiliary_material_id jika belum ada
                $auxFk = DB::selectOne(<<<SQL
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'purchase_return_items'
                      AND COLUMN_NAME = 'auxiliary_material_id'
                      AND REFERENCED_TABLE_NAME = 'auxiliary_materials'
                    LIMIT 1
                SQL);

                if (! $auxFk) {
                    $table->foreign('auxiliary_material_id')->references('id')->on('auxiliary_materials')->onDelete('restrict');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropForeign(['auxiliary_material_id']);
            $table->dropForeign(['raw_material_id']);
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropColumn('auxiliary_material_id');
            $table->unsignedBigInteger('raw_material_id')->nullable(false)->change();
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreign('raw_material_id')->references('id')->on('raw_materials')->onDelete('restrict');
        });
    }
};
