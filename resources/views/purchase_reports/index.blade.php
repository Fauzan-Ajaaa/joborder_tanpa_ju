@extends('layouts.app')

@section('title', 'Laporan Pembelian')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Laporan Pembelian</h1>
            </div>
        </div>
        
        <form action="{{ route('purchase_reports.report') }}" method="GET" class="p-6 space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Filter Type -->
                <div>
                    <label for="filter_type" class="block text-sm font-medium text-gray-700">Tipe Filter</label>
                    <select name="filter_type" id="filter_type" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="monthly">Bulanan</option>
                        <option value="yearly">Tahunan</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                <!-- Month (for monthly filter) -->
                <div id="month_field">
                    <label for="month" class="block text-sm font-medium text-gray-700">Bulan</label>
                    <select name="month" id="month"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>

                <!-- Year -->
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700">Tahun</label>
                    <select name="year" id="year"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @for($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <!-- Custom Date Range (hidden by default) -->
            <div id="custom_date_fields" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                        <input type="date" name="start_date" id="start_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                        <input type="date" name="end_date" id="end_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Additional Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700">Supplier (Opsional)</label>
                    <select name="supplier_id" id="supplier_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Supplier</option>
                        @foreach(App\Models\Supplier::orderBy('name')->get() as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="material_id" class="block text-sm font-medium text-gray-700">Bahan Baku / Penolong (Opsional)</label>
                    <select name="material_id" id="material_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Bahan</option>
                        <optgroup label="Bahan Baku">
                            @foreach(App\Models\RawMaterial::orderBy('name')->get() as $rawMaterial)
                                <option value="raw_{{ $rawMaterial->id }}">{{ $rawMaterial->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Bahan Penolong">
                            @foreach(App\Models\AuxiliaryMaterial::orderBy('name')->get() as $auxMaterial)
                                <option value="aux_{{ $auxMaterial->id }}">{{ $auxMaterial->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Tampilkan Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('filter_type').addEventListener('change', function() {
    const monthField = document.getElementById('month_field');
    const customDateFields = document.getElementById('custom_date_fields');
    
    if (this.value === 'monthly') {
        monthField.style.display = 'block';
        customDateFields.classList.add('hidden');
    } else if (this.value === 'custom') {
        monthField.style.display = 'none';
        customDateFields.classList.remove('hidden');
    } else {
        monthField.style.display = 'none';
        customDateFields.classList.add('hidden');
    }
});

// Initialize on page load
document.getElementById('filter_type').dispatchEvent(new Event('change'));
</script>
@endsection
