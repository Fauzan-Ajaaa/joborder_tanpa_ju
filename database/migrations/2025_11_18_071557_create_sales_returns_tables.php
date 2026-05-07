<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_returns')) {
            Schema::create('sales_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_transaction_id')->constrained('sales_transactions')->cascadeOnDelete();
                $table->date('return_date');
                $table->text('reason')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('ppn_amount', 15, 2)->default(0);
                $table->decimal('grand_total', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sales_return_items')) {
            Schema::create('sales_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
                $table->unsignedBigInteger('sales_item_id')->nullable();
                $table->foreign('sales_item_id')->references('id')->on('sales_items')->nullOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('quantity', 15, 2);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};