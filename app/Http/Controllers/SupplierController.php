<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::latest()->paginate(15);
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        // Preview kode supplier berikutnya (tidak wajib 100% akurat jika ada insert paralel)
        $todayCount = Supplier::whereDate('created_at', today())->count() + 1;
        $nextCode = 'SUP-' . now()->format('Ymd') . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

        return view('suppliers.create', [
            'nextCode' => $nextCode,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'contact_person' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'address' => 'nullable',
            'city' => 'nullable',
            'province' => 'nullable',
            'postal_code' => 'nullable',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil ditambahkan');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load('rawMaterials');
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required',
            'contact_person' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'address' => 'nullable',
            'city' => 'nullable',
            'province' => 'nullable',
            'postal_code' => 'nullable',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil diupdate');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier berhasil dihapus');
    }

    /**
     * Search suppliers for autocomplete
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $suppliers = Supplier::where('status', 'active')
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('code', 'LIKE', "%{$query}%")
                  ->orWhere('contact_person', 'LIKE', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'code', 'name', 'contact_person', 'phone']);
            
        return response()->json($suppliers);
    }

    /**
     * Create new supplier via AJAX
     */
    public function createAjax(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'contact_person' => 'nullable',
            'phone' => 'nullable',
            'email' => 'nullable|email',
            'address' => 'nullable',
            'city' => 'nullable',
            'province' => 'nullable',
            'postal_code' => 'nullable',
        ]);

        $supplier = Supplier::create(array_merge($validated, ['status' => 'active']));
        
        return response()->json([
            'success' => true,
            'supplier' => $supplier
        ]);
    }
}
