<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\RawMaterial;
use App\Models\Purchase;
use App\Models\SalesTransaction;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index()
    {
        // Kumpulan transaksi dari berbagai sumber
        $purchaseTransactions = Purchase::with('supplier')
            ->latest('purchase_date')
            ->take(20)
            ->get()
            ->map(function ($purchase) {
                return (object) [
                    'date' => $purchase->purchase_date,
                    'type' => 'Pembelian Bahan Baku',
                    'description' => 'PO ' . $purchase->purchase_number . ' - ' . ($purchase->supplier->name ?? '-'),
                    'amount' => $purchase->total_amount ?? 0,
                ];
            });

        $salesTransactions = SalesTransaction::latest('transaction_date')
            ->take(20)
            ->get()
            ->map(function ($sale) {
                return (object) [
                    'date' => $sale->transaction_date,
                    'type' => 'Penjualan',
                    'description' => 'Penjualan ' . $sale->transaction_number,
                    'amount' => $sale->total_amount ?? 0,
                ];
            });

        $payrollTransactions = Payroll::with('employee')
            ->latest('work_date')
            ->take(20)
            ->get()
            ->map(function ($payroll) {
                return (object) [
                    'date' => $payroll->work_date,
                    'type' => 'Penggajian',
                    'description' => 'Gaji ' . ($payroll->employee->name ?? '-') . ' (' . $payroll->kode_penggajian . ')',
                    'amount' => $payroll->total_gaji_perhari ?? 0,
                ];
            });

        $manualMaterialTransactions = Transaction::with('rawMaterial')
            ->latest('transaction_date')
            ->take(20)
            ->get()
            ->map(function ($trx) {
                $typeLabel = $trx->transaction_type === 'in' ? 'Penyesuaian Masuk' : 'Penyesuaian Keluar';
                return (object) [
                    'date' => $trx->transaction_date,
                    'type' => 'Transaksi Bahan Baku',
                    'description' => $typeLabel . ' - ' . ($trx->rawMaterial->name ?? 'N/A'),
                    'amount' => ($trx->quantity ?? 0) * ($trx->unit_price ?? 0),
                ];
            });

        $transactions = collect()
            ->merge($purchaseTransactions)
            ->merge($salesTransactions)
            ->merge($payrollTransactions)
            ->merge($manualMaterialTransactions)
            ->sortByDesc('date');

        return view('transactions.index', compact('transactions'));
    }

    public function create()
    {
        $rawMaterials = RawMaterial::all();
        return view('transactions.create', compact('rawMaterials'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'raw_material_id' => 'required|exists:raw_materials,id',
            'transaction_type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $rawMaterial = RawMaterial::findOrFail($validated['raw_material_id']);
            
            // Calculate balance
            $lastTransaction = Transaction::where('raw_material_id', $rawMaterial->id)
                ->latest('transaction_date')
                ->latest('created_at')
                ->first();
            
            $balance = $lastTransaction ? $lastTransaction->balance : $rawMaterial->stock;
            
            if ($validated['transaction_type'] === 'in') {
                $balance += $validated['quantity'];
            } else {
                $balance -= $validated['quantity'];
            }

            // Create transaction
            Transaction::create([
                'transaction_date' => $validated['transaction_date'],
                'raw_material_id' => $validated['raw_material_id'],
                'transaction_type' => $validated['transaction_type'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'balance' => $balance,
                'description' => $validated['description'],
            ]);

            // Update raw material stock
            $rawMaterial->update(['stock' => $balance]);
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil ditambahkan');
    }

    public function show(Transaction $transaction)
    {
        $transaction->load('rawMaterial');
        return view('transactions.show', compact('transaction'));
    }

    public function edit(Transaction $transaction)
    {
        $rawMaterials = RawMaterial::all();
        return view('transactions.edit', compact('transaction', 'rawMaterials'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'raw_material_id' => 'required|exists:raw_materials,id',
            'transaction_type' => 'required|in:in,out',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $transaction) {
            // Revert old transaction
            $rawMaterial = RawMaterial::findOrFail($transaction->raw_material_id);
            if ($transaction->transaction_type === 'in') {
                $rawMaterial->stock -= $transaction->quantity;
            } else {
                $rawMaterial->stock += $transaction->quantity;
            }
            $rawMaterial->save();

            // Apply new transaction
            $newRawMaterial = RawMaterial::findOrFail($validated['raw_material_id']);
            if ($validated['transaction_type'] === 'in') {
                $newRawMaterial->stock += $validated['quantity'];
            } else {
                $newRawMaterial->stock -= $validated['quantity'];
            }
            $newRawMaterial->save();

            // Update transaction
            $transaction->update([
                'transaction_date' => $validated['transaction_date'],
                'raw_material_id' => $validated['raw_material_id'],
                'transaction_type' => $validated['transaction_type'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'balance' => $newRawMaterial->stock,
                'description' => $validated['description'],
            ]);
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil diupdate');
    }

    public function destroy(Transaction $transaction)
    {
        DB::transaction(function () use ($transaction) {
            $rawMaterial = RawMaterial::findOrFail($transaction->raw_material_id);
            
            // Revert transaction
            if ($transaction->transaction_type === 'in') {
                $rawMaterial->stock -= $transaction->quantity;
            } else {
                $rawMaterial->stock += $transaction->quantity;
            }
            $rawMaterial->save();

            $transaction->delete();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus');
    }
}
