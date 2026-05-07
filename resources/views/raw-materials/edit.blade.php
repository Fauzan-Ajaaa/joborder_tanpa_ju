@extends('layouts.app')

@section('title', 'Edit Bahan Baku')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Edit Bahan Baku</h1>
        <a href="{{ route('raw-materials.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <form action="{{ route('raw-materials.update', $rawMaterial) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700">Kode Bahan</label>
                <input type="text" name="code" id="code" value="{{ old('code', $rawMaterial->code) }}" readonly
                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nama Bahan</label>
                <input type="text" name="name" id="name" value="{{ old('name', $rawMaterial->name) }}" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="unit" class="block text-sm font-medium text-gray-700">Satuan</label>
                <select name="unit" id="unit" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="" disabled {{ old('unit', $rawMaterial->unit) ? '' : 'selected' }}>Pilih Satuan</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->code }}" {{ old('unit', $rawMaterial->unit) == $unit->code ? 'selected' : '' }}>
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
                    <input type="text" id="price_per_unit_display"
                        value="{{ number_format((int) old('price_per_unit', $rawMaterial->price_per_unit), 0, ',', '.') }}"
                        class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        autocomplete="off" oninput="fmtRp(this,'price_per_unit')">
                </div>
                <input type="hidden" name="price_per_unit" id="price_per_unit" value="{{ old('price_per_unit', (int) $rawMaterial->price_per_unit) }}" required>
                @error('price_per_unit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="stock" class="block text-sm font-medium text-gray-700">Stok Saat Ini</label>
                <input type="text" id="stock" value="{{ number_format($rawMaterial->stock, 2, ',', '.') == number_format($rawMaterial->stock, 0, ',', '.') . ',00' ? number_format($rawMaterial->stock, 0, ',', '.') : number_format($rawMaterial->stock, 2, ',', '.') }} {{ $rawMaterial->unit }}" readonly
                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <p class="mt-1 text-sm text-gray-500">
                    📌 Stok tidak dapat diubah manual. Gunakan <strong>Kartu Stok</strong> untuk mencatat mutasi stok (masuk/keluar).
                </p>
                <div class="mt-2">
                    <a href="{{ route('raw-materials.stock-card', $rawMaterial) }}" class="inline-flex items-center px-3 py-1.5 bg-green-600 border border-transparent rounded-md text-xs font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        📋 Lihat Kartu Stok
                    </a>
                    <a href="{{ route('purchases.create') }}?raw_material_id={{ $rawMaterial->id }}" class="ml-2 inline-flex items-center px-3 py-1.5 bg-blue-600 border border-transparent rounded-md text-xs font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        📦 Tambah Stok (Pembelian)
                    </a>
                </div>
                @error('stock')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="min_stock" class="block text-sm font-medium text-gray-700">Stok Minimum</label>
                <input type="number" name="min_stock" id="min_stock" value="{{ old('min_stock', (int) $rawMaterial->min_stock) }}" required min="0" step="0.01"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <p class="mt-1 text-sm text-gray-500">Stok minimum untuk peringatan stok rendah</p>
                @error('min_stock')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea name="description" id="description" rows="3"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $rawMaterial->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Satuan Lainnya (opsional)</label>
                <div id="conversion-rows" class="mt-2 space-y-2">
                    @php
                        $oldConversions = old('conversions');
                        $rows = [];
                        if (is_array($oldConversions)) {
                            $rows = $oldConversions;
                        } else {
                            if (isset($rawMaterial->unitConversions) && $rawMaterial->unitConversions->count() > 0) {
                                foreach ($rawMaterial->unitConversions as $conv) {
                                    $rows[] = ['factor' => $conv->factor, 'to_unit' => $conv->to_unit];
                                }
                            }
                        }
                    @endphp

                    @if(!empty($rows))
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
                    @endif
                </div>
                <button type="button" id="add-conversion-row" class="mt-2 inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700">
                    Tambah Satuan Lainnya
                </button>
                <p class="mt-1 text-xs text-gray-500">Tambahkan satuan alternatif yang digunakan untuk bahan ini.</p>
            </div>

            <div>
                <label for="supplier_id" class="block text-sm font-medium text-gray-700">Supplier</label>
                <select name="supplier_id" id="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Pilih Supplier (Opsional)</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $rawMaterial->supplier_id) == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end space-x-2">
                <a href="{{ route('raw-materials.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
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
    function fmtRp(input, hiddenId) {
        var raw = input.value.replace(/[^0-9]/g, '');
        var num = parseInt(raw || '0', 10);
        input.value = num > 0 ? num.toLocaleString('id-ID') : '';
        if (hiddenId) document.getElementById(hiddenId).value = raw || '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('conversion-rows');
        var addBtn = document.getElementById('add-conversion-row');

        function bindRemoveButtons() {
            container.querySelectorAll('.remove-conversion-row').forEach(function (btn) {
                btn.onclick = function () {
                    this.closest('.conversion-row')?.remove();
                };
            });
        }

        function nextIndex() {
            return container.querySelectorAll('.conversion-row').length;
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
                bindRemoveButtons();
            });
        }

        // Initialize existing remove buttons
        bindRemoveButtons();
    });
</script>
@endpush
