@extends('layouts.app')
@section('title', 'Tambah Aset')
@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Tambah Aset</h1>
                    <p class="text-sm text-gray-500">Aset berwujud yang dapat disusutkan</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('aset.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Batal</a>
                    <button type="submit" form="assetForm" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">Simpan</button>
                </div>
            </div>

            @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <ul class="text-sm text-red-600 list-disc list-inside">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form id="assetForm" action="{{ route('aset.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="space-y-4">
                    <h2 class="text-base font-medium text-gray-900 border-b pb-2">A. Informasi Aset</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="nama_asset" class="block text-sm font-medium text-gray-700">Nama Aset</label>
                            <input type="text" name="nama_asset" id="nama_asset" required value="{{ old('nama_asset') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="tipe_asset" class="block text-sm font-medium text-gray-700">Tipe Aset</label>
                            <select name="tipe_asset" id="tipe_asset" required onchange="handleTipeChange(this.value)"
                                    class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">-- Pilih Tipe --</option>
                                @foreach($tipeAssetOptions as $val => $label)
                                <option value="{{ $val }}" {{ old('tipe_asset') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-base font-medium text-gray-900 border-b pb-2">B. Nilai Aset</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="harga_perolehan" class="block text-sm font-medium text-gray-700">Harga Perolehan</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 sm:text-sm">Rp</span></div>
                                <input type="text" name="harga_perolehan" id="harga_perolehan" required value="{{ old('harga_perolehan') }}"
                                       class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       oninput="formatCurrency(this)">
                            </div>
                        </div>
                        <div>
                            <label for="nilai_sisa" class="block text-sm font-medium text-gray-700">Nilai Residu <span class="text-gray-400 font-normal">(boleh 0)</span></label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500 sm:text-sm">Rp</span></div>
                                <input type="text" name="nilai_sisa" id="nilai_sisa" required value="{{ old('nilai_sisa', '0') }}"
                                       class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                       oninput="formatCurrency(this)">
                            </div>
                        </div>
                        <div>
                            <label for="masa_manfaat" class="block text-sm font-medium text-gray-700">Umur Ekonomis (tahun)</label>
                            <input type="number" name="masa_manfaat" id="masa_manfaat" min="1" required value="{{ old('masa_manfaat') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="tanggal_perolehan" class="block text-sm font-medium text-gray-700">Tanggal Perolehan</label>
                            <input type="date" name="tanggal_perolehan" id="tanggal_perolehan" required value="{{ old('tanggal_perolehan') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <p class="text-xs text-gray-400 mt-1">Tgl ≤15 → mulai bulan ini &nbsp;|&nbsp; Tgl >15 → mulai bulan berikutnya</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-base font-medium text-gray-900 border-b pb-2">C. Metode Penyusutan</h2>
                    <div id="method-wrapper">
                        <select id="depreciation_method_select"
                                class="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                onchange="document.getElementById('depreciation_method').value = this.value">
                            <option value="straight_line" {{ old('depreciation_method','straight_line')==='straight_line'?'selected':'' }}>Garis Lurus</option>
                            <option value="sum_of_years_digits" {{ old('depreciation_method')==='sum_of_years_digits'?'selected':'' }}>Jumlah Angka Tahun</option>
                            <option value="double_declining" {{ old('depreciation_method')==='double_declining'?'selected':'' }}>Saldo Menurun Ganda</option>
                        </select>
                        <input type="hidden" name="depreciation_method" id="depreciation_method"
                               value="{{ old('depreciation_method', 'straight_line') }}">
                        <p id="method-note" class="text-xs text-gray-400 mt-1"></p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function formatCurrency(input) {
    let v = input.value.replace(/[^0-9]/g, '');
    input.value = v ? parseInt(v).toLocaleString('id-ID') : '';
}

function handleTipeChange(tipe) {
    const sel = document.getElementById('depreciation_method_select');
    const hidden = document.getElementById('depreciation_method');
    const note = document.getElementById('method-note');
    if (tipe === 'bangunan') {
        sel.value = 'straight_line';
        sel.disabled = true;
        hidden.value = 'straight_line';
        note.textContent = 'Bangunan hanya menggunakan metode Garis Lurus.';
        note.className = 'text-xs text-amber-600 mt-1';
    } else {
        sel.disabled = false;
        hidden.value = sel.value;
        note.textContent = '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('tanggal_perolehan').value = '{{ old("tanggal_perolehan") }}' || today;
    const tipe = document.getElementById('tipe_asset').value;
    if (tipe) handleTipeChange(tipe);
});
</script>
@endpush
@endsection
