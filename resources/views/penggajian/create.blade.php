@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tambah Penggajian</h2>
        </div>
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form action="{{ route('penggajian.store') }}" method="POST" class="space-y-4">
                    @csrf

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
                                    {{ old('employee_id') == $e->id ? 'selected' : '' }}>
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
                            <input type="date" name="tanggal_penggajian" value="{{ old('tanggal_penggajian', now()->toDateString()) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                            @error('tanggal_penggajian') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">No Transaksi</label>
                            <input type="text" name="no_transaksi_gaji" value="" placeholder="Otomatis" class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50" readonly />
                            @error('no_transaksi_gaji') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bonus</label>
                        <input type="number" step="0.01" name="bonus" value="{{ old('bonus', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        @error('bonus') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tunjangan Makan</label>
                            <input type="number" step="0.01" name="tunjangan_makan" value="{{ old('tunjangan_makan', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tunjangan Jabatan</label>
                            <input type="number" step="0.01" name="tunjangan_jabatan" value="{{ old('tunjangan_jabatan', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tunjangan Transportasi</label>
                            <input type="number" step="0.01" name="tunjangan_lainnya" value="{{ old('tunjangan_lainnya', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lembur</label>
                            <input type="number" step="0.01" name="lembur" value="{{ old('lembur', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gaji Kotor (auto)</label>
                            <input type="text" value="Rp 0" id="gaji_kotor" class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50" readonly />
                        </div>
                        <div id="tarif_per_jam_display" class="hidden">
                            <label class="block text-sm font-medium text-gray-700">Tarif per Jam</label>
                            <input type="text" id="display_tarif_per_jam" class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50 text-blue-700 font-medium" readonly />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Potongan</label>
                            <input type="number" step="0.01" name="potongan_gaji" value="{{ old('potongan_gaji', 0) }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                        </div>
                        <input type="hidden" name="tarif" id="tarif" value="{{ old('tarif', 0) }}">
                        <div id="tarif_wrapper" class="hidden"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total Gaji Bersih (auto)</label>
                            <input type="text" value="Rp 0" id="total_gaji_bersih" class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50" readonly />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Detail Potongan</label>
                        <textarea name="detail_potongan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('detail_potongan') }}</textarea>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2">
                        <a href="{{ route('penggajian.index') }}" class="px-4 py-2 border rounded-md">Batal</a>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    (function() {
        const select = document.getElementById('employee_id');
        const tarif = document.getElementById('tarif');
        const gajiKotor = document.getElementById('gaji_kotor');
        const totalGajiBersih = document.getElementById('total_gaji_bersih');
        const btklInfo = null;
        const tarifWrapper = document.getElementById('tarif_wrapper');
        const badgeDiv = document.getElementById('employee_type_badge');
        const badgeText = document.getElementById('badge_text');
        let tarifBulanan = 0;
        
        function fmt(n) {
            return 'Rp ' + n.toLocaleString('id-ID', {minimumFractionDigits: 0});
        }

        function updateTarif() {
            const opt = select.options[select.selectedIndex];
            const employeeId = opt ? opt.value : '';
            if (!employeeId) return;

            fetch(`/employees/${employeeId}/data`)
                .then(r => r.json())
                .then(data => {
                    const type = data.employee_type;
                    const tarifPerJam = data.tarif_per_jam;
                    const jamKerja = data.jam_kerja_per_bulan;
                    const salary = data.base_salary;

                    if (type === 'BTKL') {
                        tarifBulanan = tarifPerJam * jamKerja;
                        tarifWrapper.classList.add('hidden');
                        tarif.value = tarifBulanan;
                        document.getElementById('tarif_per_jam_display').classList.remove('hidden');
                        document.getElementById('display_tarif_per_jam').value = fmt(tarifPerJam);
                        // Update badge
                        if (badgeDiv) badgeDiv.classList.remove('hidden');
                        if (badgeText) {
                            badgeText.textContent = 'BTKL - Tenaga Kerja Langsung';
                            badgeText.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                        }
                    } else {
                        tarifBulanan = salary;
                        tarifWrapper.classList.remove('hidden');
                        tarif.value = isNaN(salary) ? 0 : salary;
                        document.getElementById('tarif_per_jam_display').classList.add('hidden');
                        if (badgeDiv) badgeDiv.classList.remove('hidden');
                        if (badgeText) {
                            badgeText.textContent = 'BTKTL - Tenaga Kerja Tidak Langsung';
                            badgeText.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800';
                        }
                    }
                    calculateGaji();
                });
        }
        
        function fmtRp(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        }

        function calculateGaji() {
            const bonus = parseFloat(document.querySelector('input[name="bonus"]').value) || 0;
            const tunjanganMakan = parseFloat(document.querySelector('input[name="tunjangan_makan"]').value) || 0;
            const tunjanganJabatan = parseFloat(document.querySelector('input[name="tunjangan_jabatan"]').value) || 0;
            const tunjanganLainnya = parseFloat(document.querySelector('input[name="tunjangan_lainnya"]').value) || 0;
            const lembur = parseFloat(document.querySelector('input[name="lembur"]').value) || 0;
            const potongan = parseFloat(document.querySelector('input[name="potongan_gaji"]').value) || 0;
            
            // Gaji kotor = tarif upah bulanan + semua tambahan
            const gajiKotorValue = tarifBulanan + bonus + tunjanganMakan + tunjanganJabatan + tunjanganLainnya + lembur;
            gajiKotor.value = fmtRp(gajiKotorValue);
            
            const totalGajiBersihValue = Math.max(0, gajiKotorValue - potongan);
            totalGajiBersih.value = fmtRp(totalGajiBersihValue);
        }
        
        const inputs = [
            'input[name="bonus"]',
            'input[name="tunjangan_makan"]',
            'input[name="tunjangan_jabatan"]',
            'input[name="tunjangan_lainnya"]',
            'input[name="lembur"]',
            'input[name="potongan_gaji"]'
        ];
        
        inputs.forEach(selector => {
            const input = document.querySelector(selector);
            if (input) input.addEventListener('input', calculateGaji);
        });
        
        select && select.addEventListener('change', updateTarif);
        updateTarif();
    })();
</script>
@endsection
