<?php

namespace App\Http\Controllers\Masterdata;

use App\Http\Controllers\Controller;
use App\Models\Masterdata\Asset;
use App\Services\DepreciationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function __construct(private DepreciationService $depreciationService) {}

    public function index()
    {
        $assets = Asset::orderByDesc('tanggal_perolehan')->paginate(10);
        return view('masterdata.aset.index', compact('assets'));
    }

    public function create()
    {
        return view('masterdata.aset.create', [
            'tipeAssetOptions' => Asset::getTipeAsset(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_asset'          => 'required|string|max:255',
            'tipe_asset'          => 'required|in:bangunan,kendaraan,mesin,peralatan',
            'harga_perolehan'     => 'required|string',
            'nilai_sisa'          => 'required|string',
            'masa_manfaat'        => 'required|integer|min:1',
            'tanggal_perolehan'   => 'required|date',
            'depreciation_method' => 'required|in:straight_line,sum_of_years_digits,double_declining',
        ]);

        $hargaPerolehan = $this->normalizeCurrency($validated['harga_perolehan']);
        $nilaiSisa      = $this->normalizeCurrency($validated['nilai_sisa']);

        // Bangunan wajib garis lurus
        $method = $validated['tipe_asset'] === 'bangunan'
            ? 'straight_line'
            : $validated['depreciation_method'];

        if ($nilaiSisa > $hargaPerolehan) {
            return back()->withErrors(['nilai_sisa' => 'Nilai residu tidak boleh melebihi harga perolehan'])->withInput();
        }

        Asset::create([
            'id_assets'           => (string) Str::uuid(),
            'nama_asset'          => $validated['nama_asset'],
            'tipe_asset'          => $validated['tipe_asset'],
            'harga_perolehan'     => $hargaPerolehan,
            'nilai_sisa'          => $nilaiSisa,
            'masa_manfaat'        => $validated['masa_manfaat'],
            'tanggal_perolehan'   => $validated['tanggal_perolehan'],
            'depreciation_method' => $method,
        ]);

        // Otomatis buat COA BOP-Penyusutan & Akumulasi Penyusutan berdasarkan tipe aset
        \App\Models\ChartOfAccount::createAssetDepreciationAccounts(
            $validated['nama_asset'],
            $validated['tipe_asset']
        );

        return redirect()->route('aset.index')->with('success', 'Aset berhasil ditambahkan');
    }

    public function show(Asset $asset)
    {
        $schedule = $this->depreciationService->calculate(
            $asset->harga_perolehan,
            $asset->nilai_sisa,
            $asset->masa_manfaat,
            $asset->tanggal_perolehan->format('Y-m-d'),
            $asset->depreciation_method,
            'bulanan'
        );

        return view('masterdata.aset.show', compact('asset', 'schedule'));
    }

    public function edit(Asset $asset)
    {
        return view('masterdata.aset.edit', [
            'asset'            => $asset,
            'tipeAssetOptions' => Asset::getTipeAsset(),
        ]);
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'nama_asset'          => 'required|string|max:255',
            'tipe_asset'          => 'required|in:bangunan,kendaraan,mesin,peralatan',
            'harga_perolehan'     => 'required|string',
            'nilai_sisa'          => 'required|string',
            'masa_manfaat'        => 'required|integer|min:1',
            'tanggal_perolehan'   => 'required|date',
            'depreciation_method' => 'required|in:straight_line,sum_of_years_digits,double_declining',
        ]);

        $hargaPerolehan = $this->normalizeCurrency($validated['harga_perolehan']);
        $nilaiSisa      = $this->normalizeCurrency($validated['nilai_sisa']);

        $method = $validated['tipe_asset'] === 'bangunan'
            ? 'straight_line'
            : $validated['depreciation_method'];

        if ($nilaiSisa > $hargaPerolehan) {
            return back()->withErrors(['nilai_sisa' => 'Nilai residu tidak boleh melebihi harga perolehan'])->withInput();
        }

        $asset->update([
            'nama_asset'          => $validated['nama_asset'],
            'tipe_asset'          => $validated['tipe_asset'],
            'harga_perolehan'     => $hargaPerolehan,
            'nilai_sisa'          => $nilaiSisa,
            'masa_manfaat'        => $validated['masa_manfaat'],
            'tanggal_perolehan'   => $validated['tanggal_perolehan'],
            'depreciation_method' => $method,
        ]);

        return redirect()->route('aset.show', $asset)->with('success', 'Data aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('aset.index')->with('success', 'Aset berhasil dihapus');
    }

    /**
     * Tampilkan jadwal penyusutan + simulasi perbandingan 3 metode
     */
    public function calculateDepreciation(Asset $asset, Request $request)
    {
        $mode = $request->get('mode', 'bulanan');

        $schedule = $this->depreciationService->calculate(
            $asset->harga_perolehan,
            $asset->nilai_sisa,
            $asset->masa_manfaat,
            $asset->tanggal_perolehan->format('Y-m-d'),
            $asset->depreciation_method,
            $mode
        );

        // Simulasi hanya untuk non-bangunan
        $simulation = null;
        if ($asset->tipe_asset !== 'bangunan') {
            $simulation = $this->depreciationService->simulate(
                $asset->harga_perolehan,
                $asset->nilai_sisa,
                $asset->masa_manfaat,
                $asset->tanggal_perolehan->format('Y-m-d'),
                'tahunan'
            );
        }

        return view('masterdata.aset.depreciation', compact('asset', 'schedule', 'simulation', 'mode'));
    }

    private function normalizeCurrency(?string $value): float
    {
        if ($value === null || $value === '') return 0.0;
        return (float) str_replace(['.', ','], ['', '.'], $value);
    }
}
