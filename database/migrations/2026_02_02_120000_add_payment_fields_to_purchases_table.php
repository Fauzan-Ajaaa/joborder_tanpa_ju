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
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'credit'])->default('cash')->after('total_amount');
            $table->date('due_date')->nullable()->after('payment_method');
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending')->after('due_date');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'due_date', 
                'payment_status',
                'paid_amount'
            ]);
        });
    }
};
