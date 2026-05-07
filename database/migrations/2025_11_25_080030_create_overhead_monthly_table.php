<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('overhead_monthly')) {
            return;
        }

        Schema::create('overhead_monthly', function (Blueprint $table) {
            $table->id();

            $table->string('periode', 7);

            // Optional kategori overhead, sementara tanpa foreign key untuk hindari error instalasi awal
            $table->unsignedBigInteger('overhead_category_id')->nullable();

            $table->decimal('total_biaya', 15, 2);

            $table->timestamps();

            $table->index('periode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overhead_monthly');
    }
};
