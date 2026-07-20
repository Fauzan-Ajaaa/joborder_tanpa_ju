@extends('layouts.app')
@section('title', 'Detail Bill of Material')
@section('content')
<div class="space-y-6">

<div class="flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $billOfMaterial->name }}</h1>
        <p class="text-sm text-gray-500">{{ $billOfMaterial->code }} · Produk: {{ $billOfMaterial->product?->name ?? '-' }}</p>
    </div>
    <div class="flex items-center space-x-3">
        {{-- Quick status toggle --}}
        <form action="{{ route('bill-of-materials.toggle-status', $billOfMaterial) }}" method="POST" class="flex items-center space-x-2">
            @csrf
            @method('PATCH')
            <label class="text-sm font-medium text-gray-700">Status:</label>
            <select name="is_active" onchange="this.form.submit()" class="border-gray-300 rounded-md text-sm shadow-sm">
                <option value="1" {{ ($billOfMaterial->is_active ?? true) ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ !($billOfMaterial->is_active ?? true) ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </form>
        <a href="{{ route('bill-of-materials.edit', $billOfMaterial) }}"
           class="px-4 py-2 bg-yellow-500 text-white text-sm rounded-md hover:bg-yellow-600">Edit</a>
        <a href="{{ route('bill-of-materials.index') }}"
           class="px-4 py-2 border border-gray-300 text-sm rounded-md text-gray-700 hover:bg-gray-50">Kembali</a>
    </div>
</div>

@if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
@endif

{{-- Rekomendasi Harga dengan Margin --}}
<div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 shadow sm:rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">💡 Rekomendasi Harga Jual dengan Margin</h2>
    @php
        $durasiJam = ($billOfMaterial->processes->first()?->duration_minutes ?? 0) / 60;
        $totalBb   = $billOfMaterial->items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost);
        $totalBtkl = round((float)$billOfMaterial->btkl_rate_per_hour * $durasiJam, 2);
        $totalBopPor = round((float)$billOfMaterial->bop_rate_per_hour * $durasiJam, 2);
        $totalAux = $billOfMaterial->auxiliaries->sum(fn($a) => (float)($a->quantity ?? 0) * (float)($a->unit_cost ?? 0));
        $totalBop  = $totalBopPor + $totalAux;
        $hpp = $totalBb + $totalBtkl + $totalBop;
        
        // Margin options: 20%, 30%, 40%, 50%
        $marginOptions = [20, 30, 40, 50];
    @endphp
    
    <p class="text-sm text-gray-600 mb-4">Berdasarkan total biaya produksi (HPP) sebesar <span class="font-semibold text-gray-900">Rp {{ number_format($hpp, 0, ',', '.') }}</span>, berikut rekomendasi harga jual:</p>
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($marginOptions as $margin)
            @php
                $hargaRekomendasi = $hpp * (1 + $margin / 100);
                $hargaRekomendasi = ceil($hargaRekomendasi / 100) * 100; // Pembulatan ke ratusan terdekat
            @endphp
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:shadow-md transition-shadow">
                <div class="text-center">
                    <div class="text-xs font-medium text-gray-500 uppercase mb-1">Margin {{ $margin }}%</div>
                    <div class="text-lg font-bold text-indigo-600">Rp {{ number_format($hargaRekomendasi, 0, ',', '.') }}</div>
                    <div class="text-xs text-gray-500 mt-1">Keuntungan: Rp {{ number_format($hargaRekomendasi - $hpp, 0, ',', '.') }}</div>
                </div>
            </div>
        @endforeach
    </div>
    
    <div class="mt-4 p-3 bg-blue-100 border border-blue-200 rounded-md">
        <p class="text-xs text-blue-800">
            <strong>Catatan:</strong> Harga dibulatkan ke ratusan terdekat untuk kemudahan transaksi. Pertimbangkan juga harga pasar dan kompetitor sebelum menetapkan harga jual akhir.
        </p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Info --}}
    <div class="bg-white shadow sm:rounded-lg p-6 space-y-2">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Info BOM</h2>
        <p><span class="font-medium">Kode:</span> {{ $billOfMaterial->code }}</p>
        <p><span class="font-medium">Produk:</span> {{ $billOfMaterial->product?->name ?? '-' }}</p>
        <p><span class="font-medium">Deskripsi:</span> {{ $billOfMaterial->description ?? '-' }}</p>
        <p><span class="font-medium">Waktu Produksi:</span>
            {{ $billOfMaterial->processes->first()?->duration_minutes ?? 0 }} menit
            ({{ number_format(($billOfMaterial->processes->first()?->duration_minutes ?? 0) / 60, 4) }} jam)
        </p>
        <p><span class="font-medium">Status:</span>
            @if($billOfMaterial->is_active ?? true)
                <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">Aktif</span>
            @else
                <span class="px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-800">Nonaktif</span>
            @endif
        </p>
    </div>

    {{-- Ringkasan Biaya --}}
    <div class="bg-white shadow sm:rounded-lg p-6 space-y-2">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Ringkasan Biaya</h2>
        @php
            $durasiJam = ($billOfMaterial->processes->first()?->duration_minutes ?? 0) / 60;
            $totalBb   = $billOfMaterial->items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost);
            $totalBtkl = round((float)$billOfMaterial->btkl_rate_per_hour * $durasiJam, 2);
            $totalBopPor = round((float)$billOfMaterial->bop_rate_per_hour * $durasiJam, 2);
            // Hitung aux dari bom->auxiliaries (sudah tersinkron dengan product saat save)
            $totalAux = $billOfMaterial->auxiliaries->sum(fn($a) => (float)($a->quantity ?? 0) * (float)($a->unit_cost ?? 0));
            $totalBop  = $totalBopPor + $totalAux;
            $totalBiaya = $totalBb + $totalBtkl + $totalBop;
            $hargaJual = $billOfMaterial->getEffectiveSellingPrice();
            $marginActual = $hargaJual > 0 && $totalBiaya > 0 ? (($hargaJual - $totalBiaya) / $totalBiaya * 100) : 0;
        @endphp
        <p><span class="font-medium">Harga Jual:</span> Rp {{ number_format($hargaJual, 0, ',', '.') }}</p>
        <p><span class="font-medium">Total Bahan Baku:</span> Rp {{ number_format($totalBb, 0, ',', '.') }}</p>
        <p><span class="font-medium">Total BTKL:</span> Rp {{ number_format($totalBtkl, 0, ',', '.') }}</p>
        <p><span class="font-medium">Total BOP:</span> Rp {{ number_format($totalBop, 0, ',', '.') }}</p>
        <p class="mt-2 border-t pt-2"><span class="font-semibold">Total Biaya:</span> Rp {{ number_format($totalBiaya, 0, ',', '.') }}</p>
        
        @if($hargaJual > 0 && $totalBiaya > 0)
        <p class="mt-1 pt-1">
            <span class="font-medium">Margin Saat Ini:</span> 
            <span class="font-semibold {{ $marginActual > 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ number_format($marginActual, 2, ',', '.') }}%
            </span>
        </p>
        @endif
    </div>
</div>

{{-- Bahan Baku --}}
<div class="bg-white shadow sm:rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Bahan Baku</h2>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bahan</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Harga/Unit</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($billOfMaterial->items as $item)
            <tr>
                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->rawMaterial?->name ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ $item->unit }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 text-right">Rp {{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 text-right">Rp {{ number_format($item->quantity * $item->unit_cost, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-3 text-center text-sm text-gray-500">Belum ada bahan baku</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Bahan Penolong --}}
<div class="bg-white shadow sm:rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Bahan Penolong</h2>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bahan</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($billOfMaterial->auxiliaries as $aux)
            @php
                $qtyPerUnit = (float)($aux->quantity ?? 0);
                $unitPrice  = (float)($aux->unit_cost ?? 0);
                $totalCost  = $qtyPerUnit * $unitPrice;
            @endphp
            <tr>
                <td class="px-4 py-3 text-sm text-gray-900">{{ $aux->auxiliaryMaterial?->name ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ rtrim(rtrim(number_format($qtyPerUnit, 2, '.', ''), '0'), '.') }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ strtoupper($aux->unit ?? '') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-4 py-3 text-center text-sm text-gray-500">Belum ada bahan penolong</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

</div>
@endsection
