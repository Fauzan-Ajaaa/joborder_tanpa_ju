@extends('layouts.app')

@section('title', 'Bahan Baku')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Daftar Bahan Baku</h1>
        <a href="{{ route('raw-materials.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Tambah Bahan Baku
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Bahan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Minimum</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden">COA</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($rawMaterials as $material)
                    <tr data-base-unit="{{ $material->unit }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $material->code }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $material->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-left">
                            Rp <span class="price-display" data-base-price="{{ (float) ($material->master_price_per_unit ?? $material->price_per_unit ?? $material->calculated_price ?? 0) }}">
                                {{ number_format($material->master_price_per_unit ?? $material->price_per_unit ?? $material->calculated_price ?? 0, 0, ',', '.') }}
                            </span>
                            <span class="price-unit text-xs text-gray-500">/{{ $unitMap[$material->unit] ?? $material->unit }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-left">
                            <span class="stock-display" data-base-stock="{{ (float) ($material->stock ?? $material->calculated_stock ?? 0) }}">
                                {{ number_format($material->stock ?? $material->calculated_stock ?? 0, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-gray-500">({{ $unitMap[$material->unit] ?? $material->unit }})</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-left">
                            {{ number_format($material->min_stock, 0, ',', '.') }}&nbsp;{{ $material->unit }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <form action="{{ route('raw-materials.update-unit', $material) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                @php
                                    // Ambil semua satuan yang terlibat dalam konversi (from_unit dan to_unit)
                                    $conversionUnits = collect($material->unitConversions)
                                        ->flatMap(fn($c) => [$c->from_unit, $c->to_unit])
                                        ->filter()
                                        ->unique()
                                        ->values()
                                        ->all();
                                    $availableUnits = array_values(array_unique(array_merge([$material->unit], $conversionUnits)));
                                @endphp
                                <select name="unit" class="border-gray-300 rounded-md text-sm unit-select">
                                    @foreach($availableUnits as $unitCode)
                                        @php
                                            $label = $unitMap[$unitCode] ?? $unitCode;
                                            
                                            if ($unitCode === $material->unit) {
                                                $factor = 1.0;
                                            } else {
                                                // Ambil factor dari purchase_items (paling akurat)
                                                // conversion_factor = berapa satuan kecil per satuan beli
                                                // Misal: 1 EKOR = 6 POTONG, maka factor = 6
                                                $purchaseItem = \App\Models\PurchaseItem::where('raw_material_id', $material->id)
                                                    ->whereNotNull('conversion_factor')
                                                    ->where('conversion_factor', '>', 0)
                                                    ->orderByDesc('created_at')
                                                    ->first();
                                                $factor = $purchaseItem ? (float)$purchaseItem->conversion_factor : 1.0;
                                            }
                                        @endphp
                                        <option value="{{ $unitCode }}" data-factor="{{ $factor }}" {{ $material->unit === $unitCode ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($material->calculated_stock < $material->min_stock)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    ⚠️ Kurang
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✅ Aman
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden">
                            @if($material->chartOfAccount)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800">
                                    {{ $material->chartOfAccount->code }}
                                </span>
                                <span class="ml-1 text-xs text-gray-600">{{ $material->chartOfAccount->account_name }}</span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('raw-materials.stock-card', array_filter(['rawMaterial' => $material->id, 'unit' => $material->unit])) }}" class="text-green-600 hover:text-green-900 stock-card-link" data-material-id="{{ $material->id }}">Kartu Stok</a>
                            <a href="{{ route('raw-materials.show', $material) }}" class="text-blue-600 hover:text-blue-900">Lihat</a>
                            <a href="{{ route('raw-materials.edit', $material) }}" class="text-yellow-600 hover:text-yellow-900">Edit</a>
                            <form action="{{ route('raw-materials.destroy', $material) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus bahan baku ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data bahan baku</td>
                    </tr>
                @endforelse
            </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $rawMaterials->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        function formatNumber(x) {
            x = Math.round(Number(x || 0));
            return x.toLocaleString('id-ID', { maximumFractionDigits: 0, minimumFractionDigits: 0 });
        }

        function updateStockCardLink(row, unitCode) {
            const link = row.querySelector('.stock-card-link');
            if (!link) return;
            const url = new URL(link.href);
            url.searchParams.set('unit', unitCode);
            link.href = url.toString();
        }

        document.querySelectorAll('.unit-select').forEach(function (select) {
            const row = select.closest('tr');
            const form = select.closest('form');
            updateStockCardLink(row, select.value);

            select.addEventListener('change', function () {
                if (!row) return;

                var baseUnit = row.getAttribute('data-base-unit');
                var stockSpan = row.querySelector('.stock-display');
                if (!stockSpan) return;

                var baseStock = parseFloat(stockSpan.getAttribute('data-base-stock') || '0');
                var selectedOption = this.options[this.selectedIndex];
                var selectedUnit = this.value;
                var factor = parseFloat(selectedOption.getAttribute('data-factor') || '1');

                // Update stok
                var displayStock = selectedUnit !== baseUnit && factor > 0 ? baseStock * factor : baseStock;
                stockSpan.textContent = formatNumber(displayStock);
                const stockUnitSpan = stockSpan.nextElementSibling;
                if (stockUnitSpan && stockUnitSpan.classList.contains('text-xs')) {
                    stockUnitSpan.textContent = `(${selectedOption.textContent})`;
                }

                // Update harga satuan (Average: bagi dengan factor)
                var priceSpan = row.querySelector('.price-display');
                if (priceSpan) {
                    var basePrice = parseFloat(priceSpan.getAttribute('data-base-price') || '0');
                    var displayPrice = selectedUnit !== baseUnit && factor > 0 ? basePrice / factor : basePrice;
                    priceSpan.textContent = formatNumber(displayPrice);
                    var priceUnitSpan = row.querySelector('.price-unit');
                    if (priceUnitSpan) priceUnitSpan.textContent = '/' + selectedOption.textContent;
                }

                updateStockCardLink(row, selectedUnit);
            });

            // Block form submit — dropdown is display-only
            if (form) {
                form.addEventListener('submit', function(e) { e.preventDefault(); });
            }
        });
    });
</script>
@endpush


