<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Add missing fields if they don't exist
            if (!Schema::hasColumn('chart_of_accounts', 'code')) {
                $table->string('code')->unique()->after('id');
            }
            
            if (!Schema::hasColumn('chart_of_accounts', 'account_group_name')) {
                $table->string('account_group_name')->after('account_name');
            }
            
            if (!Schema::hasColumn('chart_of_accounts', 'description')) {
                $table->text('description')->nullable()->after('account_group_name');
            }
            
            if (!Schema::hasColumn('chart_of_accounts', 'opening_balance')) {
                $table->decimal('opening_balance', 15, 2)->default(0)->after('description');
            }
            
            if (!Schema::hasColumn('chart_of_accounts', 'normal_balance_position')) {
                $table->enum('normal_balance_position', ['debit', 'credit'])->default('debit')->after('opening_balance');
            }
            
            if (!Schema::hasColumn('chart_of_accounts', 'parent_code')) {
                $table->string('parent_code')->nullable()->after('normal_balance_position');
            }
            
            if (!Schema::hasColumn('chart_of_accounts', 'account_type')) {
                $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense'])->default('asset')->after('account_group_name');
            }
            
            // Drop old fields if they exist
            if (Schema::hasColumn('chart_of_accounts', 'account_code')) {
                $table->dropColumn('account_code');
            }
            
            if (Schema::hasColumn('chart_of_accounts', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            
            if (Schema::hasColumn('chart_of_accounts', 'balance')) {
                $table->dropColumn('balance');
            }
        });
        
        // Update existing data
        DB::statement("
            UPDATE chart_of_accounts SET 
                code = code,
                account_type = CASE 
                    WHEN SUBSTRING(code, 1, 1) = '1' THEN 'asset'
                    WHEN SUBSTRING(code, 1, 1) = '2' THEN 'liability'
                    WHEN SUBSTRING(code, 1, 1) = '3' THEN 'equity'
                    WHEN SUBSTRING(code, 1, 1) = '4' THEN 'revenue'
                    WHEN SUBSTRING(code, 1, 1) = '5' THEN 'expense'
                    ELSE 'asset'
                END,
                normal_balance_position = CASE 
                    WHEN SUBSTRING(code, 1, 1) IN ('1', '5') THEN 'debit'
                    ELSE 'credit'
                END,
                account_group_name = CASE 
                    WHEN SUBSTRING(code, 1, 1) = '1' THEN 'Aset'
                    WHEN SUBSTRING(code, 1, 1) = '2' THEN 'Kewajiban'
                    WHEN SUBSTRING(code, 1, 1) = '3' THEN 'Modal'
                    WHEN SUBSTRING(code, 1, 1) = '4' THEN 'Pendapatan'
                    WHEN SUBSTRING(code, 1, 1) = '5' THEN 'Beban'
                    ELSE 'Aset'
                END,
                opening_balance = opening_balance
            WHERE code IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Add back old fields
            $table->string('account_code')->unique()->after('id');
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0);
            
            // Drop new fields
            $table->dropColumn('code');
            $table->dropColumn('account_group_name');
            $table->dropColumn('account_type');
            $table->dropColumn('opening_balance');
            $table->dropColumn('normal_balance_position');
            $table->dropColumn('parent_code');
        });
        
        // Restore old data
        DB::statement("
            UPDATE chart_of_accounts SET 
                account_code = code,
                balance = opening_balance
            WHERE code IS NOT NULL
        ");
    }
};
