<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\JobOrder;
use App\Models\RawMaterial;
use App\Models\JobOrderMaterial;
use App\Models\CustomerDiscount;
use App\Models\SalesTransaction;
use App\Models\SalesItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::orderBy('name');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->get('min_price'));
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->get('max_price'));
        }

        $products = $query->paginate(12);

        // Get price range for filter
        $priceRange = Product::selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $company = \App\Models\Company::first();

        $carouselProducts = Product::whereNotNull('image_data')->inRandomOrder()->limit(6)->get();

        return view('catalog.index', compact('products', 'priceRange', 'company', 'carouselProducts'));
    }

    public function show(Product $product)
    {
        // no recipe data loaded
        
        // Get related products
        $relatedProducts = Product::where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('catalog.show', compact('product', 'relatedProducts'));
    }

    /**
     * ===== CART & CHECKOUT (MULTI PRODUK) =====
     */

    public function cart(Request $request)
    {
        $cart = session('catalog_cart', []);

        // Muat produk untuk setiap item di keranjang
        $items = [];
        $subtotal = 0;

        foreach ($cart as $productId => $row) {
            $product = Product::find($productId);
            if (! $product) {
                continue;
            }

            $qty = (int) ($row['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $unitPrice = (float) ($row['unit_price'] ?? $product->price ?? 0);
            $lineTotal = $qty * $unitPrice;
            $subtotal += $lineTotal;

            $items[] = [
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
        }

        // Ambil diskon aktif (jika ada)
        $today = now()->toDateString();
        $activeDiscount = CustomerDiscount::query()
            ->where('active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();

        $discountRate = $activeDiscount?->percentage ?? 0;
        $discountAmount = $subtotal * ($discountRate / 100);
        $subtotalAfterDiscount = $subtotal - $discountAmount;

        return view('catalog.cart', [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'discountRate' => $discountRate,
            'subtotalAfterDiscount' => $subtotalAfterDiscount,
            'activeDiscount' => $activeDiscount,
        ]);
    }

    /**
     * Quick checkout form untuk satu produk (tanpa lewat keranjang).
     */
    public function quickCheckoutForm(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $qty = $data['quantity'] ?? 1;
        if ($qty < 1) {
            $qty = 1;
        }

        $unitPrice = $product->price ?? 0;
        $subtotal = $qty * $unitPrice;

        // Ambil diskon aktif (jika ada)
        $today = now()->toDateString();
        $activeDiscount = CustomerDiscount::query()
            ->where('active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->orderByDesc('id')
            ->first();

        $discountRate = $activeDiscount?->percentage ?? 0;
        $discountAmount = $subtotal * ($discountRate / 100);
        $grandTotal = $subtotal - $discountAmount;

        return view('catalog.checkout', [
            'product' => $product,
            'quantity' => $qty,
            'unitPrice' => $unitPrice,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'discountRate' => $discountRate,
            'grandTotal' => $grandTotal,
            'activeDiscount' => $activeDiscount,
        ]);
    }

    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
            'mode' => 'nullable|in:cart,buy_now',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $qty = $data['quantity'] ?? 1;

        $cart = session('catalog_cart', []);

        if (! isset($cart[$product->id])) {
            $cart[$product->id] = [
                'quantity' => $qty,
                'unit_price' => $product->price ?? 0,
            ];
        } else {
            // Tambah quantity jika sudah ada
            $cart[$product->id]['quantity'] += $qty;
        }

        session(['catalog_cart' => $cart]);

        // Jika mode buy_now, langsung ke form checkout cepat (tanpa melewati keranjang)
        if (($data['mode'] ?? 'cart') === 'buy_now') {
            return redirect()->route('catalog.checkout.form', [
                'product_id' => $product->id,
                'quantity' => $qty,
            ]);
        }

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang');
    }

    public function updateCart(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:0',
        ]);

        $cart = session('catalog_cart', []);

        foreach ($data['items'] as $row) {
            $productId = (int) $row['product_id'];
            $qty = (int) $row['quantity'];

            if ($qty <= 0) {
                unset($cart[$productId]);
            } else {
                if (! isset($cart[$productId])) {
                    $product = Product::find($productId);
                    if (! $product) {
                        continue;
                    }
                    $cart[$productId] = [
                        'quantity' => $qty,
                        'unit_price' => $product->price ?? 0,
                    ];
                } else {
                    $cart[$productId]['quantity'] = $qty;
                }
            }
        }

        session(['catalog_cart' => $cart]);

        return redirect()->route('catalog.cart')->with('success', 'Keranjang berhasil diperbarui');
    }

    public function checkout(Request $request)
    {
        $cart = session('catalog_cart', []);
        if (empty($cart)) {
            return redirect()->route('catalog.cart')->with('error', 'Keranjang masih kosong');
        }

        // Hanya checkout produk yang dipilih (checkbox)
        $selectedIds = $request->input('selected', array_keys($cart));
        $selectedIds = array_map('intval', (array) $selectedIds);
        $selectedIds = array_values(array_unique($selectedIds));

        if (empty($selectedIds)) {
            return redirect()->route('catalog.cart')->with('error', 'Pilih minimal satu produk untuk di-checkout');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Untuk sementara: buat satu JobOrder dari total cart (gunakan produk pertama yang dipilih sebagai product_id utama)
            $firstProductId = $selectedIds[0];
            $firstProduct = Product::findOrFail($firstProductId);

            $jobOrder = new JobOrder();
            $jobOrder->kode_job = 'JOB-' . date('Ymd') . '-' . str_pad(JobOrder::max('id') + 1, 4, '0', STR_PAD_LEFT);
            $jobOrder->product_id = $firstProduct->id;
            $jobOrder->quantity = 0; // quantity per-produk ada di cart
            $jobOrder->status = 'draft';
            $jobOrder->order_date = now();
            $jobOrder->customer_name = $validated['customer_name'];
            $jobOrder->customer_phone = $validated['customer_phone'] ?? null;
            $jobOrder->customer_address = $validated['customer_address'] ?? null;
            $jobOrder->notes = $validated['notes'] ?? null;
            $jobOrder->save();

            // Hitung subtotal & buat JobOrderMaterial per produk yang dipilih di cart
            $subtotal = 0;
            foreach ($cart as $productId => $row) {
                if (! in_array((int) $productId, $selectedIds, true)) {
                    continue; // lewati produk yang tidak dipilih
                }

                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }

                $qty = (int) ($row['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $unitPrice = (float) ($row['unit_price'] ?? $product->price ?? 0);
                $subtotal += $qty * $unitPrice;
            }

            // Terapkan diskon jika ada
            $today = now()->toDateString();
            $activeDiscount = CustomerDiscount::query()
                ->where('active', true)
                ->where(function ($q) use ($today) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })
                ->orderByDesc('id')
                ->first();

            $discountRate = $activeDiscount?->percentage ?? 0;
            $discountAmount = $subtotal * ($discountRate / 100);
            $grandTotal = $subtotal - $discountAmount;

            $jobOrder->update([
                'material_cost' => 0, // placeholder, perhitungan detail di sistem job order utama
                'total_cost' => $grandTotal,
            ]);

            DB::commit();

            // Kosongkan keranjang setelah berhasil
            session()->forget('catalog_cart');

            // Mode A: redirect ke halaman pembayaran yang sudah ada
            return redirect()->route('catalog.payment', $jobOrder)
                ->with('success', 'Pesanan berhasil dibuat, silakan lakukan pembayaran.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('catalog.cart')->with('error', 'Terjadi kesalahan saat membuat pesanan: ' . $e->getMessage());
        }
    }

    /**
     * Proses checkout cepat untuk satu produk (tanpa keranjang).
     */
    public function checkoutSingle(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $qty = $data['quantity'];

        DB::beginTransaction();
        try {
            $jobOrder = new JobOrder();
            $jobOrder->kode_job = 'JOB-' . date('Ymd') . '-' . str_pad(JobOrder::max('id') + 1, 4, '0', STR_PAD_LEFT);
            $jobOrder->product_id = $product->id;
            $jobOrder->quantity = $qty;
            $jobOrder->status = 'draft';
            $jobOrder->order_date = now();
            $jobOrder->customer_name = $data['customer_name'];
            $jobOrder->customer_phone = $data['customer_phone'] ?? null;
            $jobOrder->customer_address = $data['customer_address'] ?? null;
            $jobOrder->notes = $data['notes'] ?? null;
            $jobOrder->save();

            // Hitung BBB mirip storeOrder untuk satu produk
            $subtotal = 0;
            $unitPrice = $product->price ?? 0;
            $subtotal += $qty * $unitPrice;

            // add BOM material costs and create JobOrderMaterial entries
            $subtotal += $this->addBomMaterials($jobOrder, $product, $qty);


            // Diskon
            $today = now()->toDateString();
            $activeDiscount = CustomerDiscount::query()
                ->where('active', true)
                ->where(function ($q) use ($today) {
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
                })
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })
                ->orderByDesc('id')
                ->first();

            $discountRate = $activeDiscount?->percentage ?? 0;
            $discountAmount = $subtotal * ($discountRate / 100);
            $grandTotal = $subtotal - $discountAmount;

            $jobOrder->update([
                'material_cost' => 0,
                'total_cost' => $grandTotal,
            ]);

            DB::commit();

            return redirect()->route('catalog.payment', $jobOrder)
                ->with('success', 'Pesanan berhasil dibuat, silakan lakukan pembayaran.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat membuat pesanan: ' . $e->getMessage());
        }
    }

    public function order()
    {
        $products = Product::where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('catalog.order', compact('products'));
    }

    public function orderProduct(Product $product)
    {
        // Allow ordering even if stock is 0 (pre-order system)
        return view('catalog.order-product', compact('product'));
    }

    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Remove stock validation - allow pre-order for any quantity
        DB::beginTransaction();
        try {
            // Create Job Order (status konsisten dengan sistem utama: 'draft')
            $jobOrder = new JobOrder();
            $jobOrder->kode_job = 'JOB-' . date('Ymd') . '-' . str_pad(JobOrder::max('id') + 1, 4, '0', STR_PAD_LEFT);
            $jobOrder->product_id = $product->id;
            $jobOrder->quantity = $validated['quantity'];
            $jobOrder->status = 'draft';
            $jobOrder->order_date = now();
            $jobOrder->customer_name = $validated['customer_name'];
            $jobOrder->customer_phone = $validated['customer_phone'] ?? null;
            $jobOrder->customer_address = $validated['customer_address'] ?? null;
            $jobOrder->notes = $validated['notes'] ?? null;
            $jobOrder->save();

            // Calculate material costs dengan konversi satuan per-bahan
            // calculate material costs via BOM
            $totalMaterialCost = $this->addBomMaterials($jobOrder, $product, $validated['quantity']);


            // Update job order with calculated costs
            $jobOrder->update([
                'material_cost' => $totalMaterialCost * $validated['quantity'],
                'total_cost' => $product->price * $validated['quantity'],
            ]);

            DB::commit();

            return redirect()->route('catalog.payment', $jobOrder)
                ->with('success', 'Pesanan berhasil dibuat!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * ===== ORDER HISTORY / RECEIPT =====
     */

    public function orders()
    {
        // Tampilkan hanya job order yang berasal dari katalog (ditandai punya payment_method)
        // Nanti bisa dipersempit lagi per-customer jika sudah pakai customer login
        $orders = JobOrder::with(['product', 'salesTransactions'])
            ->whereNotNull('payment_method')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('catalog.orders.index', compact('orders'));
    }

    public function showOrder(JobOrder $jobOrder)
    {
        // Muat juga transaksi penjualan (jika sudah terbentuk) untuk membaca ongkir
        $jobOrder->load(['product', 'salesTransactions']);

        return view('catalog.orders.show', compact('jobOrder'));
    }

    /**
     * Cetak struk sederhana untuk pesanan katalog (versi customer)
     */
    public function receipt(JobOrder $jobOrder)
    {
        $jobOrder->load(['product', 'salesTransactions.salesItems.product']);

        // Cari transaksi penjualan berdasarkan kode job
        $sale = SalesTransaction::where('job_order_id', $jobOrder->kode_job)
            ->latest('transaction_date')
            ->first();
        if (! $sale) {
            // Untuk pesanan lama (sebelum fitur auto-penjualan), bentuk penjualan sederhana on-demand
            $sale = $this->createSaleFromJobOrderIfPossible($jobOrder);
            if (! $sale) {
                return redirect()->route('catalog.orders.show', $jobOrder)
                    ->with('error', 'Struk belum tersedia karena transaksi penjualan belum terbentuk.');
            }
        }

        return view('catalog.orders.receipt', compact('jobOrder', 'sale'));
    }

    /**
     * Cetak invoice untuk pesanan katalog (versi customer)
     */
    public function invoice(JobOrder $jobOrder)
    {
        $jobOrder->load(['product', 'salesTransactions.salesItems.product']);

        // Cari transaksi penjualan berdasarkan kode job
        $sale = SalesTransaction::where('job_order_id', $jobOrder->kode_job)
            ->latest('transaction_date')
            ->first();
        if (! $sale) {
            // Untuk pesanan lama (sebelum fitur auto-penjualan), bentuk penjualan sederhana on-demand
            $sale = $this->createSaleFromJobOrderIfPossible($jobOrder);
            if (! $sale) {
                return redirect()->route('catalog.orders.show', $jobOrder)
                    ->with('error', 'Invoice belum tersedia karena transaksi penjualan belum terbentuk.');
            }
        }

        return view('catalog.orders.invoice', compact('jobOrder', 'sale'));
    }

    /**
     * Bentuk transaksi penjualan sederhana dari job order katalog
     * untuk pesanan yang sudah dibayar tapi belum punya SalesTransaction.
     * Tidak mengubah stok produk (stok sudah dikurangi saat pembayaran).
     */
    protected function createSaleFromJobOrderIfPossible(JobOrder $jobOrder): ?\App\Models\SalesTransaction
    {
        // Hanya untuk pesanan katalog yang sudah dibayar
        if (! $jobOrder->payment_method || strtolower($jobOrder->status) !== 'paid') {
            return null;
        }

        // Jika sudah ada penjualan untuk kode job ini, gunakan yang terakhir
        $existing = SalesTransaction::where('job_order_id', $jobOrder->kode_job)
            ->latest('transaction_date')
            ->first();
        if ($existing) {
            return $existing;
        }

        $transactionDate = $jobOrder->paid_at ?? now();

        $customerName = $jobOrder->customer_name;
        $customerAddress = $jobOrder->customer_address;

        $paymentStatus = match (strtolower($jobOrder->payment_method)) {
            'transfer' => 'transfer',
            'ewallet' => 'ewallet',
            default => 'cash',
        };

        // Default: anggap ambil di toko (tanpa ongkir) jika tidak ada info lain
        $fobType = 'shipping_point';
        $fobCost = 0;

        $itemsData = [];
        $jobOrder->loadMissing('jobOrderDetails.product', 'product');

        if ($jobOrder->jobOrderDetails && $jobOrder->jobOrderDetails->count() > 0) {
            foreach ($jobOrder->jobOrderDetails as $detail) {
                $qty = (float) ($detail->quantity ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $unitPrice = (float) ($detail->unit_price ?? 0);

                $itemsData[] = [
                    'product_id' => $detail->product_id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                ];
            }
        } elseif ($jobOrder->product) {
            $itemsData[] = [
                'product_id' => $jobOrder->product_id,
                'quantity' => (float) ($jobOrder->quantity ?? 1),
                'unit_price' => (float) ($jobOrder->product->price ?? 0),
            ];
        }

        if (empty($itemsData)) {
            return null;
        }

        return DB::transaction(function () use ($jobOrder, $transactionDate, $customerName, $customerAddress, $paymentStatus, $fobType, $fobCost, $itemsData) {
            $sales = SalesTransaction::create([
                'transaction_date' => $transactionDate,
                'job_order_id' => $jobOrder->kode_job,
                'customer_id' => null,
                'customer_name' => $customerName,
                'customer_address' => $customerAddress,
                'notes' => 'Penjualan otomatis (on-demand) dari katalog untuk ' . $jobOrder->kode_job,
                'discount_rate' => 0,
                'fob_type' => $fobType,
                'fob_cost' => $fobCost,
                'ppn_rate' => 11,
                'payment_status' => $paymentStatus,
                'status' => 'pending',
            ]);

            foreach ($itemsData as $item) {
                SalesItem::create([
                    'sales_transaction_id' => $sales->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            $sales->calculateTotals();
            $sales->createJournalEntry();

            $journalService = app(\App\Services\JournalService::class);
            $journalService->createHppJournal($sales);

            return $sales;
        });
    }

    public function payment(JobOrder $jobOrder)
    {
        if (strtolower($jobOrder->status) !== 'draft') {
            return redirect()->route('catalog.index')
                ->with('error', 'Pesanan tidak valid');
        }

        $jobOrder->load(['product', 'materials.rawMaterial']);

        return view('catalog.payment', compact('jobOrder'));
    }

    public function processPayment(Request $request, JobOrder $jobOrder)
    {
        if (strtolower($jobOrder->status) !== 'draft') {
            return back()->with('error', 'Pesanan tidak valid');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:transfer,cash,ewallet',
            'shipping_option' => 'required|in:pickup,delivery',
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cash_amount' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update job order status & waktu bayar
            $paidAt = now();
            $updateData = [
                'status' => 'Paid',
                'payment_method' => $validated['payment_method'],
                'paid_at' => $paidAt,
            ];
            
            // Add cash amount if payment method is cash
            if ($validated['payment_method'] === 'cash' && isset($validated['cash_amount'])) {
                $updateData['cash_amount'] = $validated['cash_amount'];
            }
            
            $jobOrder->update($updateData);

            // Handle payment proof upload if exists
            if ($request->hasFile('payment_proof')) {
                $proofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
                $jobOrder->update(['payment_proof' => $proofPath]);
            }

            // Kurangi stok produk yang dipesan di katalog (sebagai barang jadi keluar)
            if ($jobOrder->product && $jobOrder->quantity) {
                $jobOrder->product->decrement('stock', $jobOrder->quantity);
                
                // Kurangi stok bahan baku yang digunakan untuk produksi produk
                $this->consumeRawMaterialsForProduct($jobOrder->product, $jobOrder->quantity);
            }

            // === Buat transaksi penjualan otomatis untuk pesanan katalog ===
            // Hanya jika belum ada sales terkait
            if ($jobOrder->salesTransactions()->count() === 0) {
                // Tanggal transaksi = waktu pembayaran
                $transactionDate = $paidAt;

                // Data customer dari job order
                $customerName = $jobOrder->customer_name;
                $customerAddress = $jobOrder->customer_address;

                // Map metode pembayaran katalog -> payment_status penjualan
                $paymentStatus = match (strtolower($jobOrder->payment_method)) {
                    'transfer' => 'transfer',
                    'ewallet' => 'ewallet',
                    default => 'cash',
                };

                // Map opsi pengiriman ke fob_type & fob_cost
                $shippingOption = $validated['shipping_option'];
                if ($shippingOption === 'delivery') {
                    $fobType = 'destination';
                    $fobCost = 10000; // ongkir tetap, bisa disesuaikan nanti
                } else {
                    $fobType = 'shipping_point';
                    $fobCost = 0;
                }

                // Bangun items dari job order (multi-produk atau single produk)
                $itemsData = [];
                $jobOrder->loadMissing('jobOrderDetails.product', 'product');

                if ($jobOrder->jobOrderDetails && $jobOrder->jobOrderDetails->count() > 0) {
                    foreach ($jobOrder->jobOrderDetails as $detail) {
                        $qty = (float) ($detail->quantity ?? 0);
                        if ($qty <= 0) {
                            continue;
                        }

                        $unitPrice = (float) ($detail->unit_price ?? 0);

                        $itemsData[] = [
                            'product_id' => $detail->product_id,
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                        ];
                    }
                } elseif ($jobOrder->product) {
                    $itemsData[] = [
                        'product_id' => $jobOrder->product_id,
                        'quantity' => (float) ($jobOrder->quantity ?? 1),
                        'unit_price' => (float) ($jobOrder->product->price ?? 0),
                    ];
                }

                if (! empty($itemsData)) {
                    $sales = SalesTransaction::create([
                        'transaction_date' => $transactionDate,
                        'job_order_id' => $jobOrder->kode_job,
                        'customer_id' => null,
                        'customer_name' => $customerName,
                        'customer_address' => $customerAddress,
                        'notes' => 'Penjualan otomatis dari katalog untuk ' . $jobOrder->kode_job,
                        'discount_rate' => 0,
                        'fob_type' => $fobType,
                        'fob_cost' => $fobCost,
                        'ppn_rate' => 11,
                        'payment_status' => $paymentStatus,
                        'status' => 'pending',
                    ]);

                    foreach ($itemsData as $item) {
                        SalesItem::create([
                            'sales_transaction_id' => $sales->id,
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'subtotal' => $item['quantity'] * $item['unit_price'],
                        ]);
                    }

                    // Hitung total & buat jurnal penjualan + HPP
                    $sales->calculateTotals();
                    $sales->createJournalEntry();

                    $journalService = app(\App\Services\JournalService::class);
                    $journalService->createHppJournal($sales);
                }
            }

            DB::commit();

            return redirect()->route('catalog.orders.show', $jobOrder)
                ->with('success', 'Pembayaran berhasil! Pesanan Anda sedang diproses.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Kurangi stok bahan baku saat produk dari katalog dibeli
     */
    private function consumeRawMaterialsForProduct(Product $product, float $quantity): void
    {
        // use BOM items instead of deprecated materials()
        $materials = $product->bomItems()->with('rawMaterial')->get();
        
        foreach ($materials as $material) {
            $rawMaterial = $material->rawMaterial;
            
            if (!$rawMaterial) {
                continue;
            }

            // Hitung total quantity yang dibutuhkan untuk quantity produk yang dipesan
            $totalQuantityNeeded = $material->quantity_needed * $quantity;
            
            // Kurangi stok bahan baku
            if ($rawMaterial->stock >= $totalQuantityNeeded) {
                $rawMaterial->reduceStock($totalQuantityNeeded);
                
                // Log pengurangan stok untuk audit
                Log::info('Raw material stock reduced for catalog purchase', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'raw_material_id' => $rawMaterial->id,
                    'raw_material_name' => $rawMaterial->name,
                    'quantity_reduced' => $totalQuantityNeeded,
                    'remaining_stock' => $rawMaterial->stock,
                    'order_quantity' => $quantity,
                ]);
            } else {
                // Jika stok tidak mencukupi, log warning tapi tetap lanjutkan
                Log::warning('Insufficient raw material stock for catalog purchase', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'raw_material_id' => $rawMaterial->id,
                    'raw_material_name' => $rawMaterial->name,
                    'needed' => $totalQuantityNeeded,
                    'available' => $rawMaterial->stock,
                    'order_quantity' => $quantity,
                ]);
                
                // Kurangi stok sampai 0 (negative stock prevention)
                if ($rawMaterial->stock > 0) {
                    $rawMaterial->reduceStock($rawMaterial->stock);
                }
            }
        }
    }

    /**
     * Tambahkan item bahan baku dari BOM ke job order dan kembalikan total biaya
     * untuk jumlah produk yang dipesan.
     */
    private function addBomMaterials(JobOrder $jobOrder, Product $product, float $qty): float
    {
        $totalCost = 0;
        $materials = $product->bomItems()->with('rawMaterial')->get();
        foreach ($materials as $material) {
            $raw = $material->rawMaterial;
            if (! $raw) {
                continue;
            }

            $rawUnit = $raw->unit;
            $recipeUnit = $material->unit ?: $rawUnit;

            $hargaPerRecipeUnit = $raw->price_per_unit;
            if ($rawUnit && $recipeUnit && $rawUnit !== $recipeUnit) {
                $factor = $raw->getMaterialConversionFactor($rawUnit, $recipeUnit);
                if ($factor > 0) {
                    $hargaPerRecipeUnit = $hargaPerRecipeUnit / $factor;
                }
            }

            $qtyPerUnitRecipe = $material->quantity_needed;
            $qtyTotalRecipe = $qtyPerUnitRecipe * $qty;
            $qtyTotalBase = $qtyTotalRecipe;
            if ($rawUnit && $recipeUnit && $rawUnit !== $recipeUnit) {
                $factorToBase = $raw->getMaterialConversionFactor($recipeUnit, $rawUnit);
                $qtyTotalBase = $qtyTotalRecipe * $factorToBase;
            }

            $materialCostPerProduct = $qtyPerUnitRecipe * $hargaPerRecipeUnit;
            $totalCost += $materialCostPerProduct * $qty;

            JobOrderMaterial::create([
                'job_order_id' => $jobOrder->id,
                'bahan_baku_id' => $material->raw_material_id,
                'qty_per_unit' => $qtyPerUnitRecipe,
                'qty_total' => $qtyTotalBase,
                'harga_per_unit' => $hargaPerRecipeUnit,
                'total_biaya' => $materialCostPerProduct * $qty,
            ]);
        }

        return $totalCost;
    }
}
