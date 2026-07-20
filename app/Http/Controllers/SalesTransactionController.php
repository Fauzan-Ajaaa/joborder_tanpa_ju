<?php

namespace App\Http\Controllers;

use App\Models\JobOrder;
use App\Models\SalesTransaction;
use App\Models\SalesItem;
use App\Models\Product;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesTransactionController extends Controller
{
    public function index()
    {
        $sales = SalesTransaction::with(['salesItems.product'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(10);
        
        $metrics = [
            'total' => SalesTransaction::count(),
            'cash' => SalesTransaction::where('payment_method', 'cash')->count(),
            'transfer' => SalesTransaction::where('payment_method', 'transfer')->count(),
            'ewallet' => SalesTransaction::where('payment_method', 'ewallet')->count(),
            'cod' => SalesTransaction::where('payment_method', 'cod')->count(),
        ];
        
        return view('sales.index', compact('sales', 'metrics'));
    }

    public function create(Request $request)
    {
        // Jika ada job_order_id di request, ambil data job order tersebut
        if ($request->has('job_order_id')) {
            $selectedJobOrder = JobOrder::with(['product', 'customer'])
                ->where('kode_job', $request->job_order_id)
                ->first();
            
            if (!$selectedJobOrder) {
                return redirect()->route('job-orders.index')
                    ->with('error', 'Job order tidak ditemukan');
            }
            
            if ($selectedJobOrder->status !== 'completed') {
                return redirect()->route('job-orders.index')
                    ->with('error', 'Hanya job order yang sudah selesai yang dapat dibuatkan penjualan');
            }
            
            if ($selectedJobOrder->salesTransactions()->count() > 0) {
                return redirect()->route('job-orders.show', $selectedJobOrder)
                    ->with('error', 'Job order ini sudah memiliki transaksi penjualan');
            }
            
            $products = Product::with('bomItems.rawMaterial')->get();
            $employees = Employee::select('id','name')->orderBy('name')->get();
            $customers = Customer::orderBy('name')->get();
            $jobOrders = collect(); // Kosongkan karena sudah ada yang dipilih
        
        return view('sales.create', compact('products','employees','customers','jobOrders','selectedJobOrder'));
        }
        
        // Tampilkan job orders yang completed dan belum ada sales transaction
        $products = Product::with('bomItems.rawMaterial')->get();
        $employees = Employee::select('id','name')->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $jobOrders = JobOrder::with(['product', 'customer'])
            ->where('status', 'completed')
            ->whereDoesntHave('salesTransactions')
            ->get();
        
        return view('sales.create', compact('products','employees','customers','jobOrders'));
    }

    // Method baru untuk handle pembuatan penjualan dari job order
    public function createFromJobOrder(JobOrder $jobOrder)
    {
        if ($jobOrder->status !== 'completed') {
            return redirect()->route('job-orders.index')
                ->with('error', 'Hanya job order yang sudah selesai yang dapat dibuatkan penjualan');
        }
        
        if ($jobOrder->salesTransactions()->count() > 0) {
            return redirect()->route('job-orders.show', $jobOrder)
                ->with('error', 'Job order ini sudah memiliki transaksi penjualan');
        }
        
        // Load job order dengan jobOrderDetails
        $jobOrder->load(['jobOrderDetails.product', 'product', 'customer']);
        
        $products = Product::with('bomItems.rawMaterial')->get();
        $employees = Employee::select('id','name')->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        $jobOrders = JobOrder::with(['product', 'customer'])
            ->where('status', 'completed')
            ->whereDoesntHave('salesTransactions')
            ->get();
        
        $selectedJobOrder = $jobOrder; // Set the selected job order
        
        return view('sales.create', compact('products','employees','customers','jobOrders','selectedJobOrder'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_order_kode' => 'nullable|string',
            'job_order_customer_id' => 'nullable|integer',
            'transaction_date' => 'required|date',
            'customer_name' => 'nullable|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'employee_name' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string',
            'customer_phone' => 'nullable|string|max:20',
            'delivery_notes' => 'nullable|string',
            // fob_type tidak lagi dibatasi ke shipping_point/destination supaya bisa pakai label bebas seperti Dine In / Take Away
            'fob_type' => 'nullable|string|max:50',
            'fob_cost' => 'nullable|numeric|min:0',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'ppn_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'payment_method' => 'required|in:cash,transfer,ewallet,cod',
            'payment_proof' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Conditional validation for delivery
        if ($validated['fob_type'] === 'destination') {
            // Payment proof wajib untuk delivery dengan Transfer/E-Wallet
            if (in_array($validated['payment_method'], ['transfer', 'ewallet'])) {
                $request->validate([
                    'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                ], [
                    'payment_proof.required' => 'Bukti pembayaran wajib diupload untuk metode pengiriman dengan ' . ucfirst($validated['payment_method']),
                ]);
            }
        }

        // Validasi credit limit customer
        if (!empty($validated['job_order_customer_id'])) {
            $customer = Customer::find($validated['job_order_customer_id']);
            if ($customer && $customer->credit_limit > 0) {
                // Calculate grand total
                $subtotal = 0;
                foreach ($validated['items'] as $item) {
                    $subtotal += $item['quantity'] * $item['unit_price'];
                }
                $fobCost = $validated['fob_cost'] ?? 0;
                $grandTotal = $subtotal + $fobCost + ($subtotal * $validated['ppn_rate'] / 100);
                
                // Check credit limit
                if ($customer->isCreditLimitExceeded($grandTotal)) {
                    return back()
                        ->withInput()
                        ->withErrors(['credit_limit' => 'Melebihi limit kredit customer. Limit: Rp ' . number_format($customer->credit_limit, 0, ',', '.') . ', Outstanding: Rp ' . number_format($customer->total_outstanding, 0, ',', '.')]);
                }
            }
        }

        // Initialize jobOrder variable outside transaction
        $jobOrder = null;
        
        DB::transaction(function () use ($validated, $request, &$jobOrder) {
            $employeeName = null;
            $customerData = [];
            $jobOrderCode = null; // Initialize $jobOrderCode
            
            // Handle employee selection
            if (!empty($validated['employee_id'])) {
                $employeeName = optional(Employee::find($validated['employee_id']))->name;
            } elseif (!empty($validated['employee_name'])) {
                $employeeName = $validated['employee_name'];
            }
            
            // Handle customer data from job order (if exists)
            $jobOrder = null;
            $jobOrderCode = null;
            if (!empty($validated['job_order_kode'])) {
                $jobOrderCode = $validated['job_order_kode'];
                // Find job order by kode (load details & product for later use)
                $jobOrder = JobOrder::with(['jobOrderDetails.product', 'product'])->where('kode_job', $jobOrderCode)->first();
                if ($jobOrder) {
                    $customerData = [
                        'customer_name' => $jobOrder->customer_name,
                        'customer_address' => $jobOrder->customer_address,
                        'customer_phone' => $jobOrder->customer_phone,
                    ];
                    
                    // Override with manual input if provided
                    if (!empty($validated['customer_name'])) {
                        $customerData['customer_name'] = $validated['customer_name'];
                    }
                    if (!empty($validated['customer_address'])) {
                        $customerData['customer_address'] = $validated['customer_address'];
                    }
                }
            } else {
                // If no job order, create manual customer data
                $customerData = [
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_address' => $validated['customer_address'] ?? null,
                ];
            }
            
            // Validate job order if kode provided
            if (!empty($validated['job_order_kode'])) {
                if (!$jobOrder) {
                    return redirect()->back()->with('error', 'Job Order tidak ditemukan');
                }
                
                // Cek apakah job order sudah memiliki penjualan
                $existingSales = SalesTransaction::where('job_order_id', $jobOrder->kode_job)->first();
                if ($existingSales) {
                    return redirect()->back()->with('error', 'Job Order ini sudah memiliki transaksi penjualan (No: ' . $existingSales->transaction_number . ')');
                }
            } else {
                return redirect()->back()->with('error', 'Job Order harus dipilih');
            }
            
            // Sederhanakan: kalau diisi, pakai fob_cost apa adanya; kalau tidak, anggap 0
            $fobCost = (float)($validated['fob_cost'] ?? 0);

            // Gabungkan tanggal yang dipilih dengan jam saat ini supaya transaction_date punya jam yang realistis
            $transactionDate = Carbon::parse($validated['transaction_date'])
                ->setTimeFrom(Carbon::now());

            // Determine approval status - langsung approved untuk semua metode
            // karena transaksi dibuat langsung oleh admin yang sudah verifikasi pembayaran
            $approvalStatus = 'approved';

            // Handle payment proof upload
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');
                $filename = 'payment_proof_' . time() . '_' . $file->getClientOriginalName();
                $paymentProofPath = $file->storeAs('payment_proofs', $filename, 'public');
            }

            // Buat record transaksi penjualan UTAMA (tanpa item dulu)
            $sales = SalesTransaction::create([
                'transaction_number' => 'SAL-' . date('Ymd-His') . '-' . str_pad(\App\Models\SalesTransaction::count() + 1, 3, '0', STR_PAD_LEFT),
                'job_order_id' => $jobOrder->kode_job,
                'customer_id' => null, // No longer using customer master data
                'transaction_date' => $transactionDate,
                'customer_name' => $customerData['customer_name'] ?? null,
                'employee' => $employeeName ?? null,
                'customer_address' => $customerData['customer_address'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? $customerData['customer_phone'] ?? null,
                'delivery_notes' => $validated['delivery_notes'] ?? null,
                'fob_type' => $validated['fob_type'] ?? null,
                'fob_cost' => $fobCost,
                'discount_rate' => $validated['discount_rate'] ?? 0,
                'ppn_rate' => $validated['ppn_rate'],
                'notes' => $validated['notes'] ?? null,
                'payment_method' => $validated['payment_method'],
                'payment_proof' => $paymentProofPath,
                'approval_status' => $approvalStatus,
                'status' => 'pending',
            ]);

            // Bangun item dari input form (harga bisa diubah user) dengan fallback ke job order
            if ($jobOrder) {
                $itemsData = [];
                $formItems = $validated['items'] ?? [];

                if ($jobOrder->jobOrderDetails && $jobOrder->jobOrderDetails->count() > 0) {
                    foreach ($jobOrder->jobOrderDetails as $index => $detail) {
                        $qty = (float) $detail->quantity;
                        // Pakai harga dari form jika ada, fallback ke harga job order
                        $unitPrice = isset($formItems[$index]['unit_price']) && (float)$formItems[$index]['unit_price'] > 0
                            ? (float) $formItems[$index]['unit_price']
                            : ($qty > 0 ? (float) ($detail->total_price / $qty) : 0);

                        $itemsData[] = [
                            'product_id' => $detail->product_id,
                            'quantity' => $qty,
                            'unit_price' => $unitPrice,
                        ];
                    }
                } elseif ($jobOrder->product) {
                    $unitPrice = isset($formItems[0]['unit_price']) && (float)$formItems[0]['unit_price'] > 0
                        ? (float) $formItems[0]['unit_price']
                        : (float) ($jobOrder->product->price ?? 0);
                    $itemsData[] = [
                        'product_id' => $jobOrder->product_id,
                        'quantity' => (float) $jobOrder->quantity,
                        'unit_price' => $unitPrice,
                    ];
                }
            } else {
                $itemsData = $validated['items'];
            }

            // Simpan item penjualan dan kurangi stok produk
            foreach ($itemsData as $item) {
                SalesItem::create([
                    'sales_transaction_id' => $sales->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                $product = Product::findOrFail($item['product_id']);
                $product->decrement('stock', $item['quantity']);

                // Otomatis buat COA Penjualan - [Nama Produk] jika belum ada
                \App\Models\ChartOfAccount::createSaleProductAccount($product->name);
 
                // Kurangi stok bahan baku yang digunakan oleh produk
                // NOTE: Raw material usage tracking for sales transactions is intentionally
                // disabled. Stok hanya dikurangi pada saat job order selesai; bagian ini
                // dibiarkan sebagai dokumentasi desain (semula menggunakan
                // $product->productMaterials sebelum tabel dihapus dan digantikan BOM).
                // Penghitungan BOM dapat ditambahkan kembali di masa depan jika diperlukan.
                //
                // Contoh logika sebelumnya:
                // $productMaterials = $product->productMaterials; // Table dropped
                // foreach ($productMaterials as $material) {
                //     // ...
                // }

                //     // ]);
                // }
            }

            // Setelah semua item tersimpan, hitung ulang total & buat jurnal penjualan
            $sales->calculateTotals();
            $sales->createJournalEntry();

            // Buat jurnal HPP terpisah berdasarkan total HPP job order (BBB+BTKL+BOP)
            $journalService = app(\App\Services\JournalService::class);
            $journalService->createHppJournal($sales);

            // Update customer spent if customer exists
            if ($jobOrder && $jobOrder->customer_id) {
                $customer = Customer::find($jobOrder->customer_id);
                if ($customer) {
                    // Calculate total from sales items
                    $totalAmount = 0;
                    foreach ($itemsData as $item) {
                        $totalAmount += $item['quantity'] * $item['unit_price'];
                    }
                    
                    $customer->addSpent($totalAmount);
                    
                    // Update piutang jika ada (untuk pembayaran tempo/kredit)
                    // Saat ini semua metode pembayaran langsung, tidak ada piutang
                }
            }

        });

        return redirect()->route('job-orders.index', ['sales_created' => 'true', 'job_order_id' => $jobOrder?->id])->with('success', 'Transaksi penjualan berhasil ditambahkan');
    }


    public function show(SalesTransaction $sale)
    {
        $sale->load(['salesItems.product', 'salesReturns']);
        return view('sales.show', compact('sale'));
    }

    public function edit(SalesTransaction $sale)
    {
        $sale->load(['salesItems.product', 'jobOrder.product']);
        $products = Product::all();
        $employees = Employee::select('id','name')->orderBy('name')->get();
        return view('sales.edit', compact('sale', 'products','employees'));
    }

    public function update(Request $request, SalesTransaction $sale)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'customer_name' => 'nullable|string|max:255',
            'employee_id' => 'nullable|exists:employees,id',
            'employee_name' => 'nullable|string|max:255',
            'customer_address' => 'nullable|string',
            'fob_type' => 'nullable|string|max:50',
            'fob_cost' => 'nullable|numeric|min:0',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            'ppn_rate' => 'required|numeric|min:0|max:100',
            'payment_status' => 'nullable|in:cash,transfer,ewallet',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $sale) {
            foreach ($sale->salesItems as $oldItem) {
                $product = Product::findOrFail($oldItem->product_id);
                $product->increment('stock', $oldItem->quantity);
            }

            $sale->salesItems()->delete();

            foreach ($validated['items'] as $item) {
                SalesItem::create([
                    'sales_transaction_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                $product = Product::findOrFail($item['product_id']);
                $product->decrement('stock', $item['quantity']);
            }

            $fobCost = (float)($validated['fob_cost'] ?? 0);

            $employeeName = null;
            if (!empty($validated['employee_id'])) {
                $employeeName = optional(Employee::find($validated['employee_id']))->name;
            } elseif (!empty($validated['employee_name'])) {
                $employeeName = $validated['employee_name'];
            }

            // Gabungkan tanggal yang dipilih dengan jam saat update dilakukan
            $transactionDate = Carbon::parse($validated['transaction_date'])
                ->setTimeFrom(Carbon::now());

            $sale->update([
                'transaction_date' => $transactionDate,
                'customer_name' => $validated['customer_name'] ?? null,
                'employee' => $employeeName ?? null,
                'customer_address' => $validated['customer_address'] ?? null,
                'fob_type' => $validated['fob_type'] ?? null,
                'fob_cost' => $fobCost,
                'discount_rate' => $validated['discount_rate'] ?? 0,
                'ppn_rate' => $validated['ppn_rate'],
                'notes' => $validated['notes'] ?? null,
                'payment_status' => $validated['payment_status'] ?? 'cash',
            ]);

            // Recalculate totals after update
            $sale->calculateTotals();
        });

        return redirect()->route('sales.index')->with('success', 'Transaksi penjualan berhasil diupdate');
    }

    public function receipt(SalesTransaction $sale)
    {
        $sale->load([
            'salesItems.product',
            'jobOrder.customer',
            'jobOrder.labors.employee',
        ]);

        // Jika package PDF tersedia, generate struk sebagai PDF dengan ukuran kecil (mirip kertas thermal)
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.receipt', [
                'sale' => $sale,
            ]);

            // setPaper: [left, top, width, height] dalam satuan point (1 point ≈ 1/72 inch)
            // 226.77 point ≈ 80mm lebar, tinggi dibuat fleksibel (misal 600 point)
            $pdf->setPaper([0, 0, 226.77, 600], 'portrait');

            $filename = 'Struk-' . ($sale->transaction_number ?: $sale->id) . '.pdf';
            return $pdf->stream($filename);
        }

        // Fallback ke HTML jika belum ada package PDF
        return view('sales.receipt', [
            'sale' => $sale,
            'fallback' => true,
        ]);
    }

    public function destroy(SalesTransaction $sale)
    {
        DB::transaction(function () use ($sale) {
            foreach ($sale->salesItems as $item) {
                $product = Product::findOrFail($item->product_id);
                $product->increment('stock', $item->quantity);
            }

            $sale->delete();
        });

        return redirect()->route('sales.index')->with('success', 'Transaksi penjualan berhasil dihapus');
    }

    /**
     * Show payment proof
     */
    public function showPaymentProof(SalesTransaction $sale)
    {
        if (!$sale->payment_proof) {
            abort(404, 'Bukti pembayaran tidak ditemukan');
        }

        $pathToFile = storage_path('app/public/' . $sale->payment_proof);
        
        if (!file_exists($pathToFile)) {
            abort(404, 'File bukti pembayaran tidak ditemukan');
        }

        return response()->file($pathToFile);
    }

    /**
     * Approve payment
     */
    public function approvePayment(Request $request, SalesTransaction $sale)
    {
        $sale->update([
            'approval_status' => 'approved',
            'approval_notes' => 'Pembayaran disetujui',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Pembayaran berhasil disetujui');
    }

    /**
     * Reject payment
     */
    public function rejectPayment(Request $request, SalesTransaction $sale)
    {
        // Validasi input alasan penolakan
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ], [
            'rejection_reason.required' => 'Alasan penolakan harus diisi'
        ]);

        $sale->update([
            'approval_status' => 'rejected',
            'approval_notes' => 'Pembayaran ditolak. Alasan: ' . $validated['rejection_reason'],
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Pembayaran berhasil ditolak');
    }

    /**
     * Upload payment proof
     */
    public function uploadPaymentProof(Request $request, SalesTransaction $sale)
    {
        $validated = $request->validate([
            'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Delete old payment proof if exists
        if ($sale->payment_proof && \Storage::disk('public')->exists($sale->payment_proof)) {
            \Storage::disk('public')->delete($sale->payment_proof);
        }

        $paymentProofPath = $request->file('payment_proof')->store('payment_proofs', 'public');

        $sale->update([
            'payment_proof' => $paymentProofPath,
        ]);

        return redirect()->back()->with('success', 'Bukti pembayaran berhasil diupload');
    }
}