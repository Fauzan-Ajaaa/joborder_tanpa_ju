<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Payroll;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing payroll records
        // If tax was stored as amount, convert to percentage
        // This migration assumes existing tax values are amounts that need conversion
        // If your data is already in percentage format, you can skip running this migration
        
        $payrolls = Payroll::all();
        
        foreach ($payrolls as $payroll) {
            // Recalculate net salary with the current tax as percentage
            $payroll->calculateNetSalary();
            $payroll->saveQuietly(); // Save without triggering events
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse as we're just recalculating
    }
};
