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
            ]);

            // Hitung biaya berdasarkan progress job order
            $cancellation->calculateCosts();
            $cancellation->save();

            // Create journal entry immediately
            $cancellation->createJournalEntry();

            // LANGSUNG kurangi stok produk saat input
            \Log::info('=== STARTING STOCK REDUCTION ===');
            \Log::info('Product ID: ' . $validated['product_id']);
            
            $product = Product::withoutGlobalScopes()->lockForUpdate()->findOrFail($validated['product_id']);
            $quantityCancelled = (int)$validated['quantity_cancelled'];
            
            $oldStock = (float)$product->stock;
            \Log::info('OLD STOCK: ' . $oldStock);
            \Log::info('QUANTITY TO REDUCE: ' . $quantityCancelled);
            
            // Kurangi stok produk
            $newStock = $oldStock - $quantityCancelled;
            $product->stock = $newStock;
            
            \Log::info('NEW STOCK (before save): ' . $product->stock);
            
            $product->save();
            $product->refresh();
            
            \Log::info('NEW STOCK (after save): ' . $product->stock);
            \Log::info('Product cancellation created and stock reduced: ' . $cancellation->id . 
                      ', Product: ' . $product->name . 
                      ', Qty Cancelled: ' . $quantityCancelled . 
                      ', Old Stock: ' . $oldStock .
                      ', New Stock: ' . $product->stock);
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
                            $bopPerUnit = $totalDurationHours * $bopRatePerHour;
                            
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
                            'btkl_per_unit' => round(max(0, $btklPerUnit), 2),
                            'bop_per_unit' => round(max(0, $bopPerUnit), 2),
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
                        $bopPerUnit = $totalDurationHours * $bopRatePerHour;

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
                        'btkl_per_unit' => round(max(0, $btklPerUnit), 2),
                        'bop_per_unit' => round(max(0, $bopPerUnit), 2),
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