<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddRemainingAmountCommand extends Command
{
    protected $signature = 'add:remaining-amount';
    protected $description = 'Add remaining_amount field to purchases table';

    public function handle()
    {
        $this->info('Adding remaining_amount field to purchases table...');

        if (Schema::hasColumn('purchases', 'remaining_amount')) {
            $this->info('ℹ️ remaining_amount field already exists in purchases table');
            return 0;
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('remaining_amount', 15, 2)->default(0)->after('paid_amount');
        });

        $this->info('✅ remaining_amount field added successfully to purchases table');
        
        return 0;
    }
}
