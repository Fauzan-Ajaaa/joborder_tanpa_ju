@extends('layouts.app')

@section('title', 'Detail Job Order')

@section('content')
<div class="space-y-6">
    {{-- Alert untuk stok bahan baku tidak cukup --}}
    @if(session('show_purchase_link'))
        <div class="bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-sm text-red-700">
                        {!! session('error') !!}
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('purchases.create') }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Beli Bahan Baku Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Job {{ $job->kode_job }}</h1>
            <p class="text-sm text-gray-500">
                Produk: 
                @if($job->jobOrderDetails->count() > 0)
                    @foreach($job->jobOrderDetails as $detail)
                        {{ $detail->product->code ?? '-' }} - {{ $detail->product->name ?? '-' }}{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                @elseif($job->product)
                    {{ $job->product->code ?? '-' }} - {{ $job->product->name ?? '-' }}
                @else
                    -
                @endif
            </p>
        </div>

        <div class="space-x-2">
            <a href="{{ route('job-orders.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Kembali</a>

            {{-- Tombol Buat Penjualan hanya untuk job internal (bukan dari katalog/customer) --}}
            @if($job->status === 'completed' && ! $job->salesTransactions && is_null($job->payment_method))
                <a href="{{ route('sales.create', ['job_order_id' => $job->id]) }}" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Buat Penjualan
                </a>
            @endif

            @if(! $job->selesai_job_at)
                @if(! $job->mulai_job_at)
                    {{-- Job belum dimulai --}}
                    <form action="{{ url('job-orders/'.$job->id.'/start') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Mulai Job
                        </button>
                    </form>
                @else
                    {{-- Job sudah mulai tapi belum selesai --}}
                    <form action="{{ url('job-orders/'.$job->id.'/finish') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150" onclick="return confirm('Tandai job ini sebagai selesai?')">
                            Job Selesai
                        </button>
                    </form>
                    
                    {{-- Tombol Produk Batal - muncul saat job sedang berjalan --}}
                    <a href="{{ route('product-cancellations.create', ['job_order_id' => $job->id]) }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        Pengurangan Produk
                    </a>
                @endif
            @else
                {{-- Job sudah selesai --}}
                <span class="text-gray-500 text-sm">Job Selesai</span>
                
                {{-- Tombol Produk Batal - juga muncul saat job sudah selesai --}}
                <a href="{{ route('product-cancellations.create', ['job_order_id' => $job->id]) }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    Produk Batal
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white shadow sm:rounded-lg p-6 space-y-2">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Info Job</h2>
            <p><span class="font-medium">Status:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    @if($job->status === 'completed') bg-green-100 text-green-800
                    @elseif($job->status === 'in_progress') bg-yellow-100 text-yellow-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                </span>
            </p>
            <p><span class="font-medium">Nama Customer:</span> 
                @if($job->customer_id && $job->customer)
                    <a href="{{ route('customers.show', $job->customer->id) }}" class="text-blue-600 hover:text-blue-800">{{ $job->customer->name }}</a>
                @else
                    {{ $job->customer_name ?? '-' }}
                @endif
            </p>
            @if($job->customer_phone)
                <p><span class="font-medium">Telepon:</span> {{ $job->customer_phone }}</p>
            @endif
            @if($job->customer_address)
                <p><span class="font-medium">Alamat:</span> {{ $job->customer_address }}</p>
            @endif
            <p><span class="font-medium">Tanggal Order:</span> {{ optional($job->order_date)->format('d/m/Y') }}</p>
            <p><span class="font-medium">Durasi:</span>
                @php
                    // Durasi = durasi per unit (dari BOM) × total quantity produk yang dipesan
                    $totalQuantity = $job->jobOrderDetails->sum('quantity');
                    $bomProcInfo = \App\Models\BillOfMaterialProcess::whereHas('billOfMaterial', fn($q) => $q->where('product_id', $job->jobOrderDetails->first()?->product_id ?? $job->product_id))->first();
                    $durasiPerUnit = $bomProcInfo ? (float)$bomProcInfo->duration_minutes : 0;
                    $durasiMenitDisplay = $durasiPerUnit * $totalQuantity;
                @endphp
                @if($durasiMenitDisplay > 0)
                    {{ rtrim(rtrim(number_format($durasiMenitDisplay, 4, '.', ''), '0'), '.') }} menit ({{ rtrim(rtrim(number_format($durasiMenitDisplay / 60, 4, '.', ''), '0'), '.') }} jam)
                @else
                    -
                @endif
            </p>
            <p><span class="font-medium">Penanggung Jawab:</span>
                {{ $job->labors->first()?->employee?->name ?? '-' }}
            </p>
        </div>

        <div class="bg-white shadow sm:rounded-lg p-6 space-y-2">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Ringkasan Biaya</h2>
            @php
                $liveBbb = $job->materials->sum(fn($m) => (float)$m->qty_total * (float)$m->harga_per_unit);
                $displayBbb = $liveBbb > 0 ? $liveBbb : (float)($job->total_bbb ?? 0);

                // Durasi = durasi per unit × total quantity
                $totalQuantityRing = $job->jobOrderDetails->sum('quantity');
                $bomProcRing = \App\Models\BillOfMaterialProcess::whereHas('billOfMaterial', function($q) use ($job) {
                    $q->where('product_id', $job->jobOrderDetails->first()?->product_id ?? $job->product_id);
                })->first();
                $durasiPerUnitRing = $bomProcRing ? (float)$bomProcRing->duration_minutes : 0;
                $durasiMenitRing = $durasiPerUnitRing * $totalQuantityRing;
                $durasiJam = $durasiMenitRing > 0 ? $durasiMenitRing / 60 : 0;

                $displayBtkl = round($durasiJam * $btklRatePerHour, 2);

                // BOP live: por_per_jam × durasi_jam (TANPA bahan penolong)
                $porRate = $porBulanIni ? (float)$porBulanIni->por_per_jam : 0;
                $overheadBop = $porRate * $durasiJam;

                $displayBop = round($overheadBop, 2);
                // HPP dihitung dari nilai mentah (sebelum round) agar tidak ada selisih rounding
                $displayHpp = round($displayBbb + ($durasiJam * $btklRatePerHour) + $overheadBop, 2);
            @endphp
            <p><span class="font-medium">BBB (Bahan Baku):</span>
                @if($displayBbb > 0)
                    Rp {{ number_format($displayBbb, 0, ',', '.') }}
                @else
                    -
                @endif
            </p>
            <p><span class="font-medium">BTKL (Tenaga Kerja Langsung):</span>
                @if($displayBtkl > 0)
                    Rp {{ number_format($displayBtkl, 0, ',', '.') }}
                @else
                    -
                @endif
            </p>
            <p><span class="font-medium">BOP (Overhead Pabrik):</span>
                Rp {{ number_format($displayBop, 0, ',', '.') }}
            </p>
            <p class="mt-2 border-t pt-2"><span class="font-semibold">Total HPP Job:</span>
                Rp {{ number_format($displayHpp, 0, ',', '.') }}
            </p>
        </div>
    </div>

    <!-- Produk Section -->
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Rincian Produk</h2>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Jual</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Harga</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if($job->jobOrderDetails->count() > 0)
                    @foreach($job->jobOrderDetails as $detail)
                        @php
                            $bom = \App\Models\BillOfMaterial::with('product')->where('product_id', $detail->product_id)->first();
                            $livePrice = $bom?->getEffectiveSellingPrice() ?: ($detail->product?->price ?? 0);
                            // Untuk job selesai pakai snapshot unit_price, selainnya pakai live
                            $hargaJual = ($job->status === 'completed' && $detail->unit_price > 0)
                                ? $detail->unit_price
                                : ($livePrice > 0 ? $livePrice : $detail->unit_price);
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $detail->product->code ?? '-' }} - {{ $detail->product->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ (int) $detail->quantity }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp {{ number_format($hargaJual, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp {{ number_format($hargaJual * $detail->quantity, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                @elseif($job->product && ($job->quantity || $job->product_id))
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $job->product->code ?? '-' }} - {{ $job->product->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ (int) ($job->quantity ?? 1) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            Rp {{ number_format($job->product->price ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            Rp {{ number_format(($job->product->price ?? 0) * ($job->quantity ?? 1), 0, ',', '.') }}
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data produk</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- BBB Section -->
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Rincian Bahan Baku (BBB)</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bahan Baku</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga / Unit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Biaya</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($job->materials as $item)
                    @php
                        $rawId = $item->raw_material_id ?? $item->bahan_baku_id ?? null;
                        $usedInProducts = $rawId && isset($materialProductMap[$rawId]) ? $materialProductMap[$rawId] : [];
                        // Pakai harga FIFO batch terdepan saat ini
                        $hargaPerUnit = (float) ($item->harga_per_unit ?? 0);
                        if ($hargaPerUnit <= 0 && $item->rawMaterial) {
                            $hargaPerUnit = $item->rawMaterial->getCurrentFifoUnitPrice($item->unit ?: $item->rawMaterial->unit);
                        }
                        $totalBiaya = (float)($item->qty_total ?? 0) * $hargaPerUnit;
                    @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div>{{ $item->rawMaterial->name ?? '-' }}</div>
                            @if(!empty($usedInProducts))
                                <div class="text-xs text-gray-500 mt-1">
                                    Digunakan untuk:
                                    @foreach($usedInProducts as $idx => $label)
                                        {{ $label }}@if(!$loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format((float) $item->qty_total, 2, ',', '.') }}
                            <span class="text-xs text-gray-500">{{ $item->unit ?? $item->rawMaterial->unit }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($hargaPerUnit, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data bahan baku untuk job ini</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Bahan penolong (ketiga) -->
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Bahan Penolong</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bahan Penolong</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $hasAux = false;
                @endphp
                @foreach($job->jobOrderDetails as $detail)
                    @php
                        $product = $detail->product;
                        $bomForAux = \App\Models\BillOfMaterial::with('auxiliaries.auxiliaryMaterial')
                            ->where('product_id', $detail->product_id)->first();
                    @endphp
                    @if($bomForAux && $bomForAux->auxiliaries->count() > 0)
                        @foreach($bomForAux->auxiliaries as $aux)
                            @php
                                $hasAux = true;
                                $qtyPerUnit = (float)($aux->quantity ?? 0);
                                $qtyTotal   = $qtyPerUnit * (float)($detail->quantity ?? 0);
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $product->code ?? '-' }} - {{ $product->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $aux->auxiliaryMaterial?->name ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ rtrim(rtrim(number_format($qtyTotal, 2, '.', ''), '0'), '.') }}
                                    <span class="text-xs text-gray-500">{{ strtoupper($aux->unit ?? '') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
                @unless($hasAux)
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada data bahan penolong untuk job ini</td>
                    </tr>
                @endunless
            </tbody>
        </table>
    </div>

    <div class="bg-white shadow sm:rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Tenaga Kerja Langsung (BTKL)</h2>
        @php
            // Durasi = durasi per unit × total quantity
            $totalQuantityBtkl = $job->jobOrderDetails->sum('quantity');
            $bomProcBtkl = \App\Models\BillOfMaterialProcess::whereHas('billOfMaterial', fn($q) => $q->where('product_id', $job->jobOrderDetails->first()?->product_id ?? $job->product_id))->first();
            $durasiPerUnitBtkl = $bomProcBtkl ? (float)$bomProcBtkl->duration_minutes : 0;
            $durasiMenitJob = $durasiPerUnitBtkl * $totalQuantityBtkl;
            $durasiJamJob = $durasiMenitJob > 0 ? $durasiMenitJob / 60 : 0;
            $totalBtkl = (float) ($job->total_btkl ?? 0);
            $liveBtkl  = round($durasiJamJob * $btklRatePerHour, 2); // Selalu hitung live dari durasi × tarif
        @endphp
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Karyawan BTKL</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi (jam)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tarif / Jam (BTKL)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total BTKL</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $job->labors->first()?->employee?->name ?? 'Semua Karyawan BTKL' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $durasiJamJob > 0 ? rtrim(rtrim(number_format($durasiJamJob, 4, '.', ''), '0'), '.') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        Rp {{ number_format($btklRatePerHour, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $liveBtkl > 0 ? 'Rp ' . number_format($liveBtkl, 0, ',', '.') : '-' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Overhead Pabrik (BOP)</h2>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Komponen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi (jam)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">POR / Jam</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total BOP</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    // Durasi = durasi per unit × total quantity
                    $totalQuantityBop = $job->jobOrderDetails->sum('quantity');
                    $bomProcBop = \App\Models\BillOfMaterialProcess::whereHas('billOfMaterial', fn($q) => $q->where('product_id', $job->jobOrderDetails->first()?->product_id ?? $job->product_id))->first();
                    $durasiPerUnitBop = $bomProcBop ? (float)$bomProcBop->duration_minutes : 0;
                    $durasiMenit2 = $durasiPerUnitBop * $totalQuantityBop;
                    $durasiJam = $durasiMenit2 > 0 ? $durasiMenit2 / 60 : 0;
                    $porPerJam  = (float) ($porBulanIni->por_per_jam ?? 0);
                    $bopFromPor = round($porPerJam * $durasiJam, 2);

                    $totalBopDisplay = $bopFromPor;
                @endphp

                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        Overhead Pabrik
                        @if($porBulanIni)
                            <div class="text-xs text-gray-500">{{ $porBulanIni->periode }} · {{ ucfirst(str_replace('_', ' ', $porBulanIni->dasar_alokasi ?? '')) }}</div>
                        @else
                            <div class="text-xs text-red-400">Belum ada data POR bulan ini</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ rtrim(rtrim(number_format($durasiJam, 4, '.', ''), '0'), '.') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($porPerJam, 0, ',', '.') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp {{ number_format($bopFromPor, 0, ',', '.') }}</td>
                </tr>

                <tr class="bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900" colspan="3">Total BOP</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                        Rp {{ number_format($totalBopDisplay, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

