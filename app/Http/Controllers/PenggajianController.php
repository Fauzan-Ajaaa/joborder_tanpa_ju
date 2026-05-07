<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Penggajian;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenggajianController extends Controller
{
    public function index(Request $request)
    {
        $query = Penggajian::with('employee')->latest('tanggal_penggajian');

        if ($request->filled('from')) {
            $query->whereDate('tanggal_penggajian', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('tanggal_penggajian', '<=', $request->date('to'));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        $items = $query->paginate(15)->appends($request->query());
        $employees = Employee::orderBy('name')->get(['id','name','employee_number']);
        return view('penggajian.index', compact('items','employees'));
    }

    public function create()
    {
        $employees = Employee::orderBy('name')->get(['id','name','employee_number','base_salary','employee_type','tarif_per_jam']);
        return view('penggajian.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, true);
        // Server-side default: tarif from employee base_salary if not provided
        if (empty($data['tarif']) || (float)$data['tarif'] <= 0) {
            $emp = Employee::find($data['employee_id']);
            if ($emp) {
                $data['tarif'] = (float) ($emp->base_salary ?? 0);
            }
        }
        $computed = $this->computeTotals($data);
        $item = Penggajian::create(array_merge($data, $computed));
        return redirect()->route('penggajian.show', $item->id_gaji)->with('success', 'Penggajian berhasil dibuat');
    }

    public function show(Penggajian $penggajian)
    {
        $penggajian->load('employee');
        return view('penggajian.show', ['item' => $penggajian]);
    }
    
    /**
     * Menampilkan slip gaji
     */
    public function slip(Penggajian $penggajian)
    {
        $penggajian->load('employee');
        return view('penggajian.slip', ['penggajian' => $penggajian]);
    }

    public function edit(Penggajian $penggajian)
    {
        $employees = Employee::orderBy('name')->get(['id','name','employee_number','base_salary','employee_type','tarif_per_jam']);
        return view('penggajian.edit', ['item' => $penggajian, 'employees' => $employees]);
    }

    public function update(Request $request, Penggajian $penggajian)
    {
        $data = $this->validateData($request, false, $penggajian->id_gaji);
        // Server-side default: tarif from employee base_salary if not provided
        if (empty($data['tarif']) || (float)$data['tarif'] <= 0) {
            $emp = Employee::find($data['employee_id']);
            if ($emp) {
                $data['tarif'] = (float) ($emp->base_salary ?? 0);
            }
        }
        $computed = $this->computeTotals($data);
        $penggajian->update(array_merge($data, $computed));
        return redirect()->route('penggajian.show', $penggajian->id_gaji)->with('success', 'Penggajian berhasil diperbarui');
    }

    public function destroy(Penggajian $penggajian)
    {
        $penggajian->delete();
        return redirect()->route('penggajian.index')->with('success', 'Penggajian dihapus');
    }

    /**
     * Proses pembayaran penggajian
     */
    public function pay(Penggajian $penggajian)
    {
        if ($penggajian->status == 1) {
            return redirect()->route('penggajian.index')->with('info', 'Pembayaran sudah diselesaikan sebelumnya');
        }
        $penggajian->update(['status' => 1]);
        return redirect()->route('penggajian.index')->with('success', 'Pembayaran berhasil diselesaikan');
    }

    /**
     * Pengakuan Gaji: Dr Beban Gaji / Cr Utang Gaji
     */
    public function recognize(Penggajian $penggajian)
    {
        if ($penggajian->journal_status) {
            return back()->with('info', 'Pengakuan sudah dilakukan sebelumnya.');
        }

        $journalService = app(\App\Services\JournalService::class);
        $journal = $journalService->createRecognitionJournal($penggajian);

        $penggajian->update([
            'journal_status' => 'recognized',
            'recognition_journal_id' => $journal->id,
        ]);

        return back()->with('success', 'Jurnal pengakuan gaji berhasil dibuat.');
    }

    /**
     * Distribusi Gaji: Dr BDP-BTKL/BOP-BTKTL/Beban Adm / Cr Beban Gaji
     */
    public function distribute(Penggajian $penggajian)
    {
        if ($penggajian->journal_status !== 'recognized') {
            return back()->with('error', 'Lakukan pengakuan gaji terlebih dahulu.');
        }
        if ($penggajian->journal_status === 'distributed') {
            return back()->with('info', 'Distribusi sudah dilakukan sebelumnya.');
        }

        $journalService = app(\App\Services\JournalService::class);
        $journal = $journalService->createDistributionJournal($penggajian);

        $penggajian->update([
            'journal_status' => 'distributed',
            'distribution_journal_id' => $journal?->id,
        ]);

        return back()->with('success', 'Jurnal distribusi gaji berhasil dibuat.');
    }

    protected function validateData(Request $request, bool $creating = true, ?string $currentId = null): array
    {
        $rules = [
            'employee_id' => ['required','exists:employees,id'],
            'tanggal_penggajian' => ['required','date'],
            'no_transaksi_gaji' => array_filter([
                'nullable','string','max:191',
                $creating
                    ? Rule::unique('penggajian','no_transaksi_gaji')
                    : Rule::unique('penggajian','no_transaksi_gaji')->ignore($currentId, 'id_gaji'),
            ]),
            'total_service' => ['nullable','integer','min:0'],
            'bonus' => ['nullable','numeric','min:0'],
            'bonus_service' => ['nullable','numeric','min:0'],
            'total_kehadiran' => ['nullable','integer','min:0'],
            'bonus_kehadiran' => ['nullable','integer','min:0'],
            'total_bonus_kehadiran' => ['nullable','numeric','min:0'],
            'tunjangan_makan' => ['nullable','numeric','min:0'],
            'tunjangan_jabatan' => ['nullable','numeric','min:0'],
            'tunjangan_lainnya' => ['nullable','numeric','min:0'],
            'lembur' => ['nullable','numeric','min:0'],
            'potongan_gaji' => ['nullable','numeric','min:0'],
            'tarif' => ['nullable','numeric','min:0'],
            'detail_potongan' => ['nullable','string'],
        ];

        $validated = $request->validate($rules);

        $defaults = [
            'total_service' => 0,
            'bonus' => 0,
            'bonus_service' => 0,
            'total_kehadiran' => 0,
            'bonus_kehadiran' => 0,
            'total_bonus_kehadiran' => 0,
            'tunjangan_makan' => 0,
            'tunjangan_jabatan' => 0,
            'tunjangan_lainnya' => 0,
            'lembur' => 0,
            'potongan_gaji' => 0,
            'tarif' => 0,
        ];

        return array_merge($defaults, $validated);
    }

    protected function computeTotals(array $data): array
    {
        $tarif = (float)($data['tarif'] ?? 0);
        $bonus = (float)($data['bonus'] ?? 0);
        $tunjanganMakan = (float)($data['tunjangan_makan'] ?? 0);
        $tunjanganJabatan = (float)($data['tunjangan_jabatan'] ?? 0);
        $tunjanganLainnya = (float)($data['tunjangan_lainnya'] ?? 0);
        $lembur = (float)($data['lembur'] ?? 0);
        $potongan = (float)($data['potongan_gaji'] ?? 0);

        // Calculate Gaji Kotor (sum of all components before deductions)
        $gajiKotor = $tarif + $bonus + $tunjanganMakan + $tunjanganJabatan + $tunjanganLainnya + $lembur;
        
        // Calculate Total Gaji Bersih (gross salary minus deductions)
        $totalGajiBersih = $gajiKotor - $potongan;
        if ($totalGajiBersih < 0) {
            $totalGajiBersih = 0;
        }

        return [
            'bonus_service' => 0, // Legacy field, set to 0 since bonus is now nominal
            'total_bonus_kehadiran' => 0, // Legacy field, set to 0 since we removed attendance bonus
            'total_gaji_bersih' => $totalGajiBersih,
        ];
    }
}
