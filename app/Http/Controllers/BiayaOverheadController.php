<?php

namespace App\Http\Controllers;

use App\Models\BiayaOverhead;
use App\Models\ChartOfAccount;
use App\Services\JournalService;
use Illuminate\Http\Request;

class BiayaOverheadController extends Controller
{
    protected $journalService;

    public function __construct(JournalService $journalService)
    {
        $this->journalService = $journalService;
    }

    public function index()
    {
        $overheads = BiayaOverhead::orderByDesc('periode')->paginate(15);

        return view('biaya_overhead.index', compact('overheads'));
    }

    public function create()
    {
        $kategoriOptions = ['BOP', 'Beban Operasional'];

        // Ambil hanya akun dengan kode kepala 5 (misal 511, 512, 5-1000, dst)
        $expenseAccounts = ChartOfAccount::where('code', 'like', '5%')
            ->orderBy('code')
            ->get();

        return view('biaya_overhead.create', compact('kategoriOptions', 'expenseAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kategori' => ['required', 'in:BOP,Beban Operasional'],
            'chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'jenis_biaya' => ['required', 'string', 'max:100'],
            'bop_code' => ['nullable', 'string', 'max:50'],
            'periode' => ['required', 'date'],
            'total' => ['required', 'numeric', 'min:0'],
            'unit_produksi' => ['nullable', 'integer', 'min:0'],
            'product_id' => ['nullable', 'exists:products,id'],
            'status_pembayaran' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string'],
        ]);

        if (empty($data['bop_code'])) {
            // Generate kode BOP mirip job order: BOP-YYYYMMDDHHIISS
            $data['bop_code'] = 'BOP-' . now()->format('YmdHis');
        }

        if (empty($data['status_pembayaran'])) {
            // Biarkan null agar database memakai default enum ('debit')
            unset($data['status_pembayaran']);
        }

        $overhead = BiayaOverhead::create($data);

        // Journal otomatis dibuat oleh BiayaOverheadObserver

        return redirect()->route('biaya-overhead.index')->with('success', 'Data beban overhead berhasil disimpan');
    }

    public function edit(BiayaOverhead $biaya_overhead)
    {
        $kategoriOptions = ['BOP', 'Beban Operasional'];

        $expenseAccounts = ChartOfAccount::where('code', 'like', '5%')
            ->orderBy('code')
            ->get();

        return view('biaya_overhead.edit', [
            'overhead' => $biaya_overhead,
            'kategoriOptions' => $kategoriOptions,
            'expenseAccounts' => $expenseAccounts,
        ]);
    }

    public function update(Request $request, BiayaOverhead $biaya_overhead)
    {
        $data = $request->validate([
            'kategori' => ['required', 'in:BOP,Beban Operasional'],
            'chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'jenis_biaya' => ['required', 'string', 'max:100'],
            'bop_code' => ['nullable', 'string', 'max:50'],
            'periode' => ['required', 'date'],
            'total' => ['required', 'numeric', 'min:0'],
            'unit_produksi' => ['nullable', 'integer', 'min:0'],
            'product_id' => ['nullable', 'exists:products,id'],
            'status_pembayaran' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string'],
        ]);

        if (empty($data['bop_code'])) {
            // Pertahankan kode lama jika ada, atau generate baru dengan format BOP-YYYYMMDDHHIISS
            if ($biaya_overhead->bop_code) {
                $data['bop_code'] = $biaya_overhead->bop_code;
            } else {
                $data['bop_code'] = 'BOP-' . now()->format('YmdHis');
            }
        }

        if (empty($data['status_pembayaran'])) {
            // Pertahankan nilai lama jika ada, atau biarkan null sehingga tidak melanggar enum
            if ($biaya_overhead->status_pembayaran) {
                $data['status_pembayaran'] = $biaya_overhead->status_pembayaran;
            } else {
                unset($data['status_pembayaran']);
            }
        }

        $biaya_overhead->update($data);

        return redirect()->route('biaya-overhead.index')->with('success', 'Data beban overhead berhasil diupdate');
    }

    public function destroy(BiayaOverhead $biaya_overhead)
    {
        $biaya_overhead->delete();

        return redirect()->route('biaya-overhead.index')->with('success', 'Data beban overhead berhasil dihapus');
    }
}
