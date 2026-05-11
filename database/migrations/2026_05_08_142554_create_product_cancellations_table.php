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
        Schema::create('product_cancellations', function (Blueprint $table) {
            $table->id();
            $table->string('cancellation_number')->unique();
            $table->unsignedBigInteger('job_order_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity_cancelled', 10, 4);
            $table->decimal('bbb_cost', 15, 2)->default(0); // Biaya Bahan Baku
            $table->decimal('btkl_cost', 15, 2)->default(0); // Biaya Tenaga Kerja Langsung
            $table->decimal('bop_cost', 15, 2)->default(0); // Biaya Overhead Pabrik
            $table->decimal('total_cost', 15, 2)->default(0); // Total biaya produk cacat
            $table->text('reason')->nullable(); // Alasan pembatalan
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('cancelled_at');
            $table->unsignedBigInteger('cancelled_by'); // User yang membatalkan
            $table->unsignedBigInteger('approved_by')->nullable(); // User yang approve
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->unsignedBigInteger('company_id');
            $table->timestamps();

            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            $table->index(['job_order_id', 'status']);
            $table->index(['cancelled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_cancellations');
    }
};