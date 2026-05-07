@extends('layouts.app')

@section('title', 'Tambah Bahan Penolong')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">Tambah Bahan Penolong</h1>
        <a href="{{ route('auxiliary-materials.index') }}"
            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <form action="{{ route('auxiliary-materials.store') }}" method="POST" id="auxForm" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Kode -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Kode Bahan</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" name="code" id="code" value="{{ $nextCode ?? old('code') }}" readonly
                            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" id="refresh-code"
                            class="mt-1 px-3 py-2 bg-green-600 text-white text-xs rounded-md hover:bg-green-700 whitespace-nowrap">
                            Refresh Kode
                        </button>
                    </div>
                    @error('code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Nama -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama Bahan *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Satuan Dasar -->
                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700">Satuan Dasar *</label>
                    <select name="unit" id="unit" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        onchange="updateAllBaseLabels()">
                        <option value="" disabled {{ old('unit') ? '' : 'selected' }}>Pilih Satuan</option>
                        @foreach($units as $u)
                            <option value="{{ $u->code }}" {{ old('unit') == $u->code ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('unit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Harga Satuan -->
                <div>
                    <label for="price_per_unit" class="block text-sm font-medium text-gray-700">Harga Satuan Dasar *</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Rp.</span>
                        </div>
                        <input type="text" name="price_per_unit" id="price_per_unit"
                            value="{{ old('price_per_unit') }}" required
                            class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            autocomplete="off" placeholder="0">
                    </div>
                    @error('price_per_unit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Stok Awal -->
                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700">Stok Awal *</label>
                    <input type="number" name="stock" id="stock" value="{{ old('stock', 0) }}"
                        required min="0" step="0.01"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('stock')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Stok Minimum -->
                <div>
                    <label for="minimum_stock" class="block text-sm font-medium text-gray-700">Stok Minimum</label>
                    <input type="number" name="minimum_stock" id="minimum_stock"
                        value="{{ old('minimum_stock', 0) }}" min="0" step="0.01"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Batas minimum stok untuk peringatan</p>
                    @error('minimum_stock')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Deskripsi -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea name="description" id="description" rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <!-- Konversi Satuan (opsional) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Konversi Satuan (opsional)</label>
                    <div id="conversion-rows" class="mt-2 space-y-2">
                        <!-- Row template akan di-clone via JS -->
                    </div>
                    <button type="button" id="add-conversion-row" class="mt-2 inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700">
                        Tambah Satuan Lainnya
                    </button>
                    <p class="mt-1 text-xs text-gray-500">Contoh: 12 Butir = 1 Kg. Kamu bisa menambah lebih dari satu baris konversi untuk satu bahan.</p>
                </div>

                <!-- Supplier -->
                <div class="md:col-span-2">
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700">Supplier</label>
                    <select name="supplier_id" id="supplier_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih Supplier (Opsional)</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('auxiliary-materials.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── ELEMEN UTAMA ─────────────────────────────────────────────── */
    const unitSelect   = document.getElementById('unit');
    const container    = document.getElementById('conversion-rows');
    const addBtn       = document.getElementById('add-conversion-row');
    const refreshBtn   = document.getElementById('refresh-code');
    const codeInput    = document.getElementById('code');
    const priceInput   = document.getElementById('price_per_unit');
    const form         = document.getElementById('auxForm');

    /* ── REFRESH KODE ─────────────────────────────────────────────── */
    if (refreshBtn && codeInput) {
        refreshBtn.addEventListener('click', function () {
            refreshBtn.disabled    = true;
            refreshBtn.textContent = 'Loading...';

            fetch('{{ route("auxiliary-materials.generate-code") }}')
                .then(r => r.json())
                .then(data => {
                    codeInput.value        = data.code;
                    refreshBtn.textContent = 'Refresh Kode';
                    refreshBtn.disabled    = false;
                })
                .catch(() => {
                    alert('Gagal mengambil kode baru');
                    refreshBtn.textContent = 'Refresh Kode';
                    refreshBtn.disabled    = false;
                });
        });
    }

    /* ── FORMAT HARGA ─────────────────────────────────────────────── */
    function formatNumber(value) {
        const numeric = value.replace(/[^0-9]/g, '');
        if (!numeric) return '';
        return numeric.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    if (priceInput) {
        priceInput.value = formatNumber(priceInput.value);
        priceInput.addEventListener('input', function () {
            this.value = formatNumber(this.value);
        });
        // Saat submit, hapus titik pemisah ribuan sebelum dikirim
        if (form) {
            form.addEventListener('submit', function () {
                priceInput.value = priceInput.value.replace(/\./g, '');
            });
        }
    }

    /* ── LABEL SATUAN DASAR DI TIAP BARIS ────────────────────────── */
    function currentBaseUnitLabel() {
        if (!unitSelect) return 'Satuan Dasar';
        const opt = unitSelect.options[unitSelect.selectedIndex];
        return opt && opt.value ? opt.text : 'Satuan Dasar';
    }

    function updateAllBaseLabels() {
        const label = currentBaseUnitLabel();
        container.querySelectorAll('.base-unit-label').forEach(el => el.textContent = label);
    }

    if (unitSelect) {
        unitSelect.addEventListener('change', updateAllBaseLabels);
    }

    /* ── TAMBAH BARIS KONVERSI ────────────────────────────────────── */
    function getNextIndex() {
        return container.querySelectorAll('.conversion-row').length;
    }

    function bindRemoveButtons() {
        container.querySelectorAll('.remove-conversion-row').forEach(function (btn) {
            btn.onclick = function () {
                this.closest('.conversion-row')?.remove();
                updateAllBaseLabels();
            };
        });
    }

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            var index = getNextIndex();
            var div = document.createElement('div');
            div.className = 'flex items-center space-x-2 conversion-row';
            div.innerHTML = `
                <select name="conversions[${index}][to_unit]" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Pilih Satuan</option>
                    @foreach($units as $u)
                        <option value="{{ $u->code }}">{{ $u->name }} ({{ $u->code }})</option>
                    @endforeach
                </select>
                <button type="button" class="px-2 py-1 bg-red-600 text-white text-xs rounded-md hover:bg-red-700 remove-conversion-row">Hapus</button>
            `;
            container.appendChild(div);
            updateAllBaseLabels();
            bindRemoveButtons();
        });
    }

    if (unitSelect) {
        unitSelect.addEventListener('change', updateAllBaseLabels);
    }

    // Tambahkan satu baris default kosong supaya user langsung lihat strukturnya
    if (container && getNextIndex() === 0 && addBtn) {
        addBtn.click();
    } else {
        updateAllBaseLabels();
    }

});
</script>
@endpush