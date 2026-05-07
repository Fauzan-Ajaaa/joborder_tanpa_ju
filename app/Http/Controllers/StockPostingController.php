<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\AuxiliaryMaterial;
use App\Models\MaterialStockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockPostingController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);
        
        // Ambil data posting yang sudah ada untuk tahun ini
        $postedMonths = MaterialStockBalance::whereYear('period', $year)
            ->where('is_posted', true)
            ->select('period')
            ->distinct()
            ->pluck('period')
            ->map(function($date) {
                return $date->format('n');
            })
            ->toArray();

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = [
                'number' => $m,
                'name' => Carbon::create(null, $m, 1)->format('F'),
                'is_posted' => in_array($m, $postedMonths),
                'can_post' => $year < now()->year || ($year == now()->year && $m <= now()->month),
            ];
        }

        return view('stock-posting.index', compact('months', 'year'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $validated['month'];
        $year = $validated['year'];
        $period = Carbon::create($year, $month, 1)->startOfDay();

        DB::beginTransaction();
        try {
            // 1. Post Raw Materials
            $rawMaterials = RawMaterial::all();
            foreach ($rawMaterials as $material) {
                $stats = $material->getPeriodStats($month, $year);

                // Ambil beginning_fifo_layers dari ending bulan sebelumnya
                $prevPeriod = (clone $period)->subMonth();
                $prevBalance = MaterialStockBalance::where('material_type', 'RawMaterial')
                    ->where('material_id', $material->id)
                    ->where('is_posted', true)
                    ->whereYear('period', $prevPeriod->year)
                    ->whereMonth('period', $prevPeriod->month)
                    ->first();

                MaterialStockBalance::updateOrCreate(
                    [
                        'material_type' => 'RawMaterial',
                        'material_id' => $material->id,
                        'period' => $period->format('Y-m-d'),
                    ],
                    array_merge($stats, [
                        'is_posted' => true,
                        'beginning_fifo_layers' => $prevBalance?->ending_fifo_layers ?? null,
                    ])
                );
            }

            // 2. Post Auxiliary Materials
            $auxiliaryMaterials = AuxiliaryMaterial::all();
            foreach ($auxiliaryMaterials as $material) {
                $stats = $material->getPeriodStats($month, $year);

                $prevPeriod = (clone $period)->subMonth();
                $prevBalance = MaterialStockBalance::where('material_type', 'AuxiliaryMaterial')
                    ->where('material_id', $material->id)
                    ->where('is_posted', true)
                    ->whereYear('period', $prevPeriod->year)
                    ->whereMonth('period', $prevPeriod->month)
                    ->first();

                MaterialStockBalance::updateOrCreate(
                    [
                        'material_type' => 'AuxiliaryMaterial',
                        'material_id' => $material->id,
                        'period' => $period->format('Y-m-d'),
                    ],
                    array_merge($stats, [
                        'is_posted' => true,
                        'beginning_fifo_layers' => $prevBalance?->ending_fifo_layers ?? null,
                    ])
                );
            }

            DB::commit();
            return redirect()->back()
                ->with('success', "Data stok bulan " . $period->format('F Y') . " berhasil diposting.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', "Gagal posting data: " . $e->getMessage());
        }
    }
}
