<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->decimal('snapshot_bbb', 15, 2)->nullable()->after('total_hpp');
            $table->decimal('snapshot_btkl', 15, 2)->nullable()->after('snapshot_bbb');
            $table->decimal('snapshot_bop', 15, 2)->nullable()->after('snapshot_btkl');
            $table->decimal('snapshot_hpp', 15, 2)->nullable()->after('snapshot_bop');
            $table->decimal('snapshot_btkl_rate', 10, 4)->nullable()->after('snapshot_hpp');
            $table->timestamp('snapshot_at')->nullable()->after('snapshot_btkl_rate');
        });
    }

    public function down(): void
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn([
                'snapshot_bbb',
                'snapshot_btkl',
                'snapshot_bop',
                'snapshot_hpp',
                'snapshot_btkl_rate',
                'snapshot_at',
            ]);
        });
    }
};
