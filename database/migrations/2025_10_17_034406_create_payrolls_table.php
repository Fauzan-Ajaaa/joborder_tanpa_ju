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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('payroll_number')->unique();
            $table->date('period_start'); // Awal periode
            $table->date('period_end'); // Akhir periode
            $table->decimal('base_salary', 15, 2); // Gaji pokok
            $table->decimal('allowances', 15, 2)->default(0); // Tunjangan
            $table->decimal('overtime', 15, 2)->default(0); // Lembur
            $table->decimal('bonuses', 15, 2)->default(0); // Bonus
            $table->decimal('deductions', 15, 2)->default(0); // Potongan
            $table->decimal('tax', 15, 2)->default(0); // Pajak
            $table->decimal('net_salary', 15, 2); // Gaji bersih
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
