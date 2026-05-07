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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'bom_detail_id')) {
                $table->foreignId('bom_detail_id')
                    ->nullable()
                    ->after('price')
                    ->constrained('bom_details')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'bom_detail_id')) {
                $table->dropConstrainedForeignId('bom_detail_id');
            }
        });
    }
};
