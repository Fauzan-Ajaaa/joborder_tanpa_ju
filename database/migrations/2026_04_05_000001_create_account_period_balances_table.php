<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('account_period_balances')) {
            Schema::create('account_period_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
                $table->date('period'); // first day of month: 2026-04-01
                $table->decimal('ending_balance', 20, 2)->default(0); // saldo akhir periode
                $table->decimal('total_debit', 20, 2)->default(0);
                $table->decimal('total_credit', 20, 2)->default(0);
                $table->boolean('is_posted')->default(false);
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
                $table->unique(['chart_of_account_id', 'period']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_period_balances');
    }
};
