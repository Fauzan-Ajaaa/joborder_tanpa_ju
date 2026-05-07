<?php

namespace App\Console\Commands;

use App\Models\BiayaOverhead;
use App\Models\Payroll;
use App\Models\SalesTransaction;
use App\Models\Transaction;
use App\Services\JournalService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillJournals extends Command
{
    protected $signature = 'journals:backfill {--from=} {--to=} {--only=}';

    protected $description = 'Generate missing journal entries from existing transactions (sales, purchase, payroll, overhead)';

    public function handle(JournalService $journalService): int
    {
        $from = $this->option('from') ? Carbon::parse($this->option('from'))->startOfDay() : null;
        $to = $this->option('to') ? Carbon::parse($this->option('to'))->endOfDay() : null;
        $only = $this->option('only');

        $this->info('Backfilling journals...');
        if ($from || $to) {
            $this->line('Range: ' . ($from?->toDateString() ?? '-') . ' to ' . ($to?->toDateString() ?? '-'));
        }

        $created = 0;
        $skipped = 0;
        $errors = 0;

        $run = function ($collection, callable $create) use (&$created, &$skipped, &$errors) {
            foreach ($collection as $model) {
                try {
                    $journal = $create($model);
                    if ($journal->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error($e->getMessage());
                }
            }
        };

        // Sales
        if (!$only || $only === 'sales') {
            $q = SalesTransaction::query();
            if ($from) {
                $q->whereDate('transaction_date', '>=', $from);
            }
            if ($to) {
                $q->whereDate('transaction_date', '<=', $to);
            }
            $this->line('Processing sales...');
            $run($q->get(), fn ($m) => $journalService->createJournalFromSales($m));
        }

        // Purchase
        if (!$only || $only === 'purchase') {
            $q = Transaction::query()->where('type', 'purchase');
            if ($from) {
                $q->whereDate('transaction_date', '>=', $from);
            }
            if ($to) {
                $q->whereDate('transaction_date', '<=', $to);
            }
            $this->line('Processing purchases...');
            $run($q->get(), fn ($m) => $journalService->createJournalFromPurchase($m));
        }

        // Payroll
        if (!$only || $only === 'payroll') {
            $q = Payroll::query();
            if ($from) {
                $q->whereDate('work_date', '>=', $from);
            }
            if ($to) {
                $q->whereDate('work_date', '<=', $to);
            }
            $this->line('Processing payrolls...');
            $run($q->get(), fn ($m) => $journalService->createJournalFromPayroll($m));
        }

        // Overhead
        if (!$only || $only === 'overhead') {
            $q = BiayaOverhead::query();
            if ($from) {
                $q->whereDate('periode', '>=', $from);
            }
            if ($to) {
                $q->whereDate('periode', '<=', $to);
            }
            $this->line('Processing overheads...');
            $run($q->get(), fn ($m) => $journalService->createJournalFromOverhead($m));
        }

        $this->info("Done. Created: {$created}, Skipped: {$skipped}, Errors: {$errors}");
        return self::SUCCESS;
    }
}
