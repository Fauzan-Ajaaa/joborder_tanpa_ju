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
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('fob_type', ['shipping_point', 'destination'])
                ->default('shipping_point')
                ->after('supplier_id')
                ->comment('Shipping Point: pembeli bayar ongkir, Destination: penjual bayar ongkir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('fob_type');
        });
    }
};
