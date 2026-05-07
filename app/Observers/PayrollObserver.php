<?php

namespace App\Observers;

use App\Models\Payroll;
use Illuminate\Support\Facades\Log;

class PayrollObserver
{
    /**
     * Handle the Payroll "created" event.
     */
    public function created(Payroll $payroll): void
    {
        // Auto-generate jurnal saat penggajian dibuat
        try {
            $payroll->createJournalEntry();
        } catch (\Exception $e) {
            Log::error('Failed to create journal from payroll: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Payroll "updated" event.
     */
    public function updated(Payroll $payroll): void
    {
        //
    }

    /**
     * Handle the Payroll "deleted" event.
     */
    public function deleted(Payroll $payroll): void
    {
        //
    }

    /**
     * Handle the Payroll "restored" event.
     */
    public function restored(Payroll $payroll): void
    {
        //
    }

    /**
     * Handle the Payroll "force deleted" event.
     */
    public function forceDeleted(Payroll $payroll): void
    {
        //
    }
}
