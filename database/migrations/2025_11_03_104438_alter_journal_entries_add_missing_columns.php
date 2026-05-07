<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('journal_entries', 'journal_number')) {
                $table->string('journal_number')->nullable()->unique();
            }
            if (!Schema::hasColumn('journal_entries', 'transaction_date')) {
                $table->date('transaction_date')->nullable()->index();
            }
            if (!Schema::hasColumn('journal_entries', 'source_type')) {
                $table->string('source_type')->nullable()->index();
            }
            if (!Schema::hasColumn('journal_entries', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->index();
            }
            if (!Schema::hasColumn('journal_entries', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('journal_entries', 'total_debit')) {
                $table->decimal('total_debit', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('journal_entries', 'total_credit')) {
                $table->decimal('total_credit', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('journal_entries', 'status')) {
                $table->enum('status', ['draft', 'posted', 'void'])->default('posted')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (Schema::hasColumn('journal_entries', 'journal_number')) {
                $table->dropColumn('journal_number');
            }
            if (Schema::hasColumn('journal_entries', 'transaction_date')) {
                $table->dropColumn('transaction_date');
            }
            if (Schema::hasColumn('journal_entries', 'source_type')) {
                $table->dropColumn('source_type');
            }
            if (Schema::hasColumn('journal_entries', 'source_id')) {
                $table->dropColumn('source_id');
            }
            if (Schema::hasColumn('journal_entries', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('journal_entries', 'total_debit')) {
                $table->dropColumn('total_debit');
            }
            if (Schema::hasColumn('journal_entries', 'total_credit')) {
                $table->dropColumn('total_credit');
            }
            if (Schema::hasColumn('journal_entries', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
