@extends('layouts.app')
@section('title', 'Edit Bill of Material')
@section('content')
<div class="space-y-6">
<div class="max-w-7xl mx-auto py-6">

<div class="mb-6">
    <a href="{{ route('bill-of-materials.index') }}" class="text-blue-600 hover:text-blue-800">← Kembali ke Daftar BOM</a>
</div>

@if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
@endif

<form action="{{ route('bill-of-materials.update', $billOfMaterial) }}" method="POST">
@csrf
@method('PUT')

{{-- PRODUK & STATUS --}}
<div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
    <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">Produk</h2>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Produk</label>
            <select name="product_id" id="product-select" class="w-full border-gray-300 rounded-md shadow-sm" required>
                <option value="">-- Pilih Produk --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price }}" {{ $billOfMaterial->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nama BOM</label>
            <input type="text" name="name" id="bom-name" class="w-full border-gray-300 rounded-md shadow-sm" value="{{ $billOfMaterial->name }}" readonly>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-500 text-sm pointer-events-none">Rp.</span>
                <input type="text" id="selling_price_display"
                    class="w-full pl-8 border-gray-300 rounded-md shadow-sm"
                    value="{{ number_format((int) $billOfMaterial->getEffectiveSellingPrice(), 0, ',', '.') }}"
                    placeholder="0" oninput="fmtRp(this,'selling_price')">
            </div>
            <input type="hidden" name="selling_price" id="selling_price" value="{{ (int) $billOfMaterial->getEffectiveSellingPrice() }}">
        </div>
    </div>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Produksi (menit)</label>
            <input type="number" id="production_time_minutes" name="production_time_minutes"
                step="0.1" min="0"
                class="w-full border-gray-300 rounded-md shadow-sm"
                value="{{ rtrim(rtrim(number_format($billOfMaterial->processes->first()?->duration_minutes ?? 0, 2, '.', ''), '0'), '.') }}" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Waktu Produksi (jam)</label>
            <input type="text" id="production_time_hours" class="w-full border-gray-200 bg-gray-100 rounded-md" readonly>
            <input type="hidden" id="production_time_hours_raw">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="is_active" class="w-full border-gray-300 rounded-md shadow-sm">
                <option value="1" {{ ($billOfMaterial->is_active ?? true) ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ !($billOfMaterial->is_active ?? true) ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
    </div>
</div>

{{-- BAHAN BAKU --}}
<div class="bg-white shadow sm:rounded-lg p-6">
    <div class="flex justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Bahan Baku</h2>
        <button type="button" id="add-item" class="px-3 py-1 bg-green-600 text-white text-sm rounded">Tambah</button>
    </div>
    <div class="grid grid-cols-12 gap-2 mb-1 px-2 text-xs font-medium text-gray-500 uppercase">
        <div class="col-span-4">Bahan</div><div class="col-span-2">Qty</div>
        <div class="col-span-2">Satuan</div><div class="col-span-2">Harga/Unit</div>
        <div class="col-span-1">Total</div><div class="col-span-1"></div>
    </div>
    <div id="items-container" class="space-y-2">
        @foreach($billOfMaterial->items as $i => $item)
        <div class="grid grid-cols-12 gap-2 item-row border p-2 rounded">
            <div class="col-span-4">
                <select name="items[{{ $i }}][raw_material_id]" class="raw-select w-full border-gray-300 rounded text-sm">
                    <option value="">-- Pilih Bahan --</option>
                    @foreach($rawMaterials as $material)
                    @php
                        $conv = \App\Models\RawMaterialUnitConversion::where('raw_material_id', $material->id)->where('from_unit', $material->unit)->first();
                        $fifoRaw = $material->fifo_price ?? $material->price_per_unit;
                        $convPriceRaw = $conv && $conv->factor > 0 ? round($fifoRaw / $conv->factor) : $fifoRaw;
                    @endphp
                    <option value="{{ $material->id }}"
                        data-unit="{{ $conv ? $conv->to_unit : $material->unit }}"
                        data-cost="{{ $convPriceRaw }}"
                        data-conversion-price="{{ $convPriceRaw }}"
                        {{ $item->raw_material_id == $material->id ? 'selected' : '' }}>{{ $material->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2"><input type="text" name="items[{{ $i }}][quantity]" class="qty w-full border-gray-300 rounded text-sm" value="{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}"></div>
            <div class="col-span-2"><input type="text" name="items[{{ $i }}][unit]" class="unit w-full bg-gray-100 border-gray-200 rounded text-sm" value="{{ (function() use ($rawMaterials, $item) { $m = $rawMaterials->firstWhere('id', $item->raw_material_id); return $m ? $m->display_unit : ($item->unit ?? ''); })() }}" readonly></div>
            <div class="col-span-2"><input type="text" name="items[{{ $i }}][unit_cost]" class="cost w-full border-gray-300 rounded text-sm" value="{{ (function() use ($rawMaterials, $item) { $m = $rawMaterials->firstWhere('id', $item->raw_material_id); return $m ? $m->display_price : ($item->unit_cost ?? 0); })() }}"></div>
            <div class="col-span-1"><input type="text" class="row-total w-full bg-gray-100 border-gray-200 rounded text-sm" readonly></div>
            <div class="col-span-1 flex items-center"><button type="button" class="remove-item text-red-500 text-xs">Hapus</button></div>
        </div>
        @endforeach
    </div>
    <div class="text-right mt-3"><span class="font-semibold">Total Bahan Baku:</span> <span id="total-material">Rp 0</span></div>
</div>

{{-- BAHAN PENOLONG --}}
<div class="bg-white shadow sm:rounded-lg p-6">
    <div class="flex justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Bahan Penolong</h2>
        <button type="button" id="add-auxiliary" class="px-3 py-1 bg-green-600 text-white text-sm rounded">Tambah</button>
    </div>
    <div id="auxiliaries-container" class="space-y-2">
        @foreach($billOfMaterial->auxiliaries as $i => $aux)
        <div class="grid grid-cols-12 gap-2 auxiliary-row border p-2 rounded">
            <div class="col-span-5">
                <select name="auxiliaries[{{ $i }}][auxiliary_material_id]" class="aux-select w-full border-gray-300 rounded text-sm">
                    <option value="">-- Pilih Bahan --</option>
                    @foreach($auxiliaryMaterials as $material)
                    @php
                        $conv = \App\Models\AuxiliaryMaterialUnitConversion::where('auxiliary_material_id', $material->id)->where('from_unit', $material->unit)->first();
                    @endphp
                    <option value="{{ $material->id }}"
                        data-unit="{{ $conv ? $conv->to_unit : $material->unit }}"
                        {{ $aux->auxiliary_material_id == $material->id ? 'selected' : '' }}>{{ $material->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-3"><input type="text" name="auxiliaries[{{ $i }}][quantity]" class="aux-qty w-full border-gray-300 rounded text-sm" value="{{ rtrim(rtrim(number_format($aux->quantity_needed ?? $aux->quantity ?? 0, 2, '.', ''), '0'), '.') }}"></div>
            <div class="col-span-3"><input type="text" name="auxiliaries[{{ $i }}][unit]" class="aux-unit w-full bg-gray-100 border-gray-200 rounded text-sm" value="{{ (function() use ($auxiliaryMaterials, $aux) { $m = $auxiliaryMaterials->firstWhere('id', $aux->auxiliary_material_id); return $m ? $m->display_unit : ($aux->unit ?? ''); })() }}" readonly></div>
            <div class="col-span-1 flex items-center"><button type="button" class="remove-auxiliary text-red-500 text-xs">Hapus</button></div>
        </div>
        @endforeach
    </div>
</div>

{{-- BTKL --}}
<div class="bg-white shadow sm:rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Biaya Tenaga Kerja Langsung (BTKL)</h2>
    <table class="w-full border-collapse">
        <thead><tr class="bg-gray-100">
            <th class="border border-gray-300 px-4 py-2 text-left text-sm">Tarif per Jam</th>
            <th class="border border-gray-300 px-4 py-2 text-left text-sm">Durasi (Jam)</th>
            <th class="border border-gray-300 px-4 py-2 text-left text-sm">Total BTKL</th>
        </tr></thead>
        <tbody><tr>
            <td class="border border-gray-300 px-4 py-2"><input type="text" id="btkl_rate_display" class="w-full bg-gray-100 border-0 text-sm" readonly></td>
            <td class="border border-gray-300 px-4 py-2"><input type="text" id="btkl_hours_display" class="w-full bg-gray-100 border-0 text-sm" readonly></td>
            <td class="border border-gray-300 px-4 py-2"><input type="text" id="btkl_total_display" class="w-full bg-gray-100 border-0 text-sm font-semibold" readonly></td>
        </tr></tbody>
    </table>
    <input type="hidden" id="btkl_rate_per_hour" name="btkl_rate_per_hour">
    <input type="hidden" id="total_btkl_cost" name="total_btkl_cost">
</div>

{{-- BOP --}}
<div class="bg-white shadow sm:rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Biaya Overhead Produksi (BOP)</h2>
    @if($currentPOR && $currentPOR->periode !== $currentPeriod)
    <div class="mb-3 p-3 bg-yellow-50 border border-yellow-300 rounded text-sm text-yellow-800">
        ⚠️ POR bulan ini ({{ $currentPeriod }}) belum diinput. Tarif BOP = Rp 0.
    </div>
    @elseif(!$currentPOR)
    <div class="mb-3 p-3 bg-red-50 border border-red-300 rounded text-sm text-red-800">
        ⚠️ Belum ada data POR. Tarif BOP = Rp 0.
    </div>
    @endif
    <table class="w-full border-collapse">
        <thead><tr class="bg-gray-100">
            <th class="border border-gray-300 px-4 py-2 text-left text-sm">Tarif per Jam</th>
            <th class="border border-gray-300 px-4 py-2 text-left text-sm">Durasi (Jam)</th>
            <th class="border border-gray-300 px-4 py-2 text-left text-sm">Total Overhead</th>
        </tr></thead>
        <tbody><tr>
            <td class="border border-gray-300 px-4 py-2"><input type="text" id="bop_rate_display" class="w-full bg-gray-100 border-0 text-sm" readonly></td>
            <td class="border border-gray-300 px-4 py-2"><input type="text" id="bop_hours_display" class="w-full bg-gray-100 border-0 text-sm" readonly></td>
            <td class="border border-gray-300 px-4 py-2"><input type="text" id="bop_overhead_display" class="w-full bg-gray-100 border-0 text-sm" readonly></td>
        </tr></tbody>
    </table>
    <div class="mt-4 pt-4 border-t flex justify-between items-center">
        <span class="text-lg font-semibold">Total BOP:</span>
        <span id="bop_total_display" class="text-lg font-bold text-green-600">Rp 0</span>
    </div>
    <p class="text-xs text-gray-500 mt-1">BOP = Tarif per jam × Durasi</p>
    <input type="hidden" id="bop_rate_per_hour" name="bop_rate_per_hour" value="{{ $currentPOR ? $currentPOR->por_per_jam : 0 }}">
    <input type="hidden" id="bop_overhead_cost" name="bop_overhead_cost">
    <input type="hidden" id="total_bop_cost" name="total_bop_cost">
</div>

{{-- SUMMARY --}}
<div class="bg-white shadow sm:rounded-lg p-6 text-center">
    <h2 class="text-lg font-semibold mb-3">Total Biaya Produksi</h2>
    <p id="grand-total" class="text-3xl font-bold">Rp 0</p>
</div>

<button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded">Simpan Perubahan</button>
</form>
</div>

@php
    $rawMaterialsJson = $rawMaterials->map(function($m) {
        return ['id' => $m->id, 'name' => $m->name, 'unit' => $m->display_unit, 'price' => $m->display_price];
    })->values();
    $auxMaterialsJson = $auxiliaryMaterials->map(function($m) {
        return ['id' => $m->id, 'name' => $m->name, 'unit' => $m->display_unit, 'price' => $m->display_price];
    })->values();
@endphp
<script>
function fmtRp(input, hiddenId) {
    var raw = input.value.replace(/[^0-9]/g, '');
    var num = parseInt(raw || '0', 10);
    input.value = num > 0 ? num.toLocaleString('id-ID') : '';
    if (hiddenId) document.getElementById(hiddenId).value = raw || '';
}
const BTKL_RATE   = {{ $btklRatePerHour ?? 0 }};
const BOP_RATE    = {{ $currentPOR ? $currentPOR->por_per_jam : 0 }};

const rawMaterialsData = @json($rawMaterialsJson);
const auxMaterialsData = @json($auxMaterialsJson);

let itemCount = {{ $billOfMaterial->items->count() }};
let auxCount   = {{ $billOfMaterial->auxiliaries->count() }};

function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }
// Parse dari input field biasa (angka plain, desimal pakai titik)
function parseNum(v) { return parseFloat((v||'').toString().replace(/[^\d.]/g,'')) || 0; }
// Parse dari display text yang sudah diformat fmt() — strip 'Rp', spasi, titik ribuan
function parseDisplay(v) { return parseFloat((v||'').toString().replace(/[Rp\s.]/g,'').replace(',','.')) || 0; }

function buildOptions(data, selectedId) {
    return data.map(m => `<option value="${m.id}" data-unit="${m.unit}" data-conversion-price="${m.price}" ${m.id==selectedId?'selected':''}>${m.name}</option>`).join('');
}

function calcMaterial() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseNum(row.querySelector('.qty').value);
        const cost = parseNum(row.querySelector('.cost').value);
        const t = qty * cost;
        row.querySelector('.row-total').value = fmt(t);
        total += t;
    });
    // Simpan float penuh di data attribute, display pakai round via fmt()
    const el = document.getElementById('total-material');
    el.textContent = fmt(total);
    el.dataset.raw = total;
    calcGrand();
}

function calcAux() {
    // Bahan penolong tidak ada nominal, tidak perlu kalkulasi
    return;
}

function calcBTKL() {
    const hours = parseFloat(document.getElementById('production_time_hours_raw').value) || parseNum(document.getElementById('production_time_hours').value);
    const rate = BTKL_RATE;
    const total = rate * hours;
    document.getElementById('btkl_rate_display').value = fmt(rate);
    document.getElementById('btkl_hours_display').value = parseFloat(hours.toFixed(4)).toString();
    document.getElementById('btkl_total_display').value = fmt(total);
    document.getElementById('btkl_rate_per_hour').value = rate;
    document.getElementById('total_btkl_cost').value = total;
    calcGrand();
}

function calcBOP() {
    const hours = parseFloat(document.getElementById('production_time_hours_raw').value) || parseNum(document.getElementById('production_time_hours').value);
    const overhead = BOP_RATE * hours;
    document.getElementById('bop_rate_display').value = fmt(BOP_RATE);
    document.getElementById('bop_hours_display').value = parseFloat(hours.toFixed(4)).toString();
    document.getElementById('bop_overhead_display').value = fmt(overhead);
    document.getElementById('bop_total_display').textContent = fmt(overhead);
    document.getElementById('bop_overhead_cost').value = overhead;
    document.getElementById('total_bop_cost').value = overhead;
    calcGrand();
}

function calcGrand() {
    const matEl = document.getElementById('total-material');
    const mat  = parseFloat(matEl.dataset.raw || '0') || parseDisplay(matEl.textContent);
    const btkl = parseFloat(document.getElementById('total_btkl_cost').value) || 0;
    const bop  = parseFloat(document.getElementById('total_bop_cost').value) || 0;
    document.getElementById('grand-total').textContent = fmt(mat + btkl + bop);
}

function attachRowListeners() {
    document.querySelectorAll('.raw-select').forEach(s => {
        s.onchange = function() {
            const row = this.closest('.item-row');
            const opt = this.options[this.selectedIndex];
            row.querySelector('.unit').value = opt.dataset.unit || '';
            row.querySelector('.cost').value = opt.dataset.conversionPrice || '';
            calcMaterial();
        };
    });
    document.querySelectorAll('.aux-select').forEach(s => {
        s.onchange = function() {
            const row = this.closest('.auxiliary-row');
            const opt = this.options[this.selectedIndex];
            row.querySelector('.aux-unit').value = opt.dataset.unit || '';
        };
    });
    document.querySelectorAll('.qty,.cost').forEach(i => i.oninput = calcMaterial);
    document.querySelectorAll('.aux-qty').forEach(i => i.oninput = calcAux);
}

document.getElementById('product-select').addEventListener('change', function() {
    document.getElementById('bom-name').value = 'BOM ' + this.options[this.selectedIndex].text;
    const price = parseFloat(this.options[this.selectedIndex].dataset.price) || 0;
    if (price > 0) {
        document.getElementById('selling_price').value = Math.round(price);
        document.getElementById('selling_price_display').value = Math.round(price).toLocaleString('id-ID');
    }
});

document.getElementById('production_time_minutes').addEventListener('input', function() {
    const h = (parseFloat(this.value)||0) / 60;
    document.getElementById('production_time_hours').value = parseFloat(h.toFixed(4)).toString();
    document.getElementById('production_time_hours_raw').value = h;
    calcBTKL(); calcBOP();
});

document.getElementById('add-item').addEventListener('click', function() {
    const opts = buildOptions(rawMaterialsData, null);
    const row = document.createElement('div');
    row.className = 'grid grid-cols-12 gap-2 item-row border p-2 rounded';
    row.innerHTML = `<div class="col-span-4"><select name="items[${itemCount}][raw_material_id]" class="raw-select w-full border-gray-300 rounded text-sm"><option value="">-- Pilih Bahan --</option>${opts}</select></div><div class="col-span-2"><input type="text" name="items[${itemCount}][quantity]" class="qty w-full border-gray-300 rounded text-sm" placeholder="Qty"></div><div class="col-span-2"><input type="text" name="items[${itemCount}][unit]" class="unit w-full bg-gray-100 border-gray-200 rounded text-sm" readonly></div><div class="col-span-2"><input type="text" name="items[${itemCount}][unit_cost]" class="cost w-full border-gray-300 rounded text-sm" placeholder="Rp"></div><div class="col-span-1"><input type="text" class="row-total w-full bg-gray-100 border-gray-200 rounded text-sm" readonly></div><div class="col-span-1 flex items-center"><button type="button" class="remove-item text-red-500 text-xs">Hapus</button></div>`;
    document.getElementById('items-container').appendChild(row);
    itemCount++;
    attachRowListeners();
});

document.getElementById('add-auxiliary').addEventListener('click', function() {
    const opts = buildOptions(auxMaterialsData, null);
    const row = document.createElement('div');
    row.className = 'grid grid-cols-12 gap-2 auxiliary-row border p-2 rounded';
    row.innerHTML = `<div class="col-span-5"><select name="auxiliaries[${auxCount}][auxiliary_material_id]" class="aux-select w-full border-gray-300 rounded text-sm"><option value="">-- Pilih Bahan --</option>${opts}</select></div><div class="col-span-3"><input type="text" name="auxiliaries[${auxCount}][quantity]" class="aux-qty w-full border-gray-300 rounded text-sm" placeholder="Qty"></div><div class="col-span-3"><input type="text" name="auxiliaries[${auxCount}][unit]" class="aux-unit w-full bg-gray-100 border-gray-200 rounded text-sm" readonly></div><div class="col-span-1 flex items-center"><button type="button" class="remove-auxiliary text-red-500 text-xs">Hapus</button></div>`;
    document.getElementById('auxiliaries-container').appendChild(row);
    auxCount++;
    attachRowListeners();
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) { e.target.closest('.item-row').remove(); calcMaterial(); }
    if (e.target.classList.contains('remove-auxiliary')) { e.target.closest('.auxiliary-row').remove(); calcAux(); }
});

// Init
const initMin = parseFloat(document.getElementById('production_time_minutes').value) || 0;
const initHours = initMin / 60;
document.getElementById('production_time_hours').value = parseFloat(initHours.toFixed(4)).toString();
document.getElementById('production_time_hours_raw').value = initHours;
attachRowListeners();
calcMaterial();
calcAux();
calcBTKL();
calcBOP();
</script>
@endsection

