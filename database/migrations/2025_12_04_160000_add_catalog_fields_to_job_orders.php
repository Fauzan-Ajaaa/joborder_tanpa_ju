<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            // Customer information for catalog orders
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();
            
            // Payment information
            $table->string('payment_method')->nullable();
            $table->string('payment_proof')->nullable();
            $table->dateTime('paid_at')->nullable();
            
            // Rename and add fields for catalog compatibility
            $table->renameColumn('produk_id', 'product_id');
            $table->renameColumn('qty_order', 'quantity');
            $table->renameColumn('tanggal_order', 'order_date');
            
            // Add total cost for catalog
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('material_cost', 15, 2)->default(0);
        });
        
        // Update status enum to include catalog statuses
        Schema::table('job_orders', function (Blueprint $table) {
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled', 'paid'])
                ->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn([
                'customer_name',
                'customer_email', 
                'customer_phone',
                'notes',
                'payment_method',
                'payment_proof',
                'paid_at',
                'total_cost',
                'material_cost'
            ]);
            
            $table->renameColumn('product_id', 'produk_id');
            $table->renameColumn('quantity', 'qty_order');
            $table->renameColumn('order_date', 'tanggal_order');
        });
        
        // Revert status enum
        Schema::table('job_orders', function (Blueprint $table) {
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])
                ->default('draft')->change();
        });
    }
};
