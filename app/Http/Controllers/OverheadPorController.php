<?php

namespace App\Http\Controllers;

use App\Models\OverheadPor;
use App\Models\OverheadMonthly;
use App\Models\Employee;
use Illuminate\Http\Request;

class OverheadPorController extends Controller
{
    public function index(Request $request)
    {
        // Ambil filter bulan dan tahun dari request, default ke bulan dan tahun saat ini
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Format periode sesuai dengan format di database (YYYY-MM)
        $periodeFilter = sprintf('%04d-%02d', $year, $month);

        // Query dengan filter
        $query = OverheadPor::query();

        if ($month && $year) {
            $query->where('periode', $periodeFilter);
        }

        $porItems = $query->orderByDesc('periode')->paginate(15);

        return view('overhead_por.index', compact('porItems', 'month', 'year'));
    }

    public function create()
    {
        $dasarOptions = [
            'jam_tk_langsung' => 'Jam Tenaga Kerja Langsung',
        ];
        $selectedPeriod = request('periode', now()->format('Y-m'));

        $monthly = OverheadMonthly::where('periode', $selectedPeriod)->first();
        // Hitung total dari biaya_overhead kategori BOP untuk periode ini
        $totalFromBeban = \App\Models\BiayaOverhead::where('kategori', 'BOP')
            ->whereRaw("DATE_FORMAT(periode, '%Y-%m') = ?", [$selectedPeriod])
            ->sum('total');
        // Prioritaskan total dari beban-beban, fallback ke OverheadMonthly
        $defaultTotal = $totalFromBeban > 0
            ? (int) round($totalFromBeban)
            : ($monthly ? (int) round($monthly->total_biaya) : null);

        // Hitung total jam dari semua karyawan BTKL yang aktif
        // Menggunakan LIKE untuk menangkap variasi penulisan BTKL (BTKL, btkl, BTKL Langsung, dll)
        $defaultJamDasarAlokasi = Employee::where('employee_type', 'LIKE', '%BTKL%')
            ->whereRaw('UPPER(status) = ?', ['ACTIVE'])
            ->sum('jam_kerja_per_bulan');

        // Check if POR already exists for this period
        $existing = OverheadPor::where('periode', $selectedPeriod)
            ->where('dasar_alokasi', 'jam_tk_langsung')
            ->first();

        if ($existing) {
            return redirect()->route('overhead-por.edit', $existing)
                ->with('info', 'Data Overhead POR untuk periode ' . $selectedPeriod . ' sudah ada. Silakan edit data yang ada.');
        }

        return view('overhead_por.create', [
            'dasarOptions' => $dasarOptions,
            'selectedPeriod' => $selectedPeriod,
            'defaultTotal' => $defaultTotal,
            'defaultJamDasarAlokasi' => $defaultJamDasarAlokasi,
            'bebanBop' => \App\Models\BiayaOverhead::where('kategori', 'BOP')
                ->whereRaw("DATE_FORMAT(periode, '%Y-%m') = ?", [$selectedPeriod])
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'periode' => ['required', 'string', 'max:7'], // format YYYY-MM
            'dasar_alokasi' => ['required', 'string', 'max:50'],
            'total_overhead_bulanan' => ['required', 'numeric', 'min:0'],
            'total_jam_dasar_alokasi' => ['required', 'numeric', 'min:0.01'],
        ]);

        // Check if combination already exists
        $existing = OverheadPor::where('periode', $data['periode'])
            ->where('dasar_alokasi', $data['dasar_alokasi'])
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Data Overhead POR untuk periode ' . $data['periode'] . ' dengan dasar alokasi ' . $data['dasar_alokasi'] . ' sudah ada. Silakan edit data yang ada.');
        }

        $data['por_per_jam'] = $data['total_overhead_bulanan'] / $data['total_jam_dasar_alokasi'];

        OverheadPor::create($data);

        return redirect()->route('overhead-por.index')->with('success', 'Data Overhead POR berhasil disimpan');
    }

    public function edit(OverheadPor $overhead_por)
    {
        $dasarOptions = [
            'jam_tk_langsung' => 'Jam Tenaga Kerja Langsung',
        ];

        return view('overhead_por.edit', [
            'por' => $overhead_por,
            'dasarOptions' => $dasarOptions,
        ]);
    }

    public function update(Request $request, OverheadPor $overhead_por)
    {
        $data = $request->validate([
            'periode' => ['required', 'string', 'max:7'],
            'dasar_alokasi' => ['required', 'string', 'max:50'],
            'total_overhead_bulanan' => ['required', 'numeric', 'min:0'],
            'total_jam_dasar_alokasi' => ['required', 'numeric', 'min:0.01'],
        ]);

        $data['por_per_jam'] = $data['total_overhead_bulanan'] / $data['total_jam_dasar_alokasi'];

        $overhead_por->update($data);

        return redirect()->route('overhead-por.index')->with('success', 'Data Overhead POR berhasil diupdate');
    }

    public function destroy(OverheadPor $overhead_por)
    {
        $overhead_por->delete();

        return redirect()->route('overhead-por.index')->with('success', 'Data Overhead POR berhasil dihapus');
    }
}
