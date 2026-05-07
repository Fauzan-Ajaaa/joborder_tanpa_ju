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
        // No-op: original column `customer_phone` never existed on sales_transactions
        // This migration is intentionally left empty to avoid errors on fresh databases.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: nothing to revert because no rename was performed.
    }
};
