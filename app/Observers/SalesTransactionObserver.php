<?php

namespace App\Observers;

use App\Models\SalesTransaction;
use App\Services\JournalService;
use Illuminate\Support\Facades\Log;

class SalesTransactionObserver
{
    /**
     * Handle the SalesTransaction "created" event.
     */
    public function created(SalesTransaction $salesTransaction): void
    {
        // Skip auto-generate jurnal - journal dibuat di controller
        \Log::info("SalesTransaction created: {$salesTransaction->transaction_number} - Journal handled by controller");
    }

    /**
     * Handle the SalesTransaction "updated" event.
     */
    public function updated(SalesTransaction $salesTransaction): void
    {
        // Auto update status to completed when payment is paid
        if ($salesTransaction->wasChanged('payment_status') && $salesTransaction->payment_status === 'paid') {
            $salesTransaction->status = 'completed';
            $salesTransaction->saveQuietly(); // Save without triggering observer again
        }
    }

    /**
     * Handle the SalesTransaction "deleted" event.
     */
    public function deleted(SalesTransaction $salesTransaction): void
    {
        //
    }

    /**
     * Handle the SalesTransaction "restored" event.
     */
    public function restored(SalesTransaction $salesTransaction): void
    {
        //
    }

    /**
     * Handle the SalesTransaction "force deleted" event.
     */
    public function forceDeleted(SalesTransaction $salesTransaction): void
    {
        //
    }
}
