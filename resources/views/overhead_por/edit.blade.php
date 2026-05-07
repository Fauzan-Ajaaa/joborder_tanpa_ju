@extends('layouts.app')

@section('title', 'Edit Overhead POR')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Edit Overhead POR</h1>
        <a href="{{ route('overhead-por.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Kembali</a>
    </div>

    <div class="bg-white shadow sm:rounded-lg p-6">
        <form action="{{ route('overhead-por.update', ['overhead_por' => $por->id]) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Periode (YYYY-MM)</label>
                <input type="text" name="periode" value="{{ old('periode', $por->periode) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="2025-12">
                @error('periode')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Dasar Alokasi</label>
                <select name="dasar_alokasi" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @foreach($dasarOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('dasar_alokasi', $por->dasar_alokasi) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('dasar_alokasi')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Total Overhead Bulanan</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                    <input type="text" id="total_overhead_display" class="block w-full pl-10 border-gray-300 rounded-md shadow-sm" placeholder="0">
                    <input type="hidden" name="total_overhead_bulanan" id="total_overhead_bulanan" value="{{ old('total_overhead_bulanan', $por->total_overhead_bulanan) }}">
                </div>
                @error('total_overhead_bulanan')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Total Jam Dasar Alokasi</label>
                <input type="number" step="0.01" name="total_jam_dasar_alokasi" value="{{ old('total_jam_dasar_alokasi', $por->total_jam_dasar_alokasi) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('total_jam_dasar_alokasi')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4">
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
document.addEventListener('DOMContentLoaded', function() {
    const displayInput = document.getElementById('total_overhead_display');
    const hiddenInput = document.getElementById('total_overhead_bulanan');
    
    // Format number with Rupiah format
    function formatRupiah(num) {
        if (!num) return '';
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }
    
    // Remove formatting
    function unformatRupiah(str) {
        return str.replace(/\./g, '').replace(/[^\d]/g, '');
    }
    
    // Initialize display with formatted value
    if (hiddenInput.value) {
        displayInput.value = formatRupiah(hiddenInput.value);
    }
    
    // Update hidden input when display input changes
    displayInput.addEventListener('input', function(e) {
        let value = unformatRupiah(e.target.value);
        hiddenInput.value = value;
        e.target.value = formatRupiah(value);
    });
    
    // Format on blur
    displayInput.addEventListener('blur', function(e) {
        if (hiddenInput.value) {
            e.target.value = formatRupiah(hiddenInput.value);
        }
    });
});
</script>
@endpush
