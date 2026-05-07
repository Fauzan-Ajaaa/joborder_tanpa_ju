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
            $table->foreignId('received_by_employee_id')
                ->nullable()
                ->after('status')
                ->constrained('employees')
                ->nullOnDelete();

            $table->string('receipt_document_path')
                ->nullable()
                ->after('received_by_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['received_by_employee_id']);
            $table->dropColumn(['received_by_employee_id', 'receipt_document_path']);
        });
    }
};

