<?php

namespace App\Observers;

use App\Models\Penggajian;
use App\Services\JournalService;
use Illuminate\Support\Facades\Log;

class PenggajianObserver
{
    /**
     * Handle the Penggajian "created" event.
     */
    public function created(Penggajian $penggajian): void
    {
        // Jurnal dibuat manual via tombol Pengakuan Gaji di halaman detail
        // Tidak auto-create saat penggajian dibuat
        Log::info("PenggajianObserver: Penggajian created ID: " . $penggajian->id_gaji . " - jurnal akan dibuat manual");
    }

    /**
     * Handle the Penggajian "updated" event.
     */
    public function updated(Penggajian $penggajian): void
    {
        // Jika status berubah menjadi 1 (dibayar), buat jurnal pembayaran (utang gaji -> kas)
        if ($penggajian->wasChanged('status') && $penggajian->status == 1) {
            try {
                Log::info("PenggajianObserver: Status changed to paid for ID: " . $penggajian->id_gaji);
                $this->createPaymentJournal($penggajian);
                Log::info("PenggajianObserver: Payment journal created successfully for ID: " . $penggajian->id_gaji);
            } catch (\Exception $e) {
                Log::error('Failed to create payment journal from penggajian: ' . $e->getMessage());
            }
        }
    }

    /**
     * Create journal entry when penggajian is created
     */
    private function createJournalEntry(Penggajian $penggajian, string $event): void
    {
        $journalService = app(JournalService::class);
        $journalService->createJournalFromPenggajian($penggajian);
    }

    /**
     * Create payment journal when penggajian is paid
     */
    private function createPaymentJournal(Penggajian $penggajian): void
    {
        $journalService = app(JournalService::class);
        $journalService->createJournalFromPenggajianPayment($penggajian);
    }
}
