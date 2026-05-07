<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesTransaction;
use App\Services\JournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    public function globalIndex()
    {
        $returns = \App\Models\SalesReturn::with('salesTransaction', 'items.product')
            ->orderBy('return_date', 'desc')
            ->paginate(20);
        
        return view('sales_returns.global_index', compact('returns'));
    }

    public function index(SalesTransaction $sale)
    {
        $returns = $sale->salesReturns()->with('items.product')->orderBy('return_date', 'desc')->get();
        return view('sales_returns.index', compact('sale', 'returns'));
    }

    public function create(SalesTransaction $sale)
    {
        if ($sale->salesReturns()->exists()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Transaksi ini sudah pernah melakukan retur penjualan.');
        }

        $sale->load('salesItems.product');
        return view('sales_returns.create', compact('sale'));
    }

    public function store(Request $request, SalesTransaction $sale, JournalService $journalService)
    {
        if ($sale->salesReturns()->exists()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'Transaksi ini sudah pernah melakukan retur penjualan.');
        }
        $validated = $request->validate([
            'return_date' => 'required|date',
            'reason' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sales_item_id' => 'nullable|exists:sales_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $ppnRate = (float)($sale->ppn_rate ?? 11.00);

        $salesReturn = DB::transaction(function () use ($validated, $sale, $ppnRate) {
            $ret = SalesReturn::create([
                'sales_transaction_id' => $sale->id,
                'return_date' => $validated['return_date'],
                'reason' => $validated['reason'] ?? null,
                'subtotal' => 0,
                'ppn_amount' => 0,
                'grand_total' => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $qty = (int)$item['quantity'];
                $subtotal = (float)$qty * (float)$item['unit_price'];

                SalesReturnItem::create([
                    'sales_return_id' => $ret->id,
                    'sales_item_id' => $item['sales_item_id'] ?? null,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $subtotal,
                ]);

                // Kembalikan stok
                $product = Product::findOrFail($item['product_id']);
                $product->increment('stock', $qty);
            }

            $ret->recalcTotals($ppnRate);

            return $ret;
        });

        // Jurnal retur
        try {
            \Log::info("Creating journal for sales return: " . $salesReturn->id);
            \Log::info("Return totals - Subtotal: " . $salesReturn->subtotal . ", PPN: " . $salesReturn->ppn_amount);
            $journalService->createJournalFromSalesReturn($salesReturn);
            \Log::info("Journal created successfully for sales return: " . $salesReturn->id);
        } catch (\Exception $e) {
            \Log::error("Failed to create journal for sales return: " . $e->getMessage());
            throw $e;
        }

        return redirect()->route('sales.show', $sale)->with('success', 'Retur penjualan berhasil diproses');
    }

    public function show(SalesReturn $salesReturn)
    {
        $salesReturn->load(['salesTransaction', 'items.product']);
        return view('sales_returns.show', compact('salesReturn'));
    }
}