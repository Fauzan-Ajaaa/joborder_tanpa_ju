<?php

namespace App\Observers;

use App\Models\BiayaOverhead;
use App\Models\OverheadMonthly;
use App\Models\OverheadPor;

class BiayaOverheadObserver
{
    /**
     * Handle the BiayaOverhead "created" event.
     */
    public function created(BiayaOverhead $biayaOverhead): void
    {
        // Cek apakah jurnal sudah ada untuk mencegah duplikasi
        $existingJournal = \App\Models\JournalEntry::where('source_type', 'overhead')
            ->where('source_id', $biayaOverhead->id)
            ->first();

        if (!$existingJournal) {
            // SKIP jurnal otomatis untuk:
            // 1. Bahan penolong dari pembelian (BOP-BP-*)
            // 2. Gaji BTKTL dari distribusi (BOP BTKL - *)
            // Karena jurnal sudah dibuat sebelumnya (pembelian/distribusi)
            
            $isBahanPenolongFromPurchase = $biayaOverhead->kategori === 'BOP' 
                && strpos($biayaOverhead->bop_code, 'BOP-BP-') === 0;
            
            $isGajiBTKTLFromDistribution = $biayaOverhead->kategori === 'BOP'
                && (
                    // Check by account name
                    ($biayaOverhead->account && strpos($biayaOverhead->account->account_name, 'BOP BTKL -') === 0)
                    // Check by bop_code (kode COA 54xx = BTKTL)
                    || (is_numeric($biayaOverhead->bop_code) && str_starts_with((string)$biayaOverhead->bop_code, '54'))
                    // Check by jenis_biaya
                    || strpos($biayaOverhead->jenis_biaya ?? '', 'Pembayaran Gaji') !== false
                );
            
            if (!$isBahanPenolongFromPurchase && !$isGajiBTKTLFromDistribution) {
                // Auto-generate jurnal saat biaya overhead dibuat (untuk overhead manual)
                try {
                    $journalService = app(\App\Services\JournalService::class);
                    $journalService->createJournalFromOverhead($biayaOverhead);
                } catch (\Exception $e) {
                    \Log::error('Failed to create journal from overhead: ' . $e->getMessage());
                }
            }
        }

        $this->syncMonthlyOverhead($biayaOverhead->periode);
    }

    /**
     * Handle the BiayaOverhead "updated" event.
     */
    public function updated(BiayaOverhead $biayaOverhead): void
    {
        $this->syncMonthlyOverhead($biayaOverhead->periode);
    }

    /**
     * Handle the BiayaOverhead "deleted" event.
     */
    public function deleted(BiayaOverhead $biayaOverhead): void
    {
        $this->syncMonthlyOverhead($biayaOverhead->periode);
    }

    /**
     * Handle the BiayaOverhead "restored" event.
     */
    public function restored(BiayaOverhead $biayaOverhead): void
    {
        //
    }

    /**
     * Handle the BiayaOverhead "force deleted" event.
     */
    public function forceDeleted(BiayaOverhead $biayaOverhead): void
    {
        $this->syncMonthlyOverhead($biayaOverhead->periode);
    }

    /**
     * Rekap total biaya overhead per bulan ke tabel overhead_monthly.
     */
    protected function syncMonthlyOverhead($periodeDate): void
    {
        if (! $periodeDate) {
            return;
        }

        $year = (int) $periodeDate->format('Y');
        $month = (int) $periodeDate->format('m');
        $periodeKey = $periodeDate->format('Y-m');

        $total = BiayaOverhead::whereYear('periode', $year)
            ->whereMonth('periode', $month)
            ->where('kategori', 'BOP')
            ->sum('total');

        if ($total > 0) {
            OverheadMonthly::updateOrCreate(
                ['periode' => $periodeKey],
                ['total_biaya' => $total]
            );

            // Sinkronkan juga total_overhead_bulanan di OverheadPerbulan (POR) untuk periode ini
            OverheadPor::where('periode', $periodeKey)
                ->update(['total_overhead_bulanan' => $total]);
        } else {
            OverheadMonthly::where('periode', $periodeKey)->delete();

            OverheadPor::where('periode', $periodeKey)
                ->update(['total_overhead_bulanan' => 0]);
        }
    }
}
