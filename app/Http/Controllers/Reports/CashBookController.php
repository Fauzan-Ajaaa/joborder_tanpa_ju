<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\CashBook;
use App\Models\Penggajian;
use App\Models\Purchase;
use App\Models\SalesTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashBookController extends Controller
{
    public function index()
    {
        return view('reports.cash-book.index');
    }

    public function getData(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$rows, $summary] = $this->buildCashBookRows($startDate, $endDate);

        return response()->json([
            'data' => $rows,
            'summary' => $summary,
        ]);
    }

    private function buildCashBookRows(?string $startDate, ?string $endDate): array
    {
        $rows = [];

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        // 1) PENJUALAN (Kas Masuk / Debit)
        $salesQuery = SalesTransaction::query();
        if ($start && $end) {
            $salesQuery->whereBetween('transaction_date', [$start, $end]);
        }
        $salesQuery->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
        });
        $sales = $salesQuery->get();

        foreach ($sales as $trx) {
            $trxDate = $trx->transaction_date ? Carbon::parse($trx->transaction_date) : Carbon::parse($trx->created_at);
            $amount = (float) ($trx->grand_total ?? $trx->total_amount ?? 0);

            $rows[] = [
                'sort_ts' => $trxDate->format('Y-m-d H:i:s'),
                'transaction_date' => $trxDate->format('d/m/Y'),
                'description' => 'Penjualan tunai - ' . ($trx->customer_name ?: $trx->transaction_number),
                'reference' => $trx->transaction_number,
                'debit' => $amount,
                'credit' => 0,
                'source_type' => 'sales',
                'source_id' => $trx->id,
            ];
        }

        // 2) PEMBELIAN (Kas Keluar / Kredit)
        $purchaseQuery = Purchase::query();
        if ($start && $end) {
            $purchaseQuery->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()]);
        }
        $purchaseQuery->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
        });
        $purchases = $purchaseQuery->with('supplier')->get();

        foreach ($purchases as $pur) {
            $purDate = $pur->purchase_date ? Carbon::parse($pur->purchase_date) : Carbon::parse($pur->created_at);
            $sortDate = $pur->created_at ? Carbon::parse($pur->created_at) : $purDate;
            $amount = (float) ($pur->total_amount ?? 0);

            $supplierName = optional($pur->supplier)->name;
            $ket = 'Pembelian barang dagang - ' . ($supplierName ?: $pur->purchase_number);

            $rows[] = [
                'sort_ts' => $sortDate->format('Y-m-d H:i:s'),
                'transaction_date' => $purDate->format('d/m/Y'),
                'description' => $ket,
                'reference' => $pur->purchase_number,
                'debit' => 0,
                'credit' => $amount,
                'source_type' => 'purchase',
                'source_id' => $pur->id,
            ];
        }

        // 3) PENGGAJIAN (Kas Keluar / Kredit)
        $payrollQuery = Penggajian::query();
        if ($start && $end) {
            $payrollQuery->whereBetween('tanggal_penggajian', [$start->toDateString(), $end->toDateString()]);
        }
        $penggajian = $payrollQuery->with('employee')->get();

        foreach ($penggajian as $pg) {
            $pgDate = $pg->tanggal_penggajian ? Carbon::parse($pg->tanggal_penggajian) : Carbon::parse($pg->created_at);
            $sortDate = $pg->created_at ? Carbon::parse($pg->created_at) : $pgDate;
            $amount = (float) ($pg->total_gaji_bersih ?? 0);
            $periode = $pgDate->format('m/Y');

            $rows[] = [
                'sort_ts' => $sortDate->format('Y-m-d H:i:s'),
                'transaction_date' => $pgDate->format('d/m/Y'),
                'description' => 'Pembayaran gaji karyawan - ' . $periode,
                'reference' => $pg->no_transaksi_gaji,
                'debit' => 0,
                'credit' => $amount,
                'source_type' => 'payroll',
                'source_id' => $pg->id_gaji,
            ];
        }

        // 4) TRANSAKSI MANUAL (cash_books)
        $manualQuery = CashBook::query()
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc');

        if ($start && $end) {
            $manualQuery->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);
        }

        $manual = $manualQuery->get();
        foreach ($manual as $m) {
            $mDate = $m->transaction_date ? Carbon::parse($m->transaction_date) : Carbon::parse($m->created_at);
            $sortDate = $m->created_at ? Carbon::parse($m->created_at) : $mDate;

            $rows[] = [
                'sort_ts' => $sortDate->format('Y-m-d H:i:s'),
                'transaction_date' => $mDate->format('d/m/Y'),
                'description' => $m->description,
                'reference' => $m->reference_no ?: '-',
                'debit' => (float) ($m->debit ?? 0),
                'credit' => (float) ($m->credit ?? 0),
                'source_type' => 'manual',
                'source_id' => $m->id,
            ];
        }

        usort($rows, function ($a, $b) {
            return strcmp($a['sort_ts'], $b['sort_ts']);
        });

        $runningBalance = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        $result = [];

        foreach ($rows as $row) {
            $debit = (float) ($row['debit'] ?? 0);
            $credit = (float) ($row['credit'] ?? 0);

            $runningBalance += $debit - $credit;
            $totalDebit += $debit;
            $totalCredit += $credit;

            $result[] = [
                'transaction_date' => $row['transaction_date'],
                'description' => $row['description'],
                'reference' => $row['reference'],
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'type' => $row['source_type'],
                'source' => $row['source_id'],
            ];
        }

        return [
            $result,
            [
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'final_balance' => $runningBalance,
            ],
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'reference_no' => 'nullable|string|max:50',
            'debit' => 'required|numeric|min:0',
            'credit' => 'required|numeric|min:0',
        ]);

        $debit = (float) ($validated['debit'] ?? 0);
        $credit = (float) ($validated['credit'] ?? 0);
        if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
            return response()->json([
                'success' => false,
                'message' => 'Debit dan Kredit tidak boleh diisi bersamaan, dan salah satu harus lebih dari 0.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $cashBook = CashBook::create([
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'reference_no' => $validated['reference_no'],
                'debit' => $validated['debit'],
                'credit' => $validated['credit'],
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil ditambahkan',
                'data' => $cashBook,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error adding cash book transaction: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        [$rows, $summary] = $this->buildCashBookRows($startDate, $endDate);

        $transactionData = [];
        foreach ($rows as $row) {
            $transactionData[] = [
                'transaction_date' => $row['transaction_date'],
                'description' => $row['description'],
                'reference' => $row['reference'],
                'debit' => $row['debit'] > 0 ? 'Rp ' . number_format($row['debit'], 0, ',', '.') : '-',
                'credit' => $row['credit'] > 0 ? 'Rp ' . number_format($row['credit'], 0, ',', '.') : '-',
                'running_balance' => 'Rp ' . number_format($row['running_balance'], 0, ',', '.'),
            ];
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.cash-book.pdf', compact(
            'transactionData',
            'summary',
            'startDate',
            'endDate'
        ) + [
            'appName'    => auth()->user()->nama_perusahaan ?? config('app.name', 'Perusahaan'),
            'appAddress' => auth()->user()->alamat_perusahaan ?? '',
        ]);
        
        $filename = 'Laporan_Buku_Kas_' . ($startDate ? Carbon::parse($startDate)->format('Y-m-d') : 'all') . '_to_' . ($endDate ? Carbon::parse($endDate)->format('Y-m-d') : 'all') . '.pdf';
        
        return $pdf->download($filename);
    }
}
