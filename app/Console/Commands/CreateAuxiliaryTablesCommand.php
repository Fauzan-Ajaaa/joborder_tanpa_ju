<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CreateAuxiliaryTablesCommand extends Command
{
    protected $signature = 'create:auxiliary-tables';
    protected $description = 'Create auxiliary materials tables manually';

    public function handle()
    {
        $this->info('Creating auxiliary materials tables...');

        // Create auxiliary_materials table
        if (!Schema::hasTable('auxiliary_materials')) {
            Schema::create('auxiliary_materials', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('unit');
                $table->string('recipe_unit')->nullable();
                $table->decimal('recipe_conversion_factor', 8, 6)->nullable();
                $table->decimal('stock', 10, 2)->default(0);
                $table->decimal('price_per_unit', 12, 2)->default(0);
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->unsignedBigInteger('chart_of_account_id')->nullable();
                $table->timestamps();
                
                $table->index('code');
                $table->index('name');
                $table->index('supplier_id');
                $table->index('chart_of_account_id');
                
                // Foreign key constraints
                $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
                $table->foreign('chart_of_account_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            });
            $this->info('✅ Table auxiliary_materials created successfully');
        } else {
            $this->info('ℹ️ Table auxiliary_materials already exists');
        }

        // Create auxiliary_material_unit_conversions table
        if (!Schema::hasTable('auxiliary_material_unit_conversions')) {
            Schema::create('auxiliary_material_unit_conversions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('auxiliary_material_id');
                $table->string('from_unit');
                $table->string('to_unit');
                $table->decimal('factor', 8, 6);
                $table->timestamps();
                
                $table->index(['auxiliary_material_id', 'from_unit', 'to_unit'], 'aux_mat_conv_index');
                
                // Foreign key constraint
                $table->foreign('auxiliary_material_id')->references('id')->on('auxiliary_materials')->onDelete('cascade');
            });
            $this->info('✅ Table auxiliary_material_unit_conversions created successfully');
        } else {
            $this->info('ℹ️ Table auxiliary_material_unit_conversions already exists');
        }

        $this->info('🎉 All auxiliary tables created successfully!');
        
        return 0;
    }
}
