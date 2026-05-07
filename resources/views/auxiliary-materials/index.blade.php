@extends('layouts.app')

@section('title', 'Bahan Penolong')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Daftar Bahan Penolong</h1>
        <a href="{{ route('auxiliary-materials.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Tambah Bahan Penolong
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minimal Stok</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden">COA</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($auxiliaryMaterials as $material)
                    <tr data-base-unit="{{ $material->unit }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $material->code }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $material->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                            Rp <span class="price-display" data-base-price="{{ (float) ($material->master_price_per_unit ?? $material->price_per_unit ?? $material->calculated_price ?? 0) }}">
                                {{ number_format($material->master_price_per_unit ?? $material->price_per_unit ?? $material->calculated_price ?? 0, 0, ',', '.') }}
                            </span>
                            <span class="price-unit text-xs text-gray-500">/{{ $unitMap[$material->unit] ?? $material->unit }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                            <span class="stock-display" data-base-stock="{{ (float) ($material->stock ?? $material->calculated_stock ?? 0) }}">
                                {{ number_format($material->stock ?? $material->calculated_stock ?? 0, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-gray-500">({{ $unitMap[$material->unit] ?? $material->unit }})</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                            @php
                                $min = (float) $material->minimum_stock;
                                $minFormatted = number_format($min, 2, ',', '.') == number_format($min, 0, ',', '.') . ',00'
                                    ? number_format($min, 0, ',', '.')
                                    : number_format($min, 2, ',', '.');
                            @endphp
                            {{ $minFormatted }} {{ $material->unit }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <form action="{{ route('auxiliary-materials.update-unit', $material) }}" method="POST">
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
                                                $purchaseItem = \App\Models\PurchaseItem::where('auxiliary_material_id', $material->id)
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
                            @php
                                $currentStock = (float) $material->calculated_stock;
                                $minStock = (float) $material->minimum_stock;
                            @endphp
                            @if($minStock > 0 && $currentStock < $minStock)
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
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800">
                                    {{ $material->chartOfAccount->code }}
                                </span>
                                <span class="ml-1 text-xs text-gray-600">{{ $material->chartOfAccount->account_name }}</span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('auxiliary-materials.show', $material) }}" class="text-blue-600 hover:text-blue-900">Lihat</a>
                            <a href="{{ route('auxiliary-materials.edit', $material) }}" class="text-yellow-600 hover:text-yellow-900">Edit</a>
                            <form action="{{ route('auxiliary-materials.destroy', $material) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus bahan penolong ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data bahan penolong</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $auxiliaryMaterials->links() }}
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

        // Function to update minimum stock via AJAX
        window.updateMinimumStock = function(materialId, value) {
            // Input type="number" already provides clean numeric value
            var cleanValue = parseFloat(value) || 0;
            var input = document.querySelector(`input[name="minimum_stock_${materialId}"]`);
            
            // Show loading state
            if (input) {
                input.style.backgroundColor = '#fef3c7';
                input.disabled = true;
            }
            
            fetch(`/auxiliary-materials/${materialId}/minimum-stock`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    minimum_stock: cleanValue
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('Stok minimum berhasil diupdate', 'success');
                    // Reset input style
                    if (input) {
                        input.style.backgroundColor = '';
                        input.disabled = false;
                    }
                } else {
                    // Show error message
                    showNotification('Gagal mengupdate stok minimum', 'error');
                    // Reset input style and revert value
                    if (input) {
                        input.style.backgroundColor = '';
                        input.disabled = false;
                        // Revert to original value from database if needed
                        input.value = input.getAttribute('default-value');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan', 'error');
                // Reset input style
                if (input) {
                    input.style.backgroundColor = '';
                    input.disabled = false;
                    input.value = input.getAttribute('default-value');
                }
            });
        };

        // Function to show notifications
        window.showNotification = function(message, type) {
            // Create notification element
            var notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-md text-white text-sm font-medium z-50 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        };

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

            select.addEventListener('change', function (e) {
                // Prevent form submission — dropdown is display-only
                e.preventDefault();
                if (form) form.onsubmit = function() { return false; };

                var row = this.closest('tr');
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
                    stockUnitSpan.textContent = '(' + selectedOption.textContent + ')';
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

                // Update kartu stok link dengan unit yang dipilih
                updateStockCardLink(row, selectedUnit);
            });

            // Block form submit entirely
            if (form) {
                form.addEventListener('submit', function(e) { e.preventDefault(); });
            }
        });
    });
</script>
@endpush


