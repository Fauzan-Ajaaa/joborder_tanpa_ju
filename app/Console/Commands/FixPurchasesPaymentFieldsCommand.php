<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class FixPurchasesPaymentFieldsCommand extends Command
{
    protected $signature = 'fix:purchases-payment';
    protected $description = 'Add payment fields to purchases table manually';

    public function handle()
    {
        $this->info('Adding payment fields to purchases table...');

        if (Schema::hasColumn('purchases', 'payment_method')) {
            $this->info('ℹ️ Payment fields already exist in purchases table');
            return 0;
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'credit'])->default('cash')->after('total_amount');
            $table->date('due_date')->nullable()->after('payment_method');
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending')->after('due_date');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('payment_status');
        });

        $this->info('✅ Payment fields added successfully to purchases table');
        
        return 0;
    }
}
