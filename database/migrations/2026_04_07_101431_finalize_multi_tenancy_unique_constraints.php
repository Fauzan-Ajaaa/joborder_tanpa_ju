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
        // 1. Suppliers: email, phone, code (unique per company)
        Schema::table('suppliers', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM suppliers'))->pluck('Key_name');
            if ($indexes->contains('suppliers_email_unique')) $table->dropUnique(['email']);
            if ($indexes->contains('suppliers_phone_unique')) $table->dropUnique(['phone']);
            if ($indexes->contains('suppliers_code_unique')) $table->dropUnique(['code']);
            
            if (!$indexes->contains('suppliers_email_company_id_unique')) $table->unique(['email', 'company_id']);
            if (!$indexes->contains('suppliers_phone_company_id_unique')) $table->unique(['phone', 'company_id']);
            if (!$indexes->contains('suppliers_code_company_id_unique')) $table->unique(['code', 'company_id']);
        });

        // 2. Customers: email, phone, code (unique per company)
        Schema::table('customers', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM customers'))->pluck('Key_name');
            if ($indexes->contains('customers_email_unique')) $table->dropUnique(['email']);
            if ($indexes->contains('customers_phone_unique')) $table->dropUnique(['phone']);
            if ($indexes->contains('customers_code_unique')) $table->dropUnique(['code']);
            
            if (!$indexes->contains('customers_email_company_id_unique')) $table->unique(['email', 'company_id']);
            if (!$indexes->contains('customers_phone_company_id_unique')) $table->unique(['phone', 'company_id']);
            if (!$indexes->contains('customers_code_company_id_unique')) $table->unique(['code', 'company_id']);
        });

        // 3. Products: code (unique per company)
        Schema::table('products', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM products'))->pluck('Key_name');
            if ($indexes->contains('products_code_unique')) $table->dropUnique(['code']);
            if (!$indexes->contains('products_code_company_id_unique')) $table->unique(['code', 'company_id']);
        });

        // 4. Raw Materials: code (unique per company)
        Schema::table('raw_materials', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM raw_materials'))->pluck('Key_name');
            if ($indexes->contains('raw_materials_code_unique')) $table->dropUnique(['code']);
            if (!$indexes->contains('raw_materials_code_company_id_unique')) $table->unique(['code', 'company_id']);
        });

        // 5. Auxiliary Materials: code (unique per company)
        Schema::table('auxiliary_materials', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM auxiliary_materials'))->pluck('Key_name');
            if ($indexes->contains('auxiliary_materials_code_unique')) $table->dropUnique(['code']);
            if (!$indexes->contains('auxiliary_materials_code_company_id_unique')) $table->unique(['code', 'company_id']);
        });

        // 6. Employees: employee_number, email, phone (unique per company)
        Schema::table('employees', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM employees'))->pluck('Key_name');
            if ($indexes->contains('employees_email_unique')) $table->dropUnique(['email']);
            if ($indexes->contains('employees_phone_unique')) $table->dropUnique(['phone']);
            if ($indexes->contains('employees_employee_number_unique')) $table->dropUnique(['employee_number']);
            
            if (!$indexes->contains('employees_email_company_id_unique')) $table->unique(['email', 'company_id']);
            if (!$indexes->contains('employees_phone_company_id_unique')) $table->unique(['phone', 'company_id']);
            if (!$indexes->contains('employees_employee_number_company_id_unique')) $table->unique(['employee_number', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
