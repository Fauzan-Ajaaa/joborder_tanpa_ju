<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama promo, misal "Promo Ramadhan 10%"
            $table->decimal('percentage', 5, 2)->default(0); // Bisa 0–100, 0 artinya tidak ada diskon
            $table->boolean('active')->default(true); // Aktif/non-aktif manual oleh admin
            $table->date('start_date')->nullable(); // Periode mulai (opsional)
            $table->date('end_date')->nullable();   // Periode selesai (opsional)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_discounts');
    }
};
