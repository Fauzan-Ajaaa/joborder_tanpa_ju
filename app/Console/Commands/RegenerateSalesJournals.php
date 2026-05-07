<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SalesTransaction;
use App\Models\JournalEntry;
use App\Services\JournalService;

class RegenerateSalesJournals extends Command
{
    protected $signature = 'journal:regenerate-sales {sales_id?}';
    protected $description = 'Regenerate missing sales journals';

    public function handle()
    {
        $salesId = $this->argument('sales_id');
        
        if ($salesId) {
            $sales = SalesTransaction::find($salesId);
            if (!$sales) {
                $this->error("Sales transaction #{$salesId} not found");
                return 1;
            }
            $salesTransactions = collect([$sales]);
        } else {
            $salesTransactions = SalesTransaction::all();
        }
        
        $this->info("Checking " . $salesTransactions->count() . " sales transactions...");
        
        $regenerated = 0;
        $skipped = 0;
        
        foreach ($salesTransactions as $sales) {
            // Cek apakah jurnal penjualan ada
            $salesJournal = JournalEntry::where('source_type', 'sales')
                ->where('source_id', $sales->id)
                ->first();
            
            // Cek apakah jurnal HPP ada
            $hppJournal = JournalEntry::where('source_type', 'hpp')
                ->where('source_id', $sales->id)
                ->first();
            
            if (!$salesJournal) {
                $this->warn("Sales #{$sales->id} ({$sales->transaction_number}) - Missing sales journal, regenerating...");
                
                try {
                    $journalService = app(JournalService::class);
                    $journal = $journalService->createJournalFromSales($sales);
                    $this->info("  ✓ Created sales journal: {$journal->journal_number}");
                    $regenerated++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed: " . $e->getMessage());
                }
            } else {
                $this->line("Sales #{$sales->id} - OK (Journal: {$salesJournal->journal_number})");
                $skipped++;
            }
            
            if (!$hppJournal) {
                $this->warn("Sales #{$sales->id} ({$sales->transaction_number}) - Missing HPP journal, regenerating...");
                
                try {
                    $journalService = app(JournalService::class);
                    $journal = $journalService->createHppJournal($sales);
                    if ($journal) {
                        $this->info("  ✓ Created HPP journal: {$journal->journal_number}");
                        $regenerated++;
                    } else {
                        $this->warn("  - No HPP journal needed (no job order or HPP = 0)");
                    }
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed: " . $e->getMessage());
                }
            }
        }
        
        $this->info("\nSummary:");
        $this->info("  Regenerated: {$regenerated}");
        $this->info("  Skipped: {$skipped}");
        
        return 0;
    }
}
