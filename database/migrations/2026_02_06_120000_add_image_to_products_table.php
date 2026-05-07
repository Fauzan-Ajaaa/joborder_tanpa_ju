<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE products ADD image_data LONGBLOB NULL AFTER barcode');
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->binary('image_data')->nullable()->after('barcode');
            });
        }
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_mime_type', 50)->nullable()->after('image_data');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_data', 'image_mime_type']);
        });
    }
};
