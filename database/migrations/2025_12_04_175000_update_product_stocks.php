<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update product stocks to reasonable values for demo
        DB::table('products')->update([
            'stock' => 100
        ]);
        
        // Set specific stocks for known products
        DB::table('products')->where('name', 'LIKE', '%Brownies%')->update(['stock' => 50]);
        DB::table('products')->where('name', 'LIKE', '%Cake%')->update(['stock' => 30]);
        DB::table('products')->where('name', 'LIKE', '%Cookies%')->update(['stock' => 75]);
    }

    public function down(): void
    {
        // Reset all stocks to 0
        DB::table('products')->update(['stock' => 0]);
    }
};
