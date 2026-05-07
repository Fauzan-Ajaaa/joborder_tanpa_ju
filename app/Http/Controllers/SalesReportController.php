<?php

namespace App\Http\Controllers;

use App\Models\SalesTransaction;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    public function index()
    {
        return view('sales_reports.index');
    }

    public function report(Request $request)
    {
        $request->validate([
            'filter_type' => 'required|in:monthly,yearly,custom',
            'month' => 'nullable|required_if:filter_type,monthly|integer|min:1|max:12',
            'year' => 'nullable|required_if:filter_type,monthly,yearly|integer|min:2020|max:2030',
            'start_date' => 'nullable|required_if:filter_type,custom|date',
            'end_date' => 'nullable|required_if:filter_type,custom|date|after_or_equal:start_date',
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'nullable|exists:products,id',
        ]);

        $query = SalesTransaction::with(['customer', 'jobOrder', 'items.product'])
            ->when($request->filter_type === 'monthly', function ($q) use ($request) {
                $q->whereMonth('transaction_date', $request->month)
                  ->whereYear('transaction_date', $request->year);
            })
            ->when($request->filter_type === 'yearly', function ($q) use ($request) {
                $q->whereYear('transaction_date', $request->year);
            })
            ->when($request->filter_type === 'custom', function ($q) use ($request) {
                $q->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
            })
            ->when($request->customer_id, function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            })
            ->when($request->product_id, function ($q) use ($request) {
                $q->whereHas('items', function ($subQ) use ($request) {
                    $subQ->where('product_id', $request->product_id);
                });
            })
            ->orderBy('transaction_date', 'desc');

        $sales = $query->get();

        // Calculate summary
        $summary = [
            'total_transactions' => $sales->count(),
            'total_revenue' => $sales->sum('total_amount'),
            'total_items_sold' => $sales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            }),
            'avg_transaction_value' => $sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0,
            'total_discount' => $sales->sum('discount_amount'),
            'total_fob' => $sales->sum('fob_cost'),
            'total_ppn' => $sales->sum('ppn_amount'),
        ];

        return view('sales_reports.report', compact(
            'sales',
            'summary'
        ));
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'filter_type' => 'required|in:monthly,yearly,custom',
            'month' => 'nullable|required_if:filter_type,monthly|integer|min:1|max:12',
            'year' => 'nullable|required_if:filter_type,monthly,yearly|integer|min:2020|max:2030',
            'start_date' => 'nullable|required_if:filter_type,custom|date',
            'end_date' => 'nullable|required_if:filter_type,custom|date|after_or_equal:start_date',
            'customer_id' => 'nullable|exists:customers,id',
            'product_id' => 'nullable|exists:products,id',
        ]);

        // Same query as report method
        $query = SalesTransaction::with(['customer', 'jobOrder', 'items.product'])
            ->when($request->filter_type === 'monthly', function ($q) use ($request) {
                $q->whereMonth('transaction_date', $request->month)
                  ->whereYear('transaction_date', $request->year);
            })
            ->when($request->filter_type === 'yearly', function ($q) use ($request) {
                $q->whereYear('transaction_date', $request->year);
            })
            ->when($request->filter_type === 'custom', function ($q) use ($request) {
                $q->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
            })
            ->when($request->customer_id, function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            })
            ->when($request->product_id, function ($q) use ($request) {
                $q->whereHas('items', function ($subQ) use ($request) {
                    $subQ->where('product_id', $request->product_id);
                });
            })
            ->orderBy('transaction_date', 'desc');

        $sales = $query->get();

        // Generate title based on filter
        $title = 'Laporan Penjualan';
        if ($request->filter_type === 'monthly') {
            $monthName = Carbon::createFromDate($request->year, $request->month, 1)->format('F');
            $title .= " - {$monthName} {$request->year}";
        } elseif ($request->filter_type === 'yearly') {
            $title .= " - Tahun {$request->year}";
        } else {
            $title .= " - " . Carbon::parse($request->start_date)->format('d/m/Y') . 
                      " s/d " . Carbon::parse($request->end_date)->format('d/m/Y');
        }

        $summary = [
            'total_transactions' => $sales->count(),
            'total_revenue' => $sales->sum('total_amount'),
            'total_items_sold' => $sales->sum(function ($sale) {
                return $sale->items->sum('quantity');
            }),
            'avg_transaction_value' => $sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0,
            'total_discount' => $sales->sum('discount_amount'),
            'total_fob' => $sales->sum('fob_cost'),
            'total_ppn' => $sales->sum('ppn_amount'),
        ];

        $pdf = PDF::loadView('sales_reports.pdf', compact(
            'sales',
            'summary',
            'title'
        ));

        return $pdf->download('laporan-penjualan-' . date('Y-m-d') . '.pdf');
    }
}
