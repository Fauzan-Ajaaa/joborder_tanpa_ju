<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->paginate(10);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('customers', 'code')],
            'name' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')],
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = Customer::generateCode();
        }

        Customer::create($validated);

        return redirect()->route('customers.index')
                        ->with('success', 'Pelanggan berhasil ditambahkan');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
                        ->with('success', 'Data pelanggan berhasil diperbarui');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')
                        ->with('success', 'Pelanggan berhasil dihapus');
    }

    public function search(Request $request)
    {
        $search = $request->input('search');
        
        return Customer::when($search, function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->select('id', 'code', 'name', 'phone', 'email')
            ->limit(10)
            ->get();
    }
}