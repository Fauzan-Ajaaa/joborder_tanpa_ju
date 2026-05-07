@extends('layouts.app')

@section('title', 'Tambah Overhead POR')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Tambah Overhead POR</h1>
        <a href="{{ route('overhead-por.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Kembali</a>
    </div>

    <div class="bg-white shadow sm:rounded-lg p-6">
        <form action="{{ route('overhead-por.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="periode" class="block text-sm font-medium text-gray-700">Periode (YYYY-MM)</label>
                <input
                    type="month"
                    name="periode"
                    id="periode"
                    value="{{ old('periode', $selectedPeriod ?? now()->format('Y-m')) }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                    onchange="if (this.value) { window.location = '{{ route('overhead-por.create') }}?periode=' + this.value; }"
                >
                @error('periode')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>


            <div>
                <label class="block text-sm font-medium text-gray-700">Dasar Alokasi</label>
                <select name="dasar_alokasi" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @foreach($dasarOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('dasar_alokasi') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('dasar_alokasi')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Total Overhead Bulanan</label>
                @if($bebanBop->count() > 0)
                <div class="mb-2 p-3 bg-blue-50 border border-blue-200 rounded text-sm text-blue-800">
                    <p class="font-medium mb-1">Dari Beban-beban kategori BOP periode {{ $selectedPeriod }}:</p>
                    @foreach($bebanBop as $b)
                        <div class="flex justify-between text-xs">
                            <span>{{ $b->jenis_biaya }}</span>
                            <span>Rp {{ number_format($b->total, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between font-semibold mt-1 pt-1 border-t border-blue-300">
                        <span>Total</span>
                        <span>Rp {{ number_format($bebanBop->sum('total'), 0, ',', '.') }}</span>
                    </div>
                </div>
                @endif
                <input
                    type="text"
                    name="total_overhead_bulanan"
                    value="{{ $defaultTotal !== null ? $defaultTotal : old('total_overhead_bulanan') }}"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm currency-input"
                    placeholder="Rp 0"
                >
                @error('total_overhead_bulanan')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Total Jam Dasar Alokasi</label>
                <input type="number" step="0.01" name="total_jam_dasar_alokasi" value="{{ old('total_jam_dasar_alokasi', $defaultJamDasarAlokasi ?? null) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('total_jam_dasar_alokasi')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currencyInputs = document.querySelectorAll('.currency-input');
    
    // Format number with Rupiah format
    function formatRupiah(num) {
        if (!num) return '';
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }
    
    // Remove formatting
    function unformatRupiah(str) {
        return str.replace(/\./g, '').replace(/[^\d]/g, '');
    }
    
    currencyInputs.forEach(input => {
        // Initialize with formatted value
        if (input.value) {
            const numValue = unformatRupiah(input.value);
            input.value = formatRupiah(numValue);
        }
        
        // Format on input
        input.addEventListener('input', function(e) {
            let value = unformatRupiah(e.target.value);
            e.target.value = formatRupiah(value);
        });
        
        // Store unformatted value before submit
        input.closest('form').addEventListener('submit', function() {
            input.value = unformatRupiah(input.value);
        });
    });
});
</script>
@endpush
