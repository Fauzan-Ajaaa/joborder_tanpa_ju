<?php

namespace App\Http\Controllers;

use App\Models\ProductCancellation;
use App\Models\JobOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductCancellationController extends Controller
{
    public function index()
    {
        $cancellations = ProductCancellation::with(['jobOrder', 'product', 'cancelledBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('product-cancellations.index', compact('cancellations'));
    }

    public function create(Request $request)
    {
        $jobOrderId = $request->get('job_order_id');
        $jobOrder = null;
        
        if ($jobOrderId) {
            $jobOrder = JobOrder::with(['jobOrderDetails.product', 'product'])
                ->findOrFail($jobOrderId);
        }

        return view('product-cancellations.create', compact('jobOrder'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_order_id' => 'required|exists:job_orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity_cancelled' => 'required|numeric|min:1|regex:/^\d+$/',
            'reason' => 'required|string|max:1000',
        ], [
            'quantity_cancelled.regex' => 'Quantity harus berupa bilangan bulat tanpa desimal.',
        ]);

        DB::transaction(function () use ($validated) {
            $jobOrder = JobOrder::findOrFail($validated['job_order_id']);
            
            // Validasi bahwa job order sedang berjalan atau sudah selesai
            if (!in_array($jobOrder->status, ['in_progress', 'completed'])) {
                throw new \Exception('Pembatalan hanya bisa dilakukan untuk job order yang sedang berjalan atau sudah selesai.');
            }

            // Validasi quantity tidak melebihi quantity job order
            $productDetail = $jobOrder->jobOrderDetails()
                ->where('product_id', $validated['product_id'])
                ->first();
            
            if (!$productDetail) {
                throw new \Exception('Produk tidak ditemukan dalam job order ini.');
            }

            $existingCancellations = ProductCancellation::where('job_order_id', $jobOrder->id)
                ->where('product_id', $validated['product_id'])
                ->where('status', '!=', 'rejected')
                ->sum('quantity_cancelled');

            $totalCancelled = $existingCancellations + $validated['quantity_cancelled'];
            
            if ($totalCancelled > $productDetail->quantity) {
                throw new \Exception('Total quantity yang dibatalkan tidak boleh melebihi quantity produk dalam job order.');
            }

            // Buat record pembatalan
            $cancellation = ProductCancellation::create([
                'job_order_id' => $validated['job_order_id'],
                'product_id' => $validated['product_id'],
                'quantity_cancelled' => (int)$validated['quantity_cancelled'],
                'reason' => $validated['reason'],
                'status' => 'completed',
                'cancelled_at' => $jobOrder->order_date, // Tanggal pembatalan mengikuti tanggal pemesanan (tidak real-time)
            ]);

            // Hitung biaya berdasarkan progress job order
            $cancellation->calculateCosts();
            $cancellation->save();

            // Create journal entry immediately
            $cancellation->createJournalEntry();
            
            // Kurangi stok bahan baku dan bahan penolong berdasarkan BOM produk
            \Log::info('=== STARTING STOCK REDUCTION FROM BOM ===');
            \Log::info('Product ID: ' . $validated['product_id']);
            
            $jobOrder = JobOrder::findOrFail($validated['job_order_id']);
            $product = Product::withoutGlobalScopes()->findOrFail($validated['product_id']);
            $quantityCancelled = (float)$validated['quantity_cancelled'];
            
            $bom = \App\Models\BillOfMaterial::with(['items.rawMaterial', 'auxiliaries.auxiliaryMaterial'])
                ->where('product_id', $product->id)
                ->first();

            if ($bom) {
                // 1. Kurangi stok bahan baku
                foreach ($bom->items as $bomItem) {
                    $raw = $bomItem->rawMaterial;
                    if (!$raw) continue;
                    
                    $qtyPerUnit = (float)$bomItem->quantity;
                    $qtyTotalRecipe = $qtyPerUnit * $quantityCancelled;
                    $recipeUnit = $bomItem->unit ?: $raw->unit;
                    $baseUnit = $raw->unit;
                    
                    $qtyTotalBase = $qtyTotalRecipe;
                    if ($recipeUnit !== $baseUnit) {
                        $sampleItem = \App\Models\PurchaseItem::where('raw_material_id', $raw->id)
                            ->whereNotNull('conversion_factor')->where('conversion_factor', '>', 0)->first();
                        $convFactor = $sampleItem ? (float)$sampleItem->conversion_factor : 1.0;
                        if ($convFactor > 0) {
                            $qtyTotalBase = $qtyTotalRecipe / $convFactor;
                        }
                    }
                    
                    if ($qtyTotalBase > 0) {
                        \Log::info('Reducing raw material stock for cancellation (waste & remake)', [
                            'raw_material' => $raw->name,
                            'qty_total_recipe' => $qtyTotalRecipe,
                            'recipe_unit' => $recipeUnit,
                            'qty_to_reduce_base' => $qtyTotalBase,
                            'base_unit' => $baseUnit,
                            'current_stock' => $raw->stock,
                        ]);
                        $raw->stock -= $qtyTotalBase;
                        $raw->save();

                        // Buat pemakaian baru di RawMaterialUsage agar tampil sebagai baris terpisah di kartu stok
                        $originalUsage = \App\Models\RawMaterialUsage::where('job_order_id', $jobOrder->id)
                            ->where('raw_material_id', $raw->id)
                            ->first();
                        
                        $unitPrice = $originalUsage ? (float)$originalUsage->unit_price : $raw->getFifoPrice($qtyTotalBase, $baseUnit);
                        
                        \App\Models\RawMaterialUsage::create([
                            'job_order_id'    => $jobOrder->id,
                            'raw_material_id' => $raw->id,
                            'quantity_used'   => $qtyTotalRecipe,
                            'unit'            => $recipeUnit ?: $raw->unit,
                            'unit_price'      => $unitPrice,
                            'cost'            => round($qtyTotalRecipe * $unitPrice, 2),
                            'company_id'      => $jobOrder->company_id,
                            'created_at'      => $cancellation->cancelled_at, // Gunakan tanggal pembatalan agar urutan kartu stok presisi
                            'updated_at'      => $cancellation->cancelled_at,
                        ]);
                    }
                }
                
                // 2. Kurangi stok bahan penolong
                foreach ($bom->auxiliaries as $bomAux) {
                    $aux = $bomAux->auxiliaryMaterial;
                    if (!$aux) continue;
                    
                    $qtyPerUnit = (float)$bomAux->quantity;
                    $qtyTotalRecipe = $qtyPerUnit * $quantityCancelled;
                    $recipeUnit = $bomAux->unit ?: $aux->unit;
                    $baseUnit = $aux->unit;
                    
                    $qtyTotalBase = $qtyTotalRecipe;
                    if ($recipeUnit !== $baseUnit) {
                        $sampleItem = \App\Models\PurchaseItem::where('auxiliary_material_id', $aux->id)
                            ->whereNotNull('conversion_factor')->where('conversion_factor', '>', 0)->first();
                        $convFactor = $sampleItem ? (float)$sampleItem->conversion_factor : 1.0;
                        if ($convFactor > 0) {
                            $qtyTotalBase = $qtyTotalRecipe / $convFactor;
                        }
                    }
                    
                    if ($qtyTotalBase > 0) {
                        \Log::info('Cancellation adjusted: auxiliary material effective qty reduced', [
                            'auxiliary_material' => $aux->name,
                            'qty_cancelled' => $qtyTotalBase,
                            'base_unit' => $baseUnit,
                        ]);
                        // Kurangi kolom stock fisik bahan penolong di database agar sinkron
                        $aux->stock -= $qtyTotalBase;
                        $aux->save();
                    }
                }
                \Log::info('Product cancellation created: ' . $cancellation->id . ' - BOM materials reduced for ' . $quantityCancelled . ' items');
            } else {
                \Log::warning('BOM not found for cancelled product ' . $product->name . '. No stock reduced.');
            }
            \Log::info('=== STOCK REDUCTION COMPLETE ===');
        });

        return redirect()->route('product-cancellations.index')
            ->with('success', 'Pengurangan produk berhasil dibuat dan stok telah dikurangi.');
    }

    public function show(ProductCancellation $productCancellation)
    {
        $productCancellation->load(['jobOrder.jobOrderDetails.product', 'product', 'cancelledBy', 'approvedBy', 'journalEntries.items.chartOfAccount']);
        
        return view('product-cancellations.show', compact('productCancellation'));
    }

    public function destroy(ProductCancellation $productCancellation)
    {
        $productCancellation->delete();

        return redirect()->route('product-cancellations.index')
            ->with('success', 'Pengurangan produk berhasil dihapus.');
    }

    /**
     * AJAX endpoint untuk mendapatkan produk dalam job order
     * Produk diambil dari pemesanan (jobOrderDetails atau product_id)
     * BBB, BTKL, BOP per unit diambil dari BOM
     */
    public function getJobOrderProducts(JobOrder $jobOrder)
    {
        try {
            \Log::info('=== getJobOrderProducts called for Job Order ' . $jobOrder->id . ' ===');
            \Log::info('Job Order: ' . $jobOrder->kode_job . ', Status: ' . $jobOrder->status);
            \Log::info('Product ID: ' . $jobOrder->product_id . ', Quantity: ' . $jobOrder->quantity);
            
            $products = [];
            
            // Cek apakah ada jobOrderDetails
            $jobOrderDetails = $jobOrder->jobOrderDetails()->with('product')->get();
            
            \Log::info('Job Order Details Count: ' . $jobOrderDetails->count());
            
            if ($jobOrderDetails->isEmpty()) {
                \Log::warning('No job order details found, trying fallback to product_id');
                
                // Fallback: ambil dari product_id langsung
                if ($jobOrder->product_id) {
                    \Log::info('Using product_id from job_order: ' . $jobOrder->product_id);
                    $product = Product::with('billOfMaterials')->find($jobOrder->product_id);
                    
                    if ($product) {
                        \Log::info('Product found: ' . $product->name);
                        
                        // Hitung quantity yang sudah dibatalkan
                        $cancelledQuantity = (float)(ProductCancellation::where('job_order_id', $jobOrder->id)
                            ->where('product_id', $product->id)
                            ->where('status', '!=', 'rejected')
                            ->sum('quantity_cancelled') ?? 0);

                        // Ambil BOM untuk produk
                        $bom = $product->billOfMaterials;
                        
                        if ($bom) {
                            \Log::info('BOM found for product: ' . $product->name);
                            
                            // Hitung dari BOM
                            $bbbPerUnit = (float)$bom->calculateTotalMaterialCost();
                            
                            $totalDurationMinutes = (float)$bom->calculateTotalDuration();
                            $totalDurationHours = $totalDurationMinutes > 0 ? $totalDurationMinutes / 60 : 0;
                            $btklRatePerHour = (float)($bom->btkl_rate_per_hour ?? 0);
                            $btklPerUnit = $totalDurationHours * $btklRatePerHour;
                            
                            $bopRatePerHour = (float)($bom->bop_rate_per_hour ?? 0);
                            $bopPerUnit = floor($totalDurationHours * $bopRatePerHour);
                            
                            \Log::info('BOM Costs - BBB: ' . $bbbPerUnit . ', BTKL: ' . $btklPerUnit . ', BOP: ' . $bopPerUnit);
                        } else {
                            \Log::warning('BOM not found for product: ' . $product->name);
                            $bbbPerUnit = 0;
                            $btklPerUnit = 0;
                            $bopPerUnit = 0;
                        }
                        
                        $quantity = (int)$jobOrder->quantity;
                        $availableQuantity = (int)($quantity - $cancelledQuantity);
                        
                        $products[] = [
                            'id' => (int)$product->id,
                            'name' => (string)$product->name,
                            'quantity' => $quantity,
                            'cancelled_quantity' => (int)$cancelledQuantity,
                            'available_quantity' => $availableQuantity,
                            'bbb_per_unit' => round(max(0, $bbbPerUnit), 2),
                            'btkl_per_unit' => floor(max(0, $btklPerUnit)),
                            'bop_per_unit' => floor(max(0, $bopPerUnit)),
                            'job_status' => (string)$jobOrder->status,
                        ];
                        
                        \Log::info('Product added: ' . $product->name . ' (Qty: ' . $quantity . ')');
                    } else {
                        \Log::error('Product not found with ID: ' . $jobOrder->product_id);
                    }
                } else {
                    \Log::error('No product_id in job order and no jobOrderDetails');
                }
                
                \Log::info('Returning ' . count($products) . ' products (fallback)');
                return response()->json($products);
            }
            
            // Process jobOrderDetails
            foreach ($jobOrderDetails as $detail) {
                \Log::info('Processing detail: ' . $detail->id . ', Product ID: ' . $detail->product_id . ', Qty: ' . $detail->quantity);
                
                if (!$detail->product) {
                    \Log::warning('Product not found for detail: ' . $detail->id);
                    continue;
                }
                
                \Log::info('Product found: ' . $detail->product->name);
                
                try {
                    // Hitung quantity yang sudah dibatalkan
                    $cancelledQuantity = (float)(ProductCancellation::where('job_order_id', $jobOrder->id)
                        ->where('product_id', $detail->product_id)
                        ->where('status', '!=', 'rejected')
                        ->sum('quantity_cancelled') ?? 0);

                    // Ambil BOM untuk produk ini
                    $bom = $detail->product->billOfMaterials()->first();
                    
                    if (!$bom) {
                        \Log::warning('BOM not found for product: ' . $detail->product_id . ' (' . $detail->product->name . ')');
                        $bbbPerUnit = 0;
                        $btklPerUnit = 0;
                        $bopPerUnit = 0;
                    } else {
                        // Hitung dari BOM
                        $bbbPerUnit = (float)$bom->calculateTotalMaterialCost();
                        
                        $totalDurationMinutes = (float)$bom->calculateTotalDuration();
                        $totalDurationHours = $totalDurationMinutes > 0 ? $totalDurationMinutes / 60 : 0;
                        $btklRatePerHour = (float)($bom->btkl_rate_per_hour ?? 0);
                        $btklPerUnit = $totalDurationHours * $btklRatePerHour;
                        
                        $bopRatePerHour = (float)($bom->bop_rate_per_hour ?? 0);
                        $bopPerUnit = floor($totalDurationHours * $bopRatePerHour);

                        \Log::info('BOM found - BBB: ' . $bbbPerUnit . ', BTKL: ' . $btklPerUnit . ', BOP: ' . $bopPerUnit);
                    }

                    $quantity = (int)$detail->quantity;
                    $availableQuantity = (int)($quantity - $cancelledQuantity);
                    
                    $products[] = [
                        'id' => (int)$detail->product->id,
                        'name' => (string)$detail->product->name,
                        'quantity' => $quantity,
                        'cancelled_quantity' => (int)$cancelledQuantity,
                        'available_quantity' => $availableQuantity,
                        'bbb_per_unit' => round(max(0, $bbbPerUnit), 2),
                        'btkl_per_unit' => floor(max(0, $btklPerUnit)),
                        'bop_per_unit' => floor(max(0, $bopPerUnit)),
                        'job_status' => (string)$jobOrder->status,
                    ];
                    
                    \Log::info('Product added: ' . $detail->product->name . ' (Qty: ' . $quantity . ')');
                } catch (\Exception $e) {
                    \Log::error('Error processing product detail ' . $detail->id . ': ' . $e->getMessage());
                    continue;
                }
            }

            \Log::info('Returning ' . count($products) . ' products for job order ' . $jobOrder->id);
            return response()->json($products);
        } catch (\Exception $e) {
            \Log::error('Error in getJobOrderProducts: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}