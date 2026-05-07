@extends('layouts.app')

@section('title', 'Tambah Karyawan')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">Tambah Karyawan</h1>
        <a href="{{ route('employees.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <form action="{{ route('employees.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="employee_number" class="block text-sm font-medium text-gray-700">Kode Karyawan</label>
                    <input type="text" name="employee_number" id="employee_number" value="{{ old('employee_number') }}" placeholder="Auto-generated"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Kosongkan untuk generate otomatis</p>
                    @error('employee_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="position" class="block text-sm font-medium text-gray-700">Posisi <span class="text-red-500">*</span></label>
                    <input type="text" name="position" id="position" value="{{ old('position') }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('position')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="department" class="block text-sm font-medium text-gray-700">Departemen</label>
                    <input type="text" name="department" id="department" value="{{ old('department') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('department')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Telepon</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="base_salary_wrapper">
                    <label for="base_salary" class="block text-sm font-medium text-gray-700">Gaji Pokok Perbulan<span class="text-red-500">*</span></label>
                    <input type="text" name="base_salary" id="base_salary" value="{{ old('base_salary') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 currency-input" placeholder="Rp 0">
                    @error('base_salary')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="jam_kerja_wrapper">
                    <label for="jam_kerja_per_bulan" class="block text-sm font-medium text-gray-700">Jam Kerja per Bulan</label>
                    <input type="number" name="jam_kerja_per_bulan" id="jam_kerja_per_bulan" value="{{ old('jam_kerja_per_bulan') }}" min="0" step="0.01"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Misal: 173">
                    @error('jam_kerja_per_bulan')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="hire_date" class="block text-sm font-medium text-gray-700">Tanggal Masuk <span class="text-red-500">*</span></label>
                    <input type="date" name="hire_date" id="hire_date" value="{{ old('hire_date', date('Y-m-d')) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('hire_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                    <select name="status" id="status" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="employee_type" class="block text-sm font-medium text-gray-700">Tipe Karyawan <span class="text-red-500">*</span></label>
                    <select name="employee_type" id="employee_type" required
                        onchange="toggleTarifPerJam(this.value)"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="BTKTL" {{ old('employee_type', 'BTKTL') == 'BTKTL' ? 'selected' : '' }}>BTKTL (Biaya Tenaga Kerja Tidak Langsung)</option>
                        <option value="BTKL" {{ old('employee_type') == 'BTKL' ? 'selected' : '' }}>BTKL (Biaya Tenaga Kerja Langsung)</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">BTKL: Pegawai yang terlibat langsung dalam produksi</p>
                    @error('employee_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div id="tarif_per_jam_wrapper" class="hidden">
                    <label for="tarif_per_jam" class="block text-sm font-medium text-gray-700">Tarif per Jam <span class="text-red-500">*</span></label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Rp</span>
                        </div>
                        <input type="number" name="tarif_per_jam" id="tarif_per_jam"
                            value="{{ old('tarif_per_jam') }}" min="0" step="1"
                            class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="15000">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Masukkan angka tanpa titik/koma. Contoh: 15000</p>
                    @error('tarif_per_jam')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700">Alamat</label>
                    <textarea name="address" id="address" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('employees.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleTarifPerJam(type) {
    const tarifWrapper = document.getElementById('tarif_per_jam_wrapper');
    const tarifInput = document.getElementById('tarif_per_jam');
    const salaryWrapper = document.getElementById('base_salary_wrapper');
    const salaryInput = document.getElementById('base_salary');
    const jamKerjaWrapper = document.getElementById('jam_kerja_wrapper');
    const jamKerjaInput = document.getElementById('jam_kerja_per_bulan');

    if (type === 'BTKL') {
        // BTKL: Tampilkan Tarif per Jam + Jam Kerja per Bulan, HIDE Gaji Pokok
        tarifWrapper.classList.remove('hidden');
        tarifInput.required = true;
        jamKerjaWrapper.classList.remove('hidden');
        jamKerjaInput.required = true;
        salaryWrapper.classList.add('hidden');
        salaryInput.required = false;
        salaryInput.value = '0';
    } else {
        // BTKTL: Tampilkan Gaji Pokok, HIDE Tarif per Jam + Jam Kerja per Bulan
        tarifWrapper.classList.add('hidden');
        tarifInput.required = false;
        tarifInput.value = '';
        jamKerjaWrapper.classList.add('hidden');
        jamKerjaInput.required = false;
        jamKerjaInput.value = '0';
        salaryWrapper.classList.remove('hidden');
        salaryInput.required = true;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    toggleTarifPerJam(document.getElementById('employee_type').value);
});
</script>
@endpush