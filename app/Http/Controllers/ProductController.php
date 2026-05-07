<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\AuxiliaryMaterial;
use App\Models\BillOfMaterial;
use App\Models\JobOrderDetail;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::paginate(15);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $unitMap = Unit::pluck('name', 'code');
        $rawMaterials = RawMaterial::with('unitConversions')->get()->map(function ($m) use ($unitMap) {
            $codes = [$m->unit];
            foreach ($m->unitConversions as $conv) {
                if (!empty($conv->to_unit)) {
                    $codes[] = $conv->to_unit;
                }
            }
            $codes = array_values(array_unique($codes));

            $m->available_units = collect($codes)->map(function ($code) use ($unitMap, $m) {
                return [
                    'code' => $code,
                    'label' => $unitMap[$code] ?? $code,
                    'factor_from_base' => $code === $m->unit ? 1.0 : $m->getMaterialConversionFactor($m->unit, $code),
                ];
            })->values();

            return $m;
        });

        $auxiliaryMaterials = AuxiliaryMaterial::with('unitConversions')->get()->map(function ($m) {
            $codes = [$m->unit];
            foreach ($m->unitConversions as $conv) {
                if (!empty($conv->from_unit)) {
                    $codes[] = $conv->from_unit;
                }
                if (!empty($conv->to_unit)) {
                    $codes[] = $conv->to_unit;
                }
            }
            if (!empty($m->recipe_unit) && !in_array($m->recipe_unit, $codes)) {
                $codes[] = $m->recipe_unit;
            }
            $codes = array_values(array_unique(array_filter($codes)));
            if (empty($codes) && !empty($m->unit)) {
                $codes = [$m->unit];
            }

            $m->available_units = collect($codes)->map(function ($code) use ($m) {
                $factor = $m->getMaterialConversionFactor($m->unit, $code);
                return [
                    'code' => $code,
                    'label' => AuxiliaryMaterial::UNIT_OPTIONS[$code] ?? $code,
                    'factor_from_base' => $factor ?? 1.0,
                ];
            })->values()->all();
            return $m;
        });

        $nextCode = Product::generateCode();
        $nextBarcode = 'PROD' . str_pad((Product::withoutGlobalScopes()->max('id') ?? 0) + 1, 10, '0', STR_PAD_LEFT);

        return view('products.create', compact('rawMaterials', 'auxiliaryMaterials', 'nextCode', 'nextBarcode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_name' => 'required',
            'description' => 'nullable',
            'selling_price' => 'required|numeric|min:0',
            'bom_detail_id' => 'nullable|exists:bom_details,id',
            'barcode' => 'nullable|string|max:50|unique:products,barcode',
            'product_image' => 'nullable|image|mimes:png,jpg,jpeg|max:10240',
            'auxiliary_materials' => 'array',
            'auxiliary_materials.*.auxiliary_material_id' => 'nullable|exists:auxiliary_materials,id',
            'auxiliary_materials.*.quantity_needed' => 'nullable|numeric|min:0',
            'auxiliary_materials.*.unit' => 'nullable|string|max:50',
        ]);

        $product = Product::create([
            'name' => $validated['product_name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['selling_price'],
            'bom_detail_id' => $validated['bom_detail_id'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
        ]);

        // Otomatis buat COA Persediaan Barang Jadi untuk produk ini
        \App\Models\ChartOfAccount::createProductAccount($validated['product_name']);

        if ($request->hasFile('product_image')) {
            $image = $request->file('product_image');
            $mime = $image->getMimeType();
            $data = file_get_contents($image->getRealPath());
            $product->update([
                'image_data' => $data,
                'image_mime_type' => $mime,
            ]);
        }


        if (isset($validated['auxiliary_materials'])) {
            foreach ($validated['auxiliary_materials'] as $aux) {
                if (empty($aux['auxiliary_material_id']) || empty($aux['quantity_needed'])) {
                    continue;
                }

                $product->auxiliaryMaterials()->attach($aux['auxiliary_material_id'], [
                    'quantity_needed' => $aux['quantity_needed'],
                    'unit' => $aux['unit'] ?? null,
                ]);
            }
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan');
    }

    public function show(Product $product)
    {
        $product->load('auxiliaryMaterials');
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load('auxiliaryMaterials');
        $unitMap = Unit::where('is_active', true)->pluck('name', 'code');
        $rawMaterials = RawMaterial::with('unitConversions')->get()->map(function ($m) use ($unitMap) {
            $codes = [$m->unit];
            foreach ($m->unitConversions as $conv) {
                if (!empty($conv->to_unit)) {
                    $codes[] = $conv->to_unit;
                }
            }
            $codes = array_values(array_unique($codes));

            $m->available_units = collect($codes)->map(function ($code) use ($unitMap, $m) {
                return [
                    'code' => $code,
                    'label' => $unitMap[$code] ?? $code,
                    'factor_from_base' => $code === $m->unit ? 1.0 : $m->getMaterialConversionFactor($m->unit, $code),
                ];
            })->values();

            return $m;
        });

        $auxiliaryMaterials = AuxiliaryMaterial::with('unitConversions')->get()->map(function ($m) {
            $codes = [$m->unit];
            foreach ($m->unitConversions as $conv) {
                if (!empty($conv->from_unit)) {
                    $codes[] = $conv->from_unit;
                }
                if (!empty($conv->to_unit)) {
                    $codes[] = $conv->to_unit;
                }
            }
            if (!empty($m->recipe_unit) && !in_array($m->recipe_unit, $codes)) {
                $codes[] = $m->recipe_unit;
            }
            $codes = array_values(array_unique(array_filter($codes)));
            if (empty($codes) && !empty($m->unit)) {
                $codes = [$m->unit];
            }

            $m->available_units = collect($codes)->map(function ($code) use ($m) {
                $factor = $m->getMaterialConversionFactor($m->unit, $code);
                return [
                    'code' => $code,
                    'label' => AuxiliaryMaterial::UNIT_OPTIONS[$code] ?? $code,
                    'factor_from_base' => $factor ?? 1.0,
                ];
            })->values()->all();
            return $m;
        });

        return view('products.edit', compact('product', 'rawMaterials', 'auxiliaryMaterials'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'product_name' => 'required',
            'description' => 'nullable',
            'selling_price' => 'required|numeric|min:0',
            'bom_detail_id' => 'nullable|exists:bom_details,id',
            'product_image' => 'nullable|image|mimes:png,jpg,jpeg|max:10240',
            'auxiliary_materials' => 'nullable|array',
            'auxiliary_materials.*.auxiliary_material_id' => 'nullable|exists:auxiliary_materials,id',
            'auxiliary_materials.*.quantity_needed' => 'nullable|numeric|min:0',
            'auxiliary_materials.*.unit' => 'nullable|string|max:50',
        ]);

        $newPrice = (float) $validated['selling_price'];
        $priceChanged = $newPrice !== (float) $product->price;

        $product->update([
            'name' => $validated['product_name'],
            'description' => $validated['description'] ?? null,
            'price' => $newPrice,
            'bom_detail_id' => $validated['bom_detail_id'] ?? null,
            'barcode' => $validated['barcode'] ?? $product->barcode,
        ]);

        // Sync harga jual ke BOM dan job order details yang belum selesai
        if ($priceChanged) {
            BillOfMaterial::where('product_id', $product->id)
                ->update(['selling_price' => $newPrice]);

            JobOrderDetail::where('product_id', $product->id)
                ->whereHas('jobOrder', fn($q) => $q->whereIn('status', ['draft', 'in_progress']))
                ->update(['unit_price' => $newPrice]);
        }

        // Handle image upload (simpan ke database)
        if ($request->hasFile('product_image')) {
            $image = $request->file('product_image');
            $mime = $image->getMimeType();
            $data = file_get_contents($image->getRealPath());
            $product->update([
                'image_data' => $data,
                'image_mime_type' => $mime,
            ]);
        }


        $product->auxiliaryMaterials()->detach();
        if (!empty($validated['auxiliary_materials'])) {
            foreach ($validated['auxiliary_materials'] as $aux) {
                if (empty($aux['auxiliary_material_id'])) {
                    continue;
                }
                $qty = isset($aux['quantity_needed']) && $aux['quantity_needed'] !== '' && $aux['quantity_needed'] !== null
                    ? (float) $aux['quantity_needed'] : 0;
                if ($qty < 0) {
                    continue;
                }

                $product->auxiliaryMaterials()->attach($aux['auxiliary_material_id'], [
                    'quantity_needed' => $qty,
                    'unit' => $aux['unit'] ?? null,
                ]);
            }
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil diupdate');
    }

    public function deleteImage(Product $product)
    {
        $product->update([
            'image_data' => null,
            'image_mime_type' => null,
        ]);
        return redirect()->route('products.edit', $product)->with('success', 'Gambar produk berhasil dihapus');
    }

    /**
     * Tampilkan gambar produk dari database (untuk tag <img src="...">).
     */
    public function image(Product $product)
    {
        if (empty($product->image_mime_type) || empty($product->image_data)) {
            abort(404);
        }
        return response($product->image_data, 200, [
            'Content-Type' => $product->image_mime_type,
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    public function generateBarcode(Product $product)
    {
        $newBarcode = $product->generateBarcode();
        $product->update(['barcode' => $newBarcode]);

        return redirect()->route('products.edit', $product)->with('success', 'Barcode baru berhasil digenerate: ' . $newBarcode);
    }

    public function destroy(Product $product)
    {
        // Check if product is referenced in job orders
        $jobOrderCount = \App\Models\JobOrder::where('product_id', $product->id)->count();
        $jobOrderDetailCount = \App\Models\JobOrderDetail::where('product_id', $product->id)->count();

        if ($jobOrderCount > 0 || $jobOrderDetailCount > 0) {
            return redirect()->route('products.index')
                ->with('error', 'Produk tidak dapat dihapus karena masih digunakan dalam job order (' . $jobOrderCount . ' job orders, ' . $jobOrderDetailCount . ' job order details)');
        }

        // Check if product is referenced in sales transactions
        $salesTransactionCount = \App\Models\SalesItem::where('product_id', $product->id)->count();

        if ($salesTransactionCount > 0) {
            return redirect()->route('products.index')
                ->with('error', 'Produk tidak dapat dihapus karena masih digunakan dalam transaksi penjualan (' . $salesTransactionCount . ' transaksi)');
        }

        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus');
    }
}
