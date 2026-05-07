<?php

namespace App\Http\Controllers;

use App\Models\AuxiliaryMaterial;
use App\Models\Supplier;
use App\Models\AuxiliaryMaterialUnitConversion;
use App\Models\Unit;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BahanPenolongController extends Controller
{
    public function index()
    {
        $auxiliaryMaterials = AuxiliaryMaterial::with(['supplier', 'unitConversions', 'chartOfAccount'])->paginate(15);
        $unitMap = Unit::pluck('name', 'code'); // [code => name]
        return view('auxiliary-materials.index', compact('auxiliaryMaterials', 'unitMap'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $units = Unit::where('is_active', true)->orderBy('code')->get();
        $nextCode = AuxiliaryMaterial::generateCode();
        return view('auxiliary-materials.create', compact('suppliers', 'units', 'nextCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|unique:auxiliary_materials,code',
            'name' => 'required',
            'description' => 'nullable',
            'unit' => 'required',
            'recipe_unit' => 'nullable|string',
            'recipe_conversion_factor' => 'nullable|numeric|min:0.000001',
            'price_per_unit' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = AuxiliaryMaterial::generateCode();
        }

        // Gunakan DB transaction untuk memastikan konsistensi data
        $auxiliaryMaterial = DB::transaction(function () use ($validated, $request) {
            // 1. Simpan auxiliary material
            $auxiliaryMaterial = AuxiliaryMaterial::create($validated);

            // 2. Buat COA otomatis untuk auxiliary material
            $coaAccount = ChartOfAccount::createAuxiliaryMaterialAccount($validated['name']);
            
            if ($coaAccount) {
                // 3. Update auxiliary material dengan COA ID
                $auxiliaryMaterial->update(['chart_of_account_id' => $coaAccount->id]);
                \Log::info('COA created for auxiliary material: ' . $validated['name'] . ' with code: ' . $coaAccount->code);
            } else {
                \Log::warning('Failed to create COA for auxiliary material: ' . $validated['name']);
            }

            // 4. Simpan konversi multi-satuan jika ada
            $conversions = $request->input('conversions', []);
            if (is_array($conversions) && $auxiliaryMaterial->unit) {
                foreach ($conversions as $conv) {
                    $factor = isset($conv['factor']) ? (float) $conv['factor'] : 0;
                    $toUnit = $conv['to_unit'] ?? null;
                    if ($factor > 0 && $toUnit) {
                        AuxiliaryMaterialUnitConversion::create([
                            'auxiliary_material_id' => $auxiliaryMaterial->id,
                            'from_unit' => $auxiliaryMaterial->unit,
                            'to_unit' => $toUnit,
                            'factor' => $factor,
                        ]);
                    }
                }
            }

            return $auxiliaryMaterial;
        });

        $message = 'Bahan penolong berhasil ditambahkan';
        
        // Tambahkan info COA jika berhasil dibuat
        $coaAccount = ChartOfAccount::find($auxiliaryMaterial->chart_of_account_id);
        if ($coaAccount) {
            $message .= ' (COA: ' . $coaAccount->code . ' - ' . $coaAccount->account_name . ')';
        }

        return redirect()->route('auxiliary-materials.index')->with('success', $message);
    }

    public function show(AuxiliaryMaterial $auxiliaryMaterial)
    {
        $auxiliaryMaterial->load(['supplier', 'chartOfAccount']);
        return view('auxiliary-materials.show', compact('auxiliaryMaterial'));
    }

    public function edit(AuxiliaryMaterial $auxiliaryMaterial)
    {
        $auxiliaryMaterial->load('unitConversions');
        $suppliers = Supplier::all();
        $units = Unit::where('is_active', true)->orderBy('code')->get();
        return view('auxiliary-materials.edit', compact('auxiliaryMaterial', 'suppliers', 'units'));
    }

    public function update(Request $request, AuxiliaryMaterial $auxiliaryMaterial)
    {
        $validated = $request->validate([
            'code' => 'required|unique:auxiliary_materials,code,' . $auxiliaryMaterial->id,
            'name' => 'required',
            'description' => 'nullable',
            'unit' => 'required',
            'recipe_unit' => 'nullable|string',
            'recipe_conversion_factor' => 'nullable|numeric|min:0.000001',
            'price_per_unit' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        // Jika satuan dasar berubah, gunakan mekanisme konversi agar stok dan transaksi ikut menyesuaikan
        if ($validated['unit'] !== $auxiliaryMaterial->unit) {
            $auxiliaryMaterial->convertUnit($validated['unit']);

            // Setelah konversi, kita tidak ingin menimpa unit/stock/price_per_unit hasil hitungan
            unset($validated['unit'], $validated['stock'], $validated['price_per_unit']);
        }

        $auxiliaryMaterial->update($validated);

        // Sync konversi multi-satuan
        $conversions = $request->input('conversions', []);
        $auxiliaryMaterial->unitConversions()->delete();
        if (is_array($conversions) && $auxiliaryMaterial->unit) {
            foreach ($conversions as $conv) {
                $factor = isset($conv['factor']) ? (float) $conv['factor'] : 0;
                $toUnit = $conv['to_unit'] ?? null;
                if ($factor > 0 && $toUnit) {
                    AuxiliaryMaterialUnitConversion::create([
                        'auxiliary_material_id' => $auxiliaryMaterial->id,
                        'from_unit' => $auxiliaryMaterial->unit,
                        'to_unit' => $toUnit,
                        'factor' => $factor,
                    ]);
                }
            }
        }

        return redirect()->route('auxiliary-materials.index')->with('success', 'Bahan penolong berhasil diupdate');
    }

    public function destroy(AuxiliaryMaterial $auxiliaryMaterial)
    {
        // Cek apakah bahan penolong sudah dipakai di transaksi lain
        // Note: Tambahkan relasi ke tabel yang menggunakan auxiliary material
        
        $auxiliaryMaterial->delete();

        return redirect()->route('auxiliary-materials.index')->with('success', 'Bahan penolong berhasil dihapus');
    }

    public function updateUnit(Request $request, AuxiliaryMaterial $auxiliaryMaterial)
    {
        $validated = $request->validate([
            'unit' => 'required|in:' . implode(',', array_keys(AuxiliaryMaterial::UNIT_OPTIONS)),
        ]);

        $converted = $auxiliaryMaterial->convertUnit($validated['unit']);

        if (! $converted) {
            return redirect()->route('auxiliary-materials.index')->with('error', 'Konversi satuan tidak didukung.');
        }

        return redirect()->route('auxiliary-materials.index')->with('success', 'Satuan dan stok bahan penolong berhasil diupdate');
    }
}
