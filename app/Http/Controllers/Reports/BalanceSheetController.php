<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\BalanceSheetService;
use Illuminate\Http\Request;

class BalanceSheetController extends Controller
{
    protected $balanceSheetService;

    public function __construct(BalanceSheetService $balanceSheetService)
    {
        $this->balanceSheetService = $balanceSheetService;
    }

    public function index(Request $request)
    {
        $year = (int) ($request->integer('year') ?: now()->year);
        $month = (int) ($request->integer('month') ?: now()->month);

        // Calculate balance sheet using original service
        $balanceSheetData = $this->balanceSheetService->calculateBalanceSheet($year, $month);

        return view('reports.balance_sheet', [
            'year' => $year,
            'month' => $month,
            'period' => $balanceSheetData['period'],
            'balanceSheet' => $balanceSheetData['balance_sheet'],
            'totals' => $balanceSheetData['totals'],
            'systemStatus' => $balanceSheetData['system_status'],
            'isBalanced' => $balanceSheetData['is_balanced']
        ]);
    }

    public function pdf(Request $request)
    {
        $year = (int) ($request->integer('year') ?: now()->year);
        $month = (int) ($request->integer('month') ?: now()->month);

        $balanceSheetData = $this->balanceSheetService->calculateBalanceSheet($year, $month);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.balance_sheet_pdf', [
            'year'         => $year,
            'month'        => $month,
            'period'       => $balanceSheetData['period'],
            'balanceSheet' => $balanceSheetData['balance_sheet'],
            'totals'       => $balanceSheetData['totals'],
            'isBalanced'   => $balanceSheetData['is_balanced'],
            'company'      => auth()->user()->nama_perusahaan ?? 'Perusahaan',
            'address'      => auth()->user()->alamat_perusahaan ?? '',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-posisi-keuangan-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.pdf');
    }
}