<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateEmployeeTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update semua employee yang belum punya employee_type menjadi BTKTL
        DB::table('employees')
            ->whereNull('employee_type')
            ->update(['employee_type' => 'BTKTL']);
        
        $this->command->info('Employee types updated successfully!');
    }
}
