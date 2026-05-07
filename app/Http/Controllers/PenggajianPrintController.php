<?php

namespace App\Http\Controllers;

use App\Models\Penggajian;
use Illuminate\Http\Request;

class PenggajianPrintController extends Controller
{
    public function index(Request $request)
    {
        $query = Penggajian::with('employee')->orderByDesc('tanggal_penggajian');

        if ($request->filled('from')) {
            $query->whereDate('tanggal_penggajian', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('tanggal_penggajian', '<=', $request->date('to'));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        $items = $query->get();

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.penggajians', [
                'items' => $items,
            ])->setPaper('a4', 'portrait');

            return $pdf->stream('Penggajian-All.pdf');
        }

        return view('print.penggajians', [
            'items' => $items,
            'fallback' => true,
        ]);
    }

    public function show(Penggajian $penggajian)
    {
        $penggajian->load('employee');

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('print.penggajian', [
                'item' => $penggajian,
            ])->setPaper('a4');

            $filename = 'Gaji-' . ($penggajian->no_transaksi_gaji ?: $penggajian->id_gaji) . '.pdf';
            return $pdf->stream($filename);
        }

        return view('print.penggajian', [
            'item' => $penggajian,
            'fallback' => true,
        ]);
    }
}
