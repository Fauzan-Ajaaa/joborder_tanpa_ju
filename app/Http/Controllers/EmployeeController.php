<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::paginate(15);
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_number' => ['nullable', Rule::unique('employees', 'employee_number')],
            'name' => 'required',
            'position' => 'required',
            'department' => 'nullable',
            'phone' => 'nullable',
            'email' => ['nullable', 'email', Rule::unique('employees', 'email')],
            'address' => 'nullable',
            'base_salary' => 'required_if:employee_type,BTKTL|nullable|numeric|min:0',
            'hire_date' => 'required|date',
            'status' => 'required|in:active,inactive',
            'employee_type' => 'required|in:BTKL,BTKTL',
            'jam_kerja_per_bulan' => 'nullable|numeric|min:0',
            'tarif_per_jam' => 'required_if:employee_type,BTKL|nullable|numeric|min:0',
        ]);

        // Untuk BTKL: base_salary = tarif_per_jam × jam_kerja_per_bulan
        if (($validated['employee_type'] ?? '') === 'BTKL') {
            $validated['base_salary'] = ($validated['tarif_per_jam'] ?? 0) * ($validated['jam_kerja_per_bulan'] ?? 0);
        }

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil ditambahkan');
    }

    public function show(Employee $employee)
    {
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required',
            'position' => 'required',
            'department' => 'nullable',
            'phone' => 'nullable',
            'address' => 'nullable',
            'base_salary' => 'required_if:employee_type,BTKTL|nullable|numeric|min:0',
            'hire_date' => 'required|date',
            'status' => 'required|in:active,inactive',
            'employee_type' => 'required|in:BTKL,BTKTL',
            'jam_kerja_per_bulan' => 'nullable|numeric|min:0',
            'tarif_per_jam' => 'required_if:employee_type,BTKL|nullable|numeric|min:0',
        ]);

        // Untuk BTKL: base_salary = tarif_per_jam × jam_kerja_per_bulan
        if (($validated['employee_type'] ?? '') === 'BTKL') {
            $validated['base_salary'] = ($validated['tarif_per_jam'] ?? 0) * ($validated['jam_kerja_per_bulan'] ?? 0);
        }

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil diupdate');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Karyawan berhasil dihapus');
    }
}
