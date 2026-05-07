@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Penggajian</h2>
        </div>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form action="{{ route('penggajian.update', $item->id_gaji) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Karyawan</label>
                        <select name="employee_id" id="employee_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">-- Pilih --</option>
                            @foreach ($employees as $e)
                                <option value="{{ $e->id }}"
                                    data-salary="{{ (float)($e->base_salary ?? 0) }}"
                                    data-type="{{ $e->employee_type }}"
                                    data-tarif-per-jam="{{ (float)($e->tarif_per_jam ?? 0) }}"
                                    data-jam-kerja="{{ (float)($e->jam_kerja_per_bulan ?? 0) }}"
                                    {{ old('employee_id', $item->employee_id) == $e->id ? 'selected' : '' }}>
                                    {{ $e->name }} ({{ $e->employee_number }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                        <div id="employee_type_badge" class="mt-2 hidden">
                            <span id="badge_text" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
                        </div>
                    </div>



                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Penggajian</label>
                            <input type="date" name="tanggal_penggajian" value="{{ old('tanggal_penggajian', optional($item->tanggal_penggajian)->toDateString()) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                            @error('tanggal_penggajian') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">No Transaksi</label>
                            <input type="text" name="no_transaksi_gaji" value="{{ old('no_transaksi_gaji', $item->no_transaksi_gaji) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                            @error('no_transaksi_gaji') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bonus</label>
                        <input type="number" step="0.01" name="bonus" value="{{ old('bonus', $item->bonus) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        @error('bonus') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tunjangan Makan</label>
                            <input type="number" step="0.01" name="tunjangan_makan" value="{{ old('tunjangan_makan', $item->tunjangan_makan) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tunjangan Jabatan</label>
                            <input type="number" step="0.01" name="tunjangan_jabatan" value="{{ old('tunjangan_jabatan', $item->tunjangan_jabatan) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tunjangan Transportasi</label>
                            <input type="number" step="0.01" name="tunjangan_lainnya" value="{{ old('tunjangan_lainnya', $item->tunjangan_lainnya ?? 0) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lembur</label>
                            <input type="number" step="0.01" name="lembur" value="{{ old('lembur', $item->lembur) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gaji Kotor (auto)</label>
                            <input type="text" id="gaji_kotor" value="Rp {{ number_format((float)$item->total_gaji_bersih, 0, ',', '.') }}" class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50" disabled />
                        </div>
                        <div id="tarif_per_jam_display" class="hidden">
                            <label class="block text-sm font-medium text-gray-700">Tarif per Jam</label>
                            <input type="text" id="display_tarif_per_jam" class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50 text-blue-700 font-medium" readonly />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Potongan</label>
                            <input type="number" step="0.01" name="potongan_gaji" value="{{ old('potongan_gaji', $item->potongan_gaji) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                        <input type="hidden" name="tarif" id="tarif" value="{{ old('tarif', $item->tarif) }}">
                        <div id="tarif_wrapper" class="hidden"></div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Total Gaji Bersih (auto)</label>
                        <input type="text" id="total_gaji_bersih" value="Rp {{ number_format((float) $item->total_gaji_bersih, 0, ',', '.') }}" class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50" disabled />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Detail Potongan</label>
                        <textarea name="detail_potongan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('detail_potongan', $item->detail_potongan) }}</textarea>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2">
                        <a href="{{ route('penggajian.index') }}" class="px-4 py-2 border rounded-md">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
(function() {
    const select = document.getElementById('employee_id');
    const btklInfo = null;
    const tarifWrapper = document.getElementById('tarif_wrapper');
    const tarifInput = document.querySelector('input[name="tarif"]');
    const badgeDiv = document.getElementById('employee_type_badge');
    const badgeText = document.getElementById('badge_text');
    let tarifBulanan = 0;

    function fmt(n) {
        return 'Rp ' + n.toLocaleString('id-ID', {minimumFractionDigits: 0});
    }

    function updateBtklInfo() {
        const opt = select.options[select.selectedIndex];
        const type = opt ? opt.getAttribute('data-type') : '';
        const tarifPerJam = opt ? parseFloat(opt.getAttribute('data-tarif-per-jam') || '0') : 0;
        const salary = opt ? parseFloat(opt.getAttribute('data-salary') || '0') : 0;
        const jamKerja = opt ? parseFloat(opt.getAttribute('data-jam-kerja') || '0') : 0;

        // Hitung tarif upah per bulan
        if (type === 'BTKL') {
            tarifBulanan = tarifPerJam * (jamKerja > 0 ? jamKerja : 0);
            tarifWrapper.classList.add('hidden');
            if (tarifInput) tarifInput.value = tarifBulanan;
            document.getElementById('tarif_per_jam_display').classList.remove('hidden');
            document.getElementById('display_tarif_per_jam').value = 'Rp ' + Math.round(tarifPerJam).toLocaleString('id-ID');
        } else {
            tarifBulanan = salary;
            tarifWrapper.classList.remove('hidden');
            if (tarifInput && salary > 0) tarifInput.value = salary;
            document.getElementById('tarif_per_jam_display').classList.add('hidden');
        }

        // Badge
        if (type) {
            badgeDiv.classList.remove('hidden');
            if (type === 'BTKL') {
                badgeText.textContent = 'BTKL - Tenaga Kerja Langsung';
                badgeText.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
            } else {
                badgeText.textContent = 'BTKTL - Tenaga Kerja Tidak Langsung';
                badgeText.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
            }
        } else {
            badgeDiv.classList.add('hidden');
        }

        // Info BTKL
        calculateGaji();
    }

    function calculateGaji() {
        const bonus = parseFloat(document.querySelector('input[name="bonus"]')?.value) || 0;
        const tunjanganMakan = parseFloat(document.querySelector('input[name="tunjangan_makan"]')?.value) || 0;
        const tunjanganJabatan = parseFloat(document.querySelector('input[name="tunjangan_jabatan"]')?.value) || 0;
        const tunjanganLainnya = parseFloat(document.querySelector('input[name="tunjangan_lainnya"]')?.value) || 0;
        const lembur = parseFloat(document.querySelector('input[name="lembur"]')?.value) || 0;
        const potongan = parseFloat(document.querySelector('input[name="potongan_gaji"]')?.value) || 0;

        const gajiKotorEl = document.getElementById('gaji_kotor');
        const totalBersihEl = document.getElementById('total_gaji_bersih');

        const gajiKotorValue = tarifBulanan + bonus + tunjanganMakan + tunjanganJabatan + tunjanganLainnya + lembur;
        if (gajiKotorEl) gajiKotorEl.value = 'Rp ' + Math.round(gajiKotorValue).toLocaleString('id-ID');

        const totalBersih = Math.max(0, gajiKotorValue - potongan);
        if (totalBersihEl) totalBersihEl.value = 'Rp ' + Math.round(totalBersih).toLocaleString('id-ID');
    }

    // Pasang listener ke semua input tunjangan/potongan
    ['bonus','tunjangan_makan','tunjangan_jabatan','tunjangan_lainnya','lembur','potongan_gaji'].forEach(name => {
        const el = document.querySelector(`input[name="${name}"]`);
        if (el) el.addEventListener('input', calculateGaji);
    });

    select && select.addEventListener('change', updateBtklInfo);
    updateBtklInfo();
})();
</script>
