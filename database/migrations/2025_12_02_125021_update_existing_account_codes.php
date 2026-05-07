<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    $accounts = \App\Models\ChartOfAccount::all();
    
    foreach ($accounts as $account) {
        // Gunakan mutator yang sudah dibuat di model
        $account->account_code = $account->account_code;
        $account->save();
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
