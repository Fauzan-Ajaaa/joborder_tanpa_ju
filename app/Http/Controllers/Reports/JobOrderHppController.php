<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\JobOrder;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class JobOrderHppController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $jobs = JobOrder::with(['items.product'])
            ->where('status', 'completed')
            ->when($from, fn($q) => $q->whereDate('selesai_job_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('selesai_job_at', '<=', $to))
            ->orderBy('selesai_job_at', 'desc')
            ->get();

        return view('reports.job-order-hpp.index', compact('jobs', 'from', 'to'));
    }

    public function pdf(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $jobs = JobOrder::with(['items.product'])
            ->where('status', 'completed')
            ->when($from, fn($q) => $q->whereDate('selesai_job_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('selesai_job_at', '<=', $to))
            ->orderBy('selesai_job_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('reports.job-order-hpp.pdf', compact('jobs', 'from', 'to'))
            ->setPaper('a4', 'portrait');

        $filename = 'Laporan_HPP_Job_Order_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }
}
