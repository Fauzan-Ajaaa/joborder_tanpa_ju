<?php

namespace App\Console\Commands;

use App\Models\JournalEntry;
use Illuminate\Console\Command;

class JournalStats extends Command
{
    protected $signature = 'journals:stats {--from=} {--to=}';

    protected $description = 'Show journal entry counts and recent rows for debugging';

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        $q = JournalEntry::query();
        if ($from) $q->whereDate('transaction_date', '>=', $from);
        if ($to) $q->whereDate('transaction_date', '<=', $to);

        $count = (clone $q)->count();
        $this->info('Count: ' . $count);

        $rows = (clone $q)->orderBy('transaction_date', 'desc')->limit(5)->get(['id','journal_number','transaction_date','source_type','status','total_debit','total_credit']);
        foreach ($rows as $r) {
            $this->line(sprintf('%d | %s | %s | %s | %s | D %s / C %s', $r->id, $r->journal_number, $r->transaction_date?->toDateString(), $r->source_type, $r->status, $r->total_debit, $r->total_credit));
        }

        return self::SUCCESS;
    }
}
