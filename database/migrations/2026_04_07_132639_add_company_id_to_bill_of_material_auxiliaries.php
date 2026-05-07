<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_of_material_auxiliaries', function (Blueprint $table) {
            if (!Schema::hasColumn('bill_of_material_auxiliaries', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bill_of_material_auxiliaries', function (Blueprint $table) {
            if (Schema::hasColumn('bill_of_material_auxiliaries', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};

