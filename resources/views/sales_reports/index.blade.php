@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Laporan Penjualan</h1>
            </div>
        </div>
        
        <form action="{{ route('sales_reports.report') }}" method="GET" class="p-6 space-y-6">
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
                    <label for="customer_id" class="block text-sm font-medium text-gray-700">Customer (Opsional)</label>
                    <select name="customer_id" id="customer_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Customer</option>
                        @foreach(App\Models\Customer::orderBy('name')->get() as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700">Produk (Opsional)</label>
                    <select name="product_id" id="product_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Produk</option>
                        @foreach(App\Models\Product::orderBy('name')->get() as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-search mr-2"></i>Tampilkan Laporan
                </button>
                <button type="submit" formaction="{{ route('sales_reports.export_pdf') }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-file-pdf mr-2"></i>Export PDF
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterType = document.getElementById('filter_type');
    const monthField = document.getElementById('month_field');
    const customDateFields = document.getElementById('custom_date_fields');
    
    function toggleFields() {
        if (filterType.value === 'monthly') {
            monthField.classList.remove('hidden');
            customDateFields.classList.add('hidden');
        } else if (filterType.value === 'yearly') {
            monthField.classList.add('hidden');
            customDateFields.classList.add('hidden');
        } else if (filterType.value === 'custom') {
            monthField.classList.add('hidden');
            customDateFields.classList.remove('hidden');
        }
    }
    
    filterType.addEventListener('change', toggleFields);
    toggleFields(); // Initialize
    
    // Set default month to current month
    document.getElementById('month').value = new Date().getMonth() + 1;
});
</script>
@endpush
