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
        Schema::table('sales_transactions', function (Blueprint $table) {
            // Customer contact for delivery
            $table->string('customer_phone', 20)->nullable()->after('customer_address');
            
            // Delivery notes/instructions
            $table->text('delivery_notes')->nullable()->after('customer_phone');
            
            // Payment method type - includes COD for delivery
            $table->enum('payment_method', ['cash', 'transfer', 'ewallet', 'cod'])
                  ->nullable()
                  ->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn(['customer_phone', 'delivery_notes', 'payment_method']);
        });
    }
};
