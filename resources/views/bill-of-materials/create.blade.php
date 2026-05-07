@extends('layouts.app')

@section('title', 'Create Bill of Material')

@section('content')
<div class="space-y-6">

<div class="max-w-7xl mx-auto py-6">

<div class="mb-6">
    <a href="{{ route('bill-of-materials.index') }}" class="text-blue-600 hover:text-blue-800">
        ← Kembali ke Daftar BOM
    </a>
</div>

<!-- Error/Success Messages -->
@if($errors->any())
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
        {{ session('error') }}
    </div>
@endif

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('bill-of-materials.store') }}" method="POST">
@csrf


<!-- ================================================= -->
<!-- SECTION 1 : PRODUK -->
<!-- ================================================= -->

<div class="bg-white shadow sm:rounded-lg p-6 space-y-4">

<h2 class="text-lg font-semibold text-gray-800 border-b pb-2">
Produk
</h2>

<div class="grid grid-cols-3 gap-4">

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Produk</label>
<select name="product_id" id="product-select"
class="w-full border-gray-300 rounded-md shadow-sm"
required>
<option value="">-- Pilih Produk --</option>
@foreach($products as $product)
<option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->name }}</option>
@endforeach
</select>
</div>

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Nama BOM</label>
<input type="text" name="name" id="bom-name" class="w-full border-gray-300 rounded-md shadow-sm" readonly>
</div>

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual</label>
<div class="relative">
    <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-500 text-sm pointer-events-none">Rp.</span>
    <input type="text" id="selling_price_display"
        class="w-full pl-8 border-gray-300 rounded-md shadow-sm"
        placeholder="0" oninput="fmtRp(this,'selling_price')">
</div>
<input type="hidden" name="selling_price" id="selling_price" value="">
</div>

</div>

<div class="grid grid-cols-2 gap-4">

<div>
<label class="block text-sm font-medium text-gray-700 mb-1">
Waktu Produksi (menit)
</label>

<input type="number"
id="production_time_minutes"
name="production_time_minutes"
class="w-full border-gray-300 rounded-md shadow-sm"
step="0.1" min="0"
required>
</div>


<div>
<label class="block text-sm font-medium text-gray-700 mb-1">
Waktu Produksi (jam)
</label>

<input type="text"
id="production_time_hours"
class="w-full border-gray-200 bg-gray-100 rounded-md"
readonly>
<input type="hidden" id="production_time_hours_raw">
</div>

</div>

</div>



<!-- ================================================= -->
<!-- SECTION 2 : BAHAN BAKU -->
<!-- ================================================= -->

<div class="bg-white shadow sm:rounded-lg p-6">

<div class="flex justify-between mb-4">

<h2 class="text-lg font-semibold text-gray-800">
Bahan Baku
</h2>

<button type="button"
id="add-item"
class="px-3 py-1 bg-green-600 text-white text-sm rounded">
Tambah
</button>

</div>


<div id="items-container" class="space-y-2">

<div class="grid grid-cols-12 gap-2 item-row border p-2 rounded">

<div class="col-span-4">

<select name="items[0][raw_material_id]"
class="raw-select w-full border-gray-300 rounded text-sm">

<option value="">-- Pilih Bahan --</option>

@foreach($rawMaterials as $material)
<option value="{{ $material->id }}"
data-unit="{{ $material->display_unit }}"
data-conversion-price="{{ round($material->display_price) }}">
{{ $material->name }}
</option>
@endforeach

</select>

</div>

<div class="col-span-2">
<input type="text"
name="items[0][quantity]"
class="qty w-full border-gray-300 rounded text-sm"
placeholder="Qty"
step="0.1"
required>
</div>

<div class="col-span-2">
<input type="text"
name="items[0][unit]"
class="unit w-full bg-gray-100 border-gray-200 rounded text-sm"
readonly>
</div>

<div class="col-span-2">
<input type="text"
name="items[0][unit_cost]"
class="cost w-full border-gray-300 rounded text-sm"
placeholder="Rp">
</div>

<div class="col-span-1">
<input type="text"
class="row-total w-full bg-gray-100 border-gray-200 rounded text-sm"
readonly>
</div>

<div class="col-span-1 flex items-center">
<button type="button" class="remove-item text-red-500 text-xs hover:text-red-700">Hapus</button>
</div>

</div>

</div>

<div class="text-right mt-3">
<span class="font-semibold">Total Bahan Baku:</span>
<span id="total-material">Rp 0</span>
</div>

</div>



<!-- ================================================= -->
<!-- SECTION 3 : BAHAN PENOLONG -->
<!-- ================================================= -->

<div class="bg-white shadow sm:rounded-lg p-6">

<div class="flex justify-between mb-4">

<h2 class="text-lg font-semibold text-gray-800">
Bahan Penolong
</h2>

<button type="button"
id="add-auxiliary"
class="px-3 py-1 bg-green-600 text-white text-sm rounded">
Tambah
</button>

</div>


<div id="auxiliaries-container" class="space-y-2">

<div class="grid grid-cols-12 gap-2 auxiliary-row border p-2 rounded">

<div class="col-span-5">

<select name="auxiliaries[0][auxiliary_material_id]"
class="aux-select w-full border-gray-300 rounded text-sm">

<option value="">-- Pilih Bahan --</option>

@foreach($auxiliaryMaterials as $material)
<option value="{{ $material->id }}"
data-unit="{{ $material->display_unit }}">
{{ $material->name }}
</option>
@endforeach

</select>

</div>

<div class="col-span-3">
<input type="text"
name="auxiliaries[0][quantity]"
class="aux-qty w-full border-gray-300 rounded text-sm"
placeholder="Qty"
step="0.1">
</div>

<div class="col-span-3">
<input type="text"
name="auxiliaries[0][unit]"
class="aux-unit w-full bg-gray-100 border-gray-200 rounded text-sm"
readonly>
</div>

<div class="col-span-1 flex items-center">
<button type="button" class="remove-auxiliary text-red-500 text-xs hover:text-red-700">Hapus</button>
</div>

</div>

</div>


</div>



<!-- ================================================= -->
<!-- SECTION 4 : BTKL (BIAYA TENAGA KERJA LANGSUNG) -->
<!-- ================================================= -->

<div class="bg-white shadow sm:rounded-lg p-6">

<h2 class="text-lg font-semibold mb-4">
Biaya Tenaga Kerja Langsung (BTKL)
</h2>

<!-- BTKL Calculation Table -->
<div class="overflow-x-auto">
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-100">
                <th class="border border-gray-300 px-4 py-2 text-left text-sm font-medium">Tarif per Jam</th>
                <th class="border border-gray-300 px-4 py-2 text-left text-sm font-medium">Durasi (Jam)</th>
                <th class="border border-gray-300 px-4 py-2 text-left text-sm font-medium">Hasil Kali</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border border-gray-300 px-4 py-2">
                    <input type="text"
                        id="btkl_rate_per_hour_display"
                        value="Rp {{ number_format($btklRatePerHour ?? 0, 0, ',', '.') }}"
                        class="w-full bg-gray-100 border-0 text-sm"
                        readonly>
                </td>
                <td class="border border-gray-300 px-4 py-2">
                    <input type="text"
                        id="production_time_hours_display"
                        class="w-full bg-gray-100 border-0 text-sm"
                        readonly>
                </td>
                <td class="border border-gray-300 px-4 py-2">
                    <input type="text"
                        id="total_btkl_cost_display"
                        class="w-full bg-gray-100 border-0 text-sm font-semibold"
                        readonly>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Hidden fields for form submission -->
<input type="hidden" id="btkl_rate_per_hour" name="btkl_rate_per_hour">
<input type="hidden" id="total_btkl_cost" name="total_btkl_cost">

</div>



<!-- ================================================= -->
<!-- SECTION 5 : BOP -->
<!-- ================================================= -->

<div class="bg-white shadow sm:rounded-lg p-6">

<h2 class="text-lg font-semibold mb-4">
Biaya Overhead Produksi (BOP)
</h2>

@if($currentPOR && $currentPOR->periode !== $currentPeriod)
<div class="mb-3 p-3 bg-yellow-50 border border-yellow-300 rounded text-sm text-yellow-800">
    ⚠️ POR bulan ini ({{ $currentPeriod }}) belum diinput. Tarif BOP = Rp 0. Silakan input POR bulan ini terlebih dahulu.
</div>
@elseif(!$currentPOR)
<div class="mb-3 p-3 bg-red-50 border border-red-300 rounded text-sm text-red-800">
    ⚠️ Belum ada data POR. Tarif BOP = Rp 0. Silakan input data POR terlebih dahulu.
</div>
@endif

<!-- BOP Calculation Table -->
<div class="overflow-x-auto">
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-100">
                <th class="border border-gray-300 px-4 py-2 text-left text-sm font-medium">Tarif per Jam</th>
                <th class="border border-gray-300 px-4 py-2 text-left text-sm font-medium">Durasi (Jam)</th>
                <th class="border border-gray-300 px-4 py-2 text-left text-sm font-medium">Hasil Kali</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="border border-gray-300 px-4 py-2">
                    <input type="text"
                        id="bop_rate_per_hour_display"
                        value="Rp {{ number_format($currentPOR ? $currentPOR->por_per_jam : 0, 0, ',', '.') }}"
                        class="w-full bg-gray-100 border-0 text-sm"
                        readonly>
                </td>
                <td class="border border-gray-300 px-4 py-2">
                    <input type="text"
                        id="production_time_hours_display_bop"
                        class="w-full bg-gray-100 border-0 text-sm"
                        readonly>
                </td>
                <td class="border border-gray-300 px-4 py-2">
                    <input type="text"
                        id="bop_overhead_cost_display"
                        class="w-full bg-gray-100 border-0 text-sm"
                        readonly>
                </td>
            </tr>
        </tbody>
    </table>
</div>


<!-- Total BOP Section -->
<div class="mt-4 pt-4 border-t border-gray-200">
    <div class="flex justify-between items-center">
        <span class="text-lg font-semibold">Total BOP:</span>
        <span id="total_bop_cost_display" class="text-lg font-bold text-green-600">Rp 0</span>
    </div>
    <p class="text-xs text-gray-500 mt-1">BOP = Tarif per jam × Durasi</p>
</div>

<!-- Hidden fields for form submission -->
<input type="hidden" id="bop_rate_per_hour" name="bop_rate_per_hour" value="{{ $currentPOR ? $currentPOR->por_per_jam : 0 }}">
<input type="hidden" id="bop_overhead_cost" name="bop_overhead_cost">
<input type="hidden" id="total_bop_cost" name="total_bop_cost">

</div>



<!-- ================================================= -->
<!-- SUMMARY -->
<!-- ================================================= -->

<div class="bg-white shadow sm:rounded-lg p-6 text-center">

<h2 class="text-lg font-semibold mb-3">
Total Biaya Produksi
</h2>

<p id="grand-total"
class="text-3xl font-bold">
Rp 0
</p>

</div>



<button type="submit"
class="px-5 py-2 bg-blue-600 text-white rounded">
Simpan BOM
</button>

</form>
</div>

<script>
function fmtRp(input, hiddenId) {
    var raw = input.value.replace(/[^0-9]/g, '');
    var num = parseInt(raw || '0', 10);
    input.value = num > 0 ? num.toLocaleString('id-ID') : '';
    if (hiddenId) document.getElementById(hiddenId).value = raw || '';
}
// Auto-fill BOM name based on product selection
document.getElementById('product-select').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const productName = selectedOption.text;
    
    // Skip if "-- Pilih Produk --" is selected
    if (!this.value || productName.includes('--')) {
        document.getElementById('bom-name').value = '';
        return;
    }
    
    document.getElementById('bom-name').value = 'BOM ' + productName;
    console.log('BOM name set to:', 'BOM ' + productName);
    
    // Auto-fill harga jual dari produk, bisa diubah user
    const price = parseFloat(selectedOption.dataset.price) || 0;
    document.getElementById('selling_price').value = price > 0 ? Math.round(price) : '';
    document.getElementById('selling_price_display').value = price > 0 ? Math.round(price).toLocaleString('id-ID') : '';
});

// Data from controller
const btklRatePerHour = {{ $btklRatePerHour ?? 0 }};
const currentBOPRate = {{ $currentPOR ? $currentPOR->por_per_jam : 0 }};

let itemCount = 1;
let auxiliaryCount = 1;

// Update BOP calculations (TANPA auxiliary costs - bahan penolong tidak menambah BOP)
function updateBOPCalculations() {
    const productionHours = parseFloat(document.getElementById('production_time_hours_raw').value) || parseFloat(document.getElementById('production_time_hours').value) || 0;
    const ratePerHour = currentBOPRate;
    
    // Simpan float penuh, round hanya untuk display
    const overheadCost = ratePerHour * productionHours;
    
    document.getElementById('bop_rate_per_hour_display').value = 'Rp ' + Math.round(ratePerHour).toLocaleString('id-ID');
    document.getElementById('bop_overhead_cost_display').value = 'Rp ' + Math.round(overheadCost).toLocaleString('id-ID');
    document.getElementById('total_bop_cost_display').textContent = 'Rp ' + Math.round(overheadCost).toLocaleString('id-ID');
    
    // Simpan float penuh ke hidden field
    document.getElementById('bop_rate_per_hour').value = ratePerHour;
    document.getElementById('bop_overhead_cost').value = overheadCost;
    document.getElementById('total_bop_cost').value = overheadCost;
    
    updateGrandTotal();
}

// Convert minutes to hours
document.getElementById('production_time_minutes').addEventListener('input', function() {
    const minutes = parseFloat(this.value) || 0;
    const hours = minutes / 60;
    const hoursStr = parseFloat(hours.toFixed(4)).toString();
    document.getElementById('production_time_hours').value = hoursStr;
    document.getElementById('production_time_hours_raw').value = hours;
    document.getElementById('production_time_hours_display').value = hoursStr;
    document.getElementById('production_time_hours_display_bop').value = hoursStr;
    calculateBTKL();
    updateBOPCalculations();
    updateGrandTotal();
});

// Calculate BTKL cost
function calculateBTKL() {
    const productionHours = parseFloat(document.getElementById('production_time_hours_raw').value) || parseFloat(document.getElementById('production_time_hours').value) || 0;
    const ratePerHour = btklRatePerHour;
    // Simpan float penuh, round hanya untuk display
    const totalCost = ratePerHour * productionHours;
    
    document.getElementById('btkl_rate_per_hour_display').value = 'Rp ' + Math.round(ratePerHour).toLocaleString('id-ID');
    document.getElementById('total_btkl_cost_display').value = 'Rp ' + Math.round(totalCost).toLocaleString('id-ID');
    
    // Simpan float penuh ke hidden field
    document.getElementById('btkl_rate_per_hour').value = ratePerHour;
    document.getElementById('total_btkl_cost').value = totalCost;
    
    updateGrandTotal();
}

// Add material row
document.getElementById('add-item').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'grid grid-cols-12 gap-2 item-row border p-2 rounded';
    newRow.innerHTML = `
        <div class="col-span-4">
            <select name="items[${itemCount}][raw_material_id]"
                class="raw-select w-full border-gray-300 rounded text-sm">
                <option value="">-- Pilih Bahan --</option>
                @foreach($rawMaterials as $material)
@php
    // Get conversion data from unit conversions table
    $conversion = \App\Models\RawMaterialUnitConversion::where('raw_material_id', $material->id)
        ->where('from_unit', $material->unit)
        ->first();
    
    $conversionUnitName = $conversion ? $conversion->to_unit : $material->unit;
    $conversionUnit = $conversion ? $conversion->factor : 1;
    $conversionPrice = $conversion ? ($material->price_per_unit / $conversion->factor) : $material->price_per_unit;
@endphp
                    <option value="{{ $material->id }}"
                        data-unit="{{ $conversionUnitName }}"
                        data-cost="{{ $material->price_per_unit }}"
                        data-conversion="{{ $conversionUnit }}"
                        data-conversion-price="{{ $conversionPrice }}">
                        {{ $material->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-span-2">
            <input type="text"
                name="items[${itemCount}][quantity]"
                class="qty w-full border-gray-300 rounded text-sm"
                placeholder="Qty"
                step="0.1">
        </div>
        <div class="col-span-2">
            <input type="text"
                name="items[${itemCount}][unit]"
                class="unit w-full bg-gray-100 border-gray-200 rounded text-sm"
                readonly>
        </div>
        <div class="col-span-2">
            <input type="text"
                name="items[${itemCount}][unit_cost]"
                class="cost w-full border-gray-300 rounded text-sm"
                placeholder="Rp">
        </div>
        <div class="col-span-1">
            <input type="text"
                class="row-total w-full bg-gray-100 border-gray-200 rounded text-sm"
                readonly>
        </div>
        <div class="col-span-1 flex items-center">
            <button type="button" class="remove-item text-red-500 text-xs hover:text-red-700">Hapus</button>
        </div>
    `;
    container.appendChild(newRow);
    itemCount++;
    attachItemListeners();
});

// Add auxiliary row
document.getElementById('add-auxiliary').addEventListener('click', function() {
    const container = document.getElementById('auxiliaries-container');
    const newRow = document.createElement('div');
    newRow.className = 'grid grid-cols-12 gap-2 auxiliary-row border p-2 rounded';
    newRow.innerHTML = `
        <div class="col-span-5">
            <select name="auxiliaries[${auxiliaryCount}][auxiliary_material_id]"
                class="aux-select w-full border-gray-300 rounded text-sm">
                <option value="">-- Pilih Bahan --</option>
                @foreach($auxiliaryMaterials as $material)
                    <option value="{{ $material->id }}"
                        data-unit="{{ $material->display_unit }}">
                        {{ $material->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-span-3">
            <input type="text"
                name="auxiliaries[${auxiliaryCount}][quantity]"
                class="aux-qty w-full border-gray-300 rounded text-sm"
                placeholder="Qty"
                step="0.1">
        </div>
        <div class="col-span-3">
            <input type="text"
                name="auxiliaries[${auxiliaryCount}][unit]"
                class="aux-unit w-full bg-gray-100 border-gray-200 rounded text-sm"
                readonly>
        </div>
        <div class="col-span-1 flex items-center">
            <button type="button" class="remove-auxiliary text-red-500 text-xs hover:text-red-700">Hapus</button>
        </div>
    `;
    container.appendChild(newRow);
    auxiliaryCount++;
    attachAuxiliaryListeners();
});

// Remove items
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        e.target.closest('.item-row').remove();
        calculateMaterialTotal();
    }
    if (e.target.closest('.remove-auxiliary')) {
        e.target.closest('.auxiliary-row').remove();
        calculateAuxiliaryTotal();
    }
});

// Parse number function to handle decimal comma/dot
function parseNumber(value) {
    if (!value) return 0;
    return parseFloat(value.toString().replace(/[^\d.]/g,'')) || 0;
}
// Parse dari display text yang sudah diformat (Rp 6.621) — strip titik ribuan
function parseDisplay(value) {
    if (!value) return 0;
    return parseFloat(value.toString().replace(/[Rp\s.]/g,'').replace(',','.')) || 0;
}

// Calculate material total
function calculateMaterialTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseNumber(row.querySelector('.qty').value);
        const costText = row.querySelector('.cost').value || '';
        const cost = parseNumber(costText);
        const rowTotal = qty * cost;
        row.querySelector('.row-total').value = 'Rp ' + Math.round(rowTotal).toLocaleString('id-ID');
        total += rowTotal;
    });
    // Simpan float penuh di data attribute, display pakai round
    document.getElementById('total-material').textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
    document.getElementById('total-material').dataset.raw = total;
    updateGrandTotal();
}

// Calculate auxiliary total (DISABLED - bahan penolong tidak ada nominal)
function calculateAuxiliaryTotal() {
    // Bahan penolong hanya rincian resep, tidak ada perhitungan nominal
    // Tidak perlu update BOP karena bahan penolong tidak menambah BOP
    return;
}

// Update grand total — pakai float penuh, round hanya di display
function updateGrandTotal() {
    const matEl = document.getElementById('total-material');
    const material = parseFloat(matEl.dataset.raw || '0') || parseDisplay(matEl.textContent);
    const btkl = parseFloat(document.getElementById('total_btkl_cost').value) || 0;
    const bop  = parseFloat(document.getElementById('total_bop_cost').value)  || 0;
    const grandTotal = material + btkl + bop;
    document.getElementById('grand-total').textContent = 'Rp ' + Math.round(grandTotal).toLocaleString('id-ID');
}

// Attach listeners
function attachItemListeners() {
    document.querySelectorAll('.raw-select').forEach(select => {
        select.addEventListener('change', function() {
            const row = this.closest('.item-row');
            const option = this.options[this.selectedIndex];
            row.querySelector('.unit').value = option.dataset.unit || '';
            
            // Use conversion price if available, otherwise use regular price
            const conversionPrice = parseFloat(option.dataset.conversionPrice) || 0;
            row.querySelector('.cost').value = conversionPrice > 0 ? Math.round(conversionPrice) : '';
            calculateMaterialTotal();
        });
    });
    
    document.querySelectorAll('.qty, .cost').forEach(input => {
        input.addEventListener('input', calculateMaterialTotal);
    });
}

function attachAuxiliaryListeners() {
    document.querySelectorAll('.aux-select').forEach(select => {
        select.addEventListener('change', function() {
            const row = this.closest('.auxiliary-row');
            const option = this.options[this.selectedIndex];
            row.querySelector('.aux-unit').value = option.dataset.unit || '';
        });
    });
}

// Initial attachment and calculations
attachItemListeners();
attachAuxiliaryListeners();

// Trigger change event for pre-filled selects to populate prices
document.querySelectorAll('.raw-select').forEach(select => {
    if (select.value) {
        select.dispatchEvent(new Event('change'));
    }
});

document.querySelectorAll('.aux-select').forEach(select => {
    if (select.value) {
        select.dispatchEvent(new Event('change'));
    }
});

calculateMaterialTotal();
calculateAuxiliaryTotal();
calculateBTKL();
updateBOPCalculations(); // Initialize BOP calculations with current POR
</script>
@endsection
