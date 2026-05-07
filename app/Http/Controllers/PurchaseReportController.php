<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\RawMaterial;
use App\Models\AuxiliaryMaterial;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PurchaseReportController extends Controller
{
    public function index()
    {
        return view('purchase_reports.index');
    }

    public function report(Request $request)
    {
        $request->validate([
            'filter_type' => 'required|in:monthly,yearly,custom',
            'month' => 'nullable|required_if:filter_type,monthly|integer|min:1|max:12',
            'year' => 'nullable|required_if:filter_type,monthly,yearly|integer|min:2020|max:2030',
            'start_date' => 'nullable|required_if:filter_type,custom|date',
            'end_date' => 'nullable|required_if:filter_type,custom|date|after_or_equal:start_date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'material_id' => 'nullable|string',
        ]);

        $query = Purchase::with(['supplier', 'items.rawMaterial', 'items.auxiliaryMaterial'])
            ->when($request->filter_type === 'monthly', function ($q) use ($request) {
                $q->whereMonth('purchase_date', $request->month)
                  ->whereYear('purchase_date', $request->year);
            })
            ->when($request->filter_type === 'yearly', function ($q) use ($request) {
                $q->whereYear('purchase_date', $request->year);
            })
            ->when($request->filter_type === 'custom', function ($q) use ($request) {
                $q->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
            })
            ->when($request->supplier_id, function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            })
            ->when($request->material_id, function ($q) use ($request) {
                $materialId = $request->material_id;
                $q->whereHas('items', function ($subQ) use ($materialId) {
                    if (str_starts_with($materialId, 'raw_')) {
                        $subQ->where('raw_material_id', str_replace('raw_', '', $materialId));
                    } elseif (str_starts_with($materialId, 'aux_')) {
                        $subQ->where('auxiliary_material_id', str_replace('aux_', '', $materialId));
                    }
                });
            })
            ->orderBy('purchase_date', 'desc');

        $purchases = $query->get();

        // Calculate summary
        $summary = [
            'total_transactions' => $purchases->count(),
            'total_purchase' => $purchases->sum('total_amount'),
            'total_items_purchased' => $purchases->sum(function ($purchase) {
                return $purchase->items->sum('quantity');
            }),
            'avg_transaction_value' => $purchases->count() > 0 ? $purchases->sum('total_amount') / $purchases->count() : 0,
            'total_discount' => $purchases->sum('discount_amount'),
            'total_shipping' => $purchases->sum('fob_cost'),
            'total_tax' => $purchases->sum('ppn_amount'),
        ];

        return view('purchase_reports.report', compact(
            'purchases',
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
            'supplier_id' => 'nullable|exists:suppliers,id',
            'material_id' => 'nullable|string',
        ]);

        // Same query as report method
        $query = Purchase::with(['supplier', 'items.rawMaterial', 'items.auxiliaryMaterial'])
            ->when($request->filter_type === 'monthly', function ($q) use ($request) {
                $q->whereMonth('purchase_date', $request->month)
                  ->whereYear('purchase_date', $request->year);
            })
            ->when($request->filter_type === 'yearly', function ($q) use ($request) {
                $q->whereYear('purchase_date', $request->year);
            })
            ->when($request->filter_type === 'custom', function ($q) use ($request) {
                $q->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
            })
            ->when($request->supplier_id, function ($q) use ($request) {
                $q->where('supplier_id', $request->supplier_id);
            })
            ->when($request->material_id, function ($q) use ($request) {
                $materialId = $request->material_id;
                $q->whereHas('items', function ($subQ) use ($materialId) {
                    if (str_starts_with($materialId, 'raw_')) {
                        $subQ->where('raw_material_id', str_replace('raw_', '', $materialId));
                    } elseif (str_starts_with($materialId, 'aux_')) {
                        $subQ->where('auxiliary_material_id', str_replace('aux_', '', $materialId));
                    }
                });
            })
            ->orderBy('purchase_date', 'desc');

        $purchases = $query->get();

        // Generate title based on filter
        $title = 'Laporan Pembelian';
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
            'total_transactions' => $purchases->count(),
            'total_purchase' => $purchases->sum('total_amount'),
            'total_items_purchased' => $purchases->sum(function ($purchase) {
                return $purchase->items->sum('quantity');
            }),
            'avg_transaction_value' => $purchases->count() > 0 ? $purchases->sum('total_amount') / $purchases->count() : 0,
            'total_discount' => $purchases->sum('discount_amount'),
            'total_shipping' => $purchases->sum('fob_cost'),
            'total_tax' => $purchases->sum('ppn_amount'),
        ];

        $pdf = PDF::loadView('purchase_reports.pdf', compact(
            'purchases',
            'summary',
            'title'
        ));

        return $pdf->download('laporan-pembelian-' . date('Y-m-d') . '.pdf');
    }
}
