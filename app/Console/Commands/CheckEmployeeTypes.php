<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;

class CheckEmployeeTypes extends Command
{
    protected $signature = 'employee:check-types';
    protected $description = 'Check and display employee types';

    public function handle()
    {
        $this->info('=== Employee Type Status ===');
        
        $nullCount = Employee::whereNull('employee_type')->count();
        $btklCount = Employee::where('employee_type', 'BTKL')->count();
        $btktlCount = Employee::where('employee_type', 'BTKTL')->count();
        
        $this->table(
            ['Type', 'Count'],
            [
                ['NULL', $nullCount],
                ['BTKL', $btklCount],
                ['BTKTL', $btktlCount],
            ]
        );
        
        if ($nullCount > 0) {
            $this->warn("Found {$nullCount} employees with NULL employee_type!");
            
            if ($this->confirm('Do you want to set them as BTKTL?')) {
                Employee::whereNull('employee_type')->update(['employee_type' => 'BTKTL']);
                $this->info('Updated successfully!');
            }
        }
        
        $this->info("\n=== BTKL Employees ===");
        $btklEmployees = Employee::where('employee_type', 'BTKL')->get(['name', 'employee_number', 'status']);
        
        if ($btklEmployees->isEmpty()) {
            $this->warn('No BTKL employees found!');
        } else {
            $this->table(
                ['Name', 'Employee Number', 'Status'],
                $btklEmployees->map(fn($e) => [$e->name, $e->employee_number, $e->status])->toArray()
            );
        }
        
        $this->info("\n=== BTKTL Employees ===");
        $btktlEmployees = Employee::where('employee_type', 'BTKTL')->get(['name', 'employee_number', 'status']);
        
        if ($btktlEmployees->isEmpty()) {
            $this->warn('No BTKTL employees found!');
        } else {
            $this->table(
                ['Name', 'Employee Number', 'Status'],
                $btktlEmployees->map(fn($e) => [$e->name, $e->employee_number, $e->status])->toArray()
            );
        }
        
        return 0;
    }
}
