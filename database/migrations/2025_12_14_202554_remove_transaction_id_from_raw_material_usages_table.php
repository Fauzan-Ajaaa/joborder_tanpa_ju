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
        // Use raw SQL to safely drop foreign key and column if they exist
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        try {
            // Drop foreign key if it exists
            DB::statement('ALTER TABLE raw_material_usages DROP FOREIGN KEY IF EXISTS raw_material_usages_transaction_id_foreign');
            
            // Drop column if it exists
            DB::statement('ALTER TABLE raw_material_usages DROP COLUMN IF EXISTS transaction_id');
        } catch (\Exception $e) {
            // Ignore errors, continue
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_material_usages', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_material_usages', 'transaction_id')) {
                $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            }
        });
    }
};
