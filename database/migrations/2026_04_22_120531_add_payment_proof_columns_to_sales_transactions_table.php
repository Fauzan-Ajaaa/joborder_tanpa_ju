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
            $table->string('payment_proof')->nullable()->after('payment_status');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('payment_proof');
            $table->text('approval_notes')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approval_notes');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'payment_proof',
                'approval_status', 
                'approval_notes',
                'approved_at',
                'approved_by'
            ]);
        });
    }
};
