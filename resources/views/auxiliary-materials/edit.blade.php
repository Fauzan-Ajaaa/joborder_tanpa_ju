@extends('layouts.app')

@section('title', 'Edit Bahan Penolong')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Edit Bahan Penolong</h1>
        <a href="{{ route('auxiliary-materials.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <form action="{{ route('auxiliary-materials.update', $auxiliaryMaterial) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Kode Bahan</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $auxiliaryMaterial->code) }}" readonly
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nama Bahan</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $auxiliaryMaterial->name) }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="unit" class="block text-sm font-medium text-gray-700">Satuan</label>
                    <select name="unit" id="unit" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="" disabled {{ old('unit', $auxiliaryMaterial->unit) ? '' : 'selected' }}>Pilih Satuan</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->code }}" {{ old('unit', $auxiliaryMaterial->unit) == $unit->code ? 'selected' : '' }}>
                                {{ $unit->name }} ({{ $unit->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('unit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="price_per_unit" class="block text-sm font-medium text-gray-700">Harga Per Unit</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Rp.</span>
                        </div>
                        <input type="text" name="price_per_unit" id="price_per_unit" value="{{ old('price_per_unit', (int) $auxiliaryMaterial->price_per_unit) }}" required
                            class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" autocomplete="off">
                    </div>
                    @error('price_per_unit')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="stock" class="block text-sm font-medium text-gray-700">Stok Saat Ini</label>
                    <input type="text" id="stock"
                        value="{{ rtrim(rtrim(number_format($auxiliaryMaterial->stock, 2, '.', ''), '0'), '.') }} {{ $auxiliaryMaterial->unit }}"
                        readonly
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">
                        📌 Stok tidak dapat diubah manual. Gunakan <strong>Kartu Stok</strong> untuk mencatat mutasi stok (masuk/keluar).
                    </p>
                    <div class="mt-2">
                        <a href="{{ route('auxiliary-materials.stock-card', $auxiliaryMaterial) }}" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md text-xs font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            📋 Lihat Kartu Stok
                        </a>
                        <a href="{{ route('purchases.create') }}?auxiliary_material_id={{ $auxiliaryMaterial->id }}" class="ml-2 inline-flex items-center px-3 py-1.5 bg-blue-600 border border-transparent rounded-md text-xs font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            📦 Tambah Stok (Pembelian)
                        </a>
                    </div>
                </div>

                <div>
                    <label for="minimum_stock" class="block text-sm font-medium text-gray-700">Minimal Stok</label>
                    <input
                        type="number"
                        name="minimum_stock"
                        id="minimum_stock"
                        value="{{ old('minimum_stock') !== null ? old('minimum_stock') : rtrim(rtrim(number_format($auxiliaryMaterial->minimum_stock, 2, '.', ''), '0'), '.') }}"
                        min="0"
                        step="0.01"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    @error('minimum_stock')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea name="description" id="description" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $auxiliaryMaterial->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Konversi Satuan (opsional)</label>
                    <div id="conversion-rows" class="mt-2 space-y-2">
                        @php
                            $oldConversions = old('conversions');
                            $rows = [];
                            if (is_array($oldConversions)) {
                                $rows = $oldConversions;
                            } else {
                                foreach ($auxiliaryMaterial->unitConversions as $conv) {
                                    $rows[] = ['factor' => $conv->factor, 'to_unit' => $conv->to_unit];
                                }
                            }
                        @endphp

                        @foreach($rows as $index => $conv)
                            <div class="flex items-center space-x-2 conversion-row">
                                <select name="conversions[{{ $index }}][to_unit]" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Pilih Satuan</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->code }}" {{ ($conv['to_unit'] ?? '') == $unit->code ? 'selected' : '' }}>
                                            {{ $unit->name }} ({{ $unit->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="px-2 py-1 bg-red-600 text-white text-xs rounded-md hover:bg-red-700 remove-conversion-row">Hapus</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="add-conversion-row" class="mt-2 inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700">
                        Tambah Satuan Lainnya
                    </button>
                    <p class="mt-1 text-xs text-gray-500">Contoh: 12 Butir = 1 Kg. Kamu bisa menambah lebih dari satu baris konversi untuk satu bahan.</p>
                </div>

                <div class="md:col-span-2">
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih Supplier (Opsional)</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $auxiliaryMaterial->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('auxiliary-materials.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('conversion-rows');
        var addBtn = document.getElementById('add-conversion-row');
        var unitSelect = document.getElementById('unit');

        function currentBaseUnitLabel() {
            if (!unitSelect) return 'Satuan Utama';
            var option = unitSelect.options[unitSelect.selectedIndex];
            return option && option.value ? option.text : 'Satuan Utama';
        }

        function updateAllBaseLabels() {
            if (!container) return;
            var labels = container.querySelectorAll('.base-unit-label');
            var text = currentBaseUnitLabel();
            labels.forEach(function (el) { el.textContent = text; });
        }

        function bindRemoveButtons() {
            if (!container) return;
            container.querySelectorAll('.remove-conversion-row').forEach(function (btn) {
                btn.onclick = function () {
                    this.closest('.conversion-row')?.remove();
                    updateAllBaseLabels();
                };
            });
        }

        function nextIndex() {
            if (!container) return 0;
            var rows = container.querySelectorAll('.conversion-row');
            return rows.length;
        }

        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var index = nextIndex();
                var div = document.createElement('div');
                div.className = 'flex items-center space-x-2 conversion-row';
                div.innerHTML = `
                    <select name="conversions[${index}][to_unit]" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Pilih Satuan</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->code }}">{{ $unit->name }} ({{ $unit->code }})</option>
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

        bindRemoveButtons();
        updateAllBaseLabels();

        function formatInt(value) {
            var numeric = value.replace(/[^0-9]/g, '');
            if (!numeric) return '';
            return numeric.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        var priceInput = document.getElementById('price_per_unit');

        if (priceInput) {
            // Format tampilan awal harga sebagai ribuan
            priceInput.value = formatInt(priceInput.value);
            // Format ulang setiap kali user mengetik
            priceInput.addEventListener('input', function () {
                this.value = formatInt(this.value);
            });
        }

        var form = document.querySelector('form[action*="auxiliary-materials"]');
        if (form) {
            form.addEventListener('submit', function () {
                // Sebelum dikirim ke server, hapus titik pada harga
                if (priceInput) {
                    priceInput.value = priceInput.value.replace(/\./g, '');
                }
                // Field stock dan minimum_stock dibiarkan angka murni tanpa formatting,
                // supaya update dan validasi numeric berjalan normal.
            });
        }
    });
</script>
@endpush
