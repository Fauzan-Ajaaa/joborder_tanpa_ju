<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Drop unique index first if present
            if (Schema::hasColumn('payrolls', 'payroll_number')) {
                try {
                    $table->dropUnique('payrolls_payroll_number_unique');
                } catch (\Throwable $e) {
                    // index may not exist; ignore
                }
            }
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $columns = [
                'payroll_number',
                'period_start',
                'period_end',
                'base_salary',
                'allowances',
                'overtime',
                'bonuses',
                'deductions',
                'tax',
                'net_salary',
                'status',
                'payment_date',
                'notes',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('payrolls', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Recreate columns in a minimal form to allow rollback
            if (!Schema::hasColumn('payrolls', 'payroll_number')) {
                $table->string('payroll_number')->unique()->nullable();
            }
            if (!Schema::hasColumn('payrolls', 'period_start')) {
                $table->date('period_start')->nullable();
            }
            if (!Schema::hasColumn('payrolls', 'period_end')) {
                $table->date('period_end')->nullable();
            }
            if (!Schema::hasColumn('payrolls', 'base_salary')) {
                $table->decimal('base_salary', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'allowances')) {
                $table->decimal('allowances', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'overtime')) {
                $table->decimal('overtime', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'bonuses')) {
                $table->decimal('bonuses', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'deductions')) {
                $table->decimal('deductions', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'tax')) {
                $table->decimal('tax', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'net_salary')) {
                $table->decimal('net_salary', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('payrolls', 'status')) {
                $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            }
            if (!Schema::hasColumn('payrolls', 'payment_date')) {
                $table->date('payment_date')->nullable();
            }
            if (!Schema::hasColumn('payrolls', 'notes')) {
                $table->text('notes')->nullable();
            }
        });
    }
};
