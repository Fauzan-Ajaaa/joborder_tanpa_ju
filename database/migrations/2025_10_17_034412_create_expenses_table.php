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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->foreignId('chart_of_account_id')->constrained()->onDelete('restrict');
            $table->string('category'); // Kategori beban (operasional, marketing, dll)
            $table->string('description');
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->string('vendor')->nullable(); // Supplier/Vendor
            $table->string('payment_method')->nullable(); // Metode pembayaran
            $table->string('reference_number')->nullable(); // No referensi/invoice
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable(); // File bukti
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
