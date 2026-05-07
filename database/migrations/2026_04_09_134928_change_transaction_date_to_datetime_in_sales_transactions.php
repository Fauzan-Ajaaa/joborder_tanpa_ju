<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sales_transactions MODIFY transaction_date DATETIME NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sales_transactions MODIFY transaction_date DATE NULL');
    }
};
