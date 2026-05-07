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
        // Tabel Purchases (Header Pembelian)
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number')->unique();
            $table->date('purchase_date');
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('fob_cost', 15, 2)->default(0)->comment('Freight on Board / Biaya Pengiriman');
            $table->decimal('ppn_rate', 5, 2)->default(11)->comment('PPN Rate dalam persen');
            $table->decimal('ppn_amount', 15, 2)->default(0)->comment('Jumlah PPN');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'approved', 'received', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Tabel Purchase Items (Detail Item Pembelian)
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            $table->foreignId('raw_material_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('conversion_factor', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });

        // Tabel Purchase Returns (Retur Pembelian)
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique();
            $table->foreignId('purchase_id')->constrained()->onDelete('restrict');
            $table->date('return_date');
            $table->enum('reason', ['damaged', 'wrong_item', 'excess', 'quality_issue', 'other'])->default('other');
            $table->text('notes')->nullable();
            $table->decimal('total_return_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'completed'])->default('pending');
            $table->timestamps();
        });

        // Tabel Purchase Return Items (Detail Item Retur)
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_item_id')->constrained()->onDelete('restrict');
            $table->foreignId('raw_material_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
