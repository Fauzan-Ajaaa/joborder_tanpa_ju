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
        // Change ENUM to include 'completed' status
        DB::statement("ALTER TABLE product_cancellations MODIFY status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'completed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM
        DB::statement("ALTER TABLE product_cancellations MODIFY status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }
};
