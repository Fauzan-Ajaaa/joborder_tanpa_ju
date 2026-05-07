@extends('layouts.app')

@section('title', 'Buat Retur Pembelian')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Buat Retur Pembelian</h1>
            <p class="mt-1 text-sm text-gray-600">Pilih purchase order dan item yang akan diretur (parsial)</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <ul class="list-disc list-inside text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('purchase-returns.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Purchase Order</label>
                    <select name="purchase_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">-- Pilih Purchase (status received) --</option>
                        @foreach($purchases as $p)
                            <option value="{{ $p->id }}" @selected(old('purchase_id', optional($purchase)->id) == $p->id)>
                                {{ $p->purchase_number }} - {{ $p->supplier?->name }} ({{ $p->purchase_date->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tanggal Retur</label>
                    <input type="date" name="return_date" value="{{ old('return_date', now()->toDateString()) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Alasan Retur</label>
                    <select name="reason" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="damaged" @selected(old('reason')==='damaged')>Damaged</option>
                        <option value="wrong_item" @selected(old('reason')==='wrong_item')>Wrong Item</option>
                        <option value="excess" @selected(old('reason')==='excess')>Excess</option>
                        <option value="quality_issue" @selected(old('reason')==='quality_issue')>Quality Issue</option>
                        <option value="other" @selected(old('reason','other')==='other')>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Catatan</label>
                    <textarea name="notes" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        @if($purchase)
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Pilih Item yang Diretur</h3>
                <p class="text-sm text-gray-500">Isi qty retur untuk item yang ingin dikembalikan. Biaya retur dihitung dari subtotal (qty retur × harga satuan), tanpa PPN dan FOB.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $purchase->purchase_type === 'auxiliary_material' ? 'Bahan Penolong' : 'Bahan Baku' }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Dibeli</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan Retur</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan Retur</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Retur</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($purchase->items as $index => $item)
                            @php
                                $qtyVal = (float) $item->quantity;
                                $qtyDisplay = $qtyVal == floor($qtyVal) ? (string) (int) $qtyVal : number_format($qtyVal, 2, ',', '.');
                                $itemUnit = $item->display_unit;
                                $allUnits = $item->raw_material_id
                                    ? \App\Models\RawMaterial::UNIT_OPTIONS
                                    : \App\Models\AuxiliaryMaterial::UNIT_OPTIONS;
                                if ($itemUnit && !isset($allUnits[$itemUnit])) {
                                    $allUnits[$itemUnit] = $itemUnit;
                                }
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->item_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $qtyDisplay }} {{ $itemUnit }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <select name="items[{{ $index }}][unit]" class="border-gray-300 rounded-md shadow-sm text-sm">
                                        @foreach($allUnits as $unitKey => $unitLabel)
                                            <option value="{{ $unitKey }}" @selected(old("items.$index.unit", $itemUnit) == $unitKey)>{{ $unitKey }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    <input type="hidden" name="items[{{ $index }}][purchase_item_id]" value="{{ $item->id }}">
                                    <input type="number" step="0.01" min="0" name="items[{{ $index }}][quantity]" value="{{ old("items.$index.quantity", 0) }}" class="w-32 border-gray-300 rounded-md shadow-sm text-right">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-sm text-yellow-800">
            Pilih purchase order terlebih dahulu, lalu form akan menampilkan item yang bisa diretur.
        </div>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ route('purchase-returns.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-gray-700 shadow-md">Batal</a>
            <button type="submit" id="submitBtn" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-red-700 shadow-md">Simpan Retur</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="{{ route("purchase-returns.store") }}"]');
    const submitBtn = document.getElementById('submitBtn');
    
    if (form && submitBtn) {
        form.addEventListener('submit', function(e) {
            // Disable button setelah form di-submit
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        });
    }
});
</script>
@endsection
