<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayrollPrintController extends Controller
{
    public function index(Request $request)
    {
        $query = Payroll::with(['employee', 'product'])->orderByDesc('created_at');

        // Optional filter by product or date range
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }
        if ($request->filled('from')) {
            $query->whereDate('work_date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('work_date', '<=', $request->date('to'));
        }

        $payrolls = $query->get();

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.payrolls', [
                'payrolls' => $payrolls,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('BTKL-All.pdf');
        }

        return view('print.payrolls', [
            'payrolls' => $payrolls,
            'fallback' => true,
        ]);
    }

    public function show(Payroll $payroll)
    {
        $payroll->load(['employee', 'product']);

        // If barryvdh/laravel-dompdf is installed, generate PDF
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.payroll', [
                'payroll' => $payroll,
            ])->setPaper('a4');

            $filename = 'BTKL-' . ($payroll->kode_penggajian ?: $payroll->id) . '.pdf';
            return $pdf->stream($filename);
        }

        // Fallback to print-friendly HTML
        return view('print.payroll', [
            'payroll' => $payroll,
            'fallback' => true,
        ]);
    }
}
