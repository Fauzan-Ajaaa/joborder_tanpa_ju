@extends('layouts.app')

@section('title', 'Kartu Stok - ' . $rawMaterial->name)

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Kartu Stok Bahan Baku</h1>
        <a href="{{ route('raw-materials.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-black uppercase tracking-widest shadow-md hover:bg-gray-50">
            Kembali
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
        <div class="mb-4 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $rawMaterial->name }}</h3>
                <p class="text-sm text-gray-500">Kode: {{ $rawMaterial->code }}</p>
                <p class="text-sm text-gray-500">
                    Metode: <span class="font-semibold text-indigo-600">
                        {{ strtoupper($selectedMethod ?? 'AVERAGE') }}
                        {{ $selectedMethod === 'fifo' ? '(First In First Out)' : '(Moving Average)' }}
                    </span>
                </p>
                <p class="text-sm text-gray-500">
                    Stok Saat Ini:
                    <span class="font-semibold">{{ number_format((int) round($currentStockDisplay ?? $rawMaterial->stock ?? 0), 0, ',', '.') }}</span>
                    <span class="text-xs text-gray-600 font-medium">({{ $unitMap[$selectedUnit] ?? $selectedUnit }})</span>
                </p>
            </div>
            <div class="flex flex-col md:flex-row md:items-end gap-4">
                {{-- Filter metode (pertahankan unit, bulan, tahun) --}}
                <form method="GET" action="{{ route('raw-materials.stock-card', $rawMaterial) }}" class="flex items-center gap-2">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="unit" value="{{ $selectedUnit }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="year" value="{{ $year ?? now()->year }}">
                    <label for="method" class="text-sm font-medium text-gray-700">Metode</label>
                    <select id="method" name="method" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" onchange="this.form.submit()">
                        <option value="average" {{ ($selectedMethod ?? 'average') === 'average' ? 'selected' : '' }}>Average</option>
                        <option value="fifo" {{ ($selectedMethod ?? 'average') === 'fifo' ? 'selected' : '' }}>FIFO</option>
                    </select>
                </form>

                {{-- Filter satuan & periode (bulan/tahun) --}}
                <form method="GET" action="{{ route('raw-materials.stock-card', $rawMaterial) }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="method" value="{{ $selectedMethod ?? 'average' }}">

                    <label for="unit" class="text-sm font-medium text-gray-700">Satuan</label>
                    <select id="unit" name="unit" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" onchange="this.form.submit()">
                        @foreach(($unitMap ?? []) as $code => $name)
                            <option value="{{ $code }}" {{ $code === $selectedUnit ? 'selected' : '' }}>{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>

                    @php
                        $monthNames = [
                            1 => 'January',
                            2 => 'February',
                            3 => 'March',
                            4 => 'April',
                            5 => 'May',
                            6 => 'June',
                            7 => 'July',
                            8 => 'August',
                            9 => 'September',
                            10 => 'October',
                            11 => 'November',
                            12 => 'December',
                        ];
                    @endphp

                    <label for="month" class="text-sm font-medium text-gray-700 ms-2">Bulan</label>
                    <select id="month" name="month" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" onchange="this.form.submit()">
                        <option value="">Semua</option>
                        @foreach($monthNames as $num => $name)
                            <option value="{{ $num }}" {{ (int)($month ?? 0) === $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>

                    <label for="year" class="text-sm font-medium text-gray-700 ms-2">Tahun</label>
                    <input id="year" name="year" type="number" min="2000" max="2100" value="{{ $year ?? now()->year }}" class="mt-1 block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" onchange="this.form.submit()" />
                </form>

                <div class="flex gap-2">
                    <a href="{{ route('raw-materials.stock-card.pdf', array_merge(['rawMaterial' => $rawMaterial->id], request()->query())) }}" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-md">
                        Cetak PDF
                    </a>
                    
                    @if($month && $year)
                        <form action="{{ route('stock-posting.store') }}" method="POST" onsubmit="return confirm('Posting data untuk bulan ini? Hal ini akan memperbarui saldo awal bulan berikutnya.')">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit" class="inline-flex items-center px-4 py-2 {{ $isPosted ? 'bg-orange-600 hover:bg-orange-700' : 'bg-indigo-600 hover:bg-indigo-700' }} border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-md">
                                {{ $isPosted ? 'Posting Ulang' : 'Post Data' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                        <th colspan="3" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-l">Masuk</th>
                        <th colspan="3" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-l">Keluar</th>
                        <th colspan="3" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-l">Saldo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-l">Keterangan</th>
                    </tr>
                    <tr class="bg-gray-50">
                        <th></th>
                        <th></th>
                        <th class="px-6 py-2 text-right text-[10px] font-medium text-gray-500 uppercase">Qty ({{ $unitMap[$selectedUnit] ?? $selectedUnit }})</th>
                        <th class="px-6 py-2 text-right text-[10px] font-medium text-gray-500 uppercase">Harga / Unit</th>
                        <th class="px-6 py-2 text-right text-[10px] font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-2 text-right text-[10px] font-medium text-gray-500 uppercase border-l">Qty ({{ $unitMap[$selectedUnit] ?? $selectedUnit }})</th>
                        <th class="px-6 py-2 text-right text-[10px] font-medium text-gray-500 uppercase">Harga / Unit</th>
                        <th class="px-6 py-2 text-right text-[10px] font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-2 text-right text-[10px] font-medium text-gray-500 uppercase border-l">Qty ({{ $unitMap[$selectedUnit] ?? $selectedUnit }})</th>
                        <th class="px-6 py-2 text-right text-[10px] font-medium text-gray-500 uppercase">Harga Rata-rata</th>
                        <th class="px-6 py-2 text-right text-[10px] font-medium text-gray-500 uppercase">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        // Inisialisasi saldo awal untuk metode average
                        $selectedMethod = $selectedMethod ?? 'average';

                        if ($selectedMethod !== 'fifo') {
                            // Untuk average: mulai dengan saldo 0 dan akumulasikan dari transaksi
                            $balanceBase = 0;
                            $balanceTotal = 0;
                            $averagePrice = 0;
                        } else {
                            // Untuk FIFO: saldo & layer sudah dihitung di controller per baris
                            $balanceBase = 0;
                            $balanceTotal = 0;
                            $averagePrice = 0;
                        }
                    @endphp
                    @forelse($entries as $entry)
                        @php
                            // qty_in & qty_out datang dalam satuan dasar bahan baku
                            $qtyInBase = $entry->qty_in ?? 0;
                            $qtyOutBase = $entry->qty_out ?? 0;

                            // Skip transaksi jika qty masuk dan qty keluar = 0 (kecuali Saldo Awal)
                            $type = $entry->type ?? '';
                            if ($qtyInBase == 0 && $qtyOutBase == 0 && $type !== 'opening') {
                                continue;
                            }

                            // Harga per unit untuk transaksi ini
                            $unitPriceIn = $entry->unit_price_in ?? null;

                             if ($selectedMethod === 'fifo') {
                                // Untuk FIFO, gunakan hasil perhitungan dari controller per baris transaksi
                                $totalInBase = $qtyInBase * ($entry->unit_price_in ?? 0);
                                $totalOutBase = $qtyOutBase * ($entry->unit_price_out ?? 0);

                                $unitPriceOut = $entry->unit_price_out ?? 0;

                                $balanceBase = $entry->fifo_balance_qty ?? 0;
                                $balanceTotal = $entry->fifo_balance_total ?? 0;
                                $averagePrice = $entry->fifo_balance_avg_price ?? 0;

                            } else {
                                // Metode Average (existing logic)
                                if ($type === 'opening') {
                                    $balanceBase = (float) ($entry->fifo_balance_qty ?? 0);
                                    $balanceTotal = (float) ($entry->fifo_balance_total ?? 0);
                                    $averagePrice = (float) ($entry->fifo_balance_avg_price ?? 0);
                                    $totalInBase = 0;
                                    $totalOutBase = 0;
                                    $unitPriceOut = $averagePrice;
                                } elseif ($qtyInBase > 0 && $unitPriceIn !== null) {
                                    // Transaksi MASUK - hitung ulang harga rata-rata
                                    $totalInBase = $qtyInBase * $unitPriceIn;
                                    $totalOutBase = 0;
                                    
                                    // Update saldo total dan qty
                                    $balanceTotal = $balanceTotal + $totalInBase;
                                    $balanceBase += $qtyInBase;
                                    
                                    // Hitung harga rata-rata baru
                                    if ($balanceBase > 0) {
                                        $averagePrice = $balanceTotal / $balanceBase;
                                    }
                                    $unitPriceOut = $averagePrice;
                                    
                                } elseif ($qtyOutBase > 0) {
                                    // Transaksi KELUAR - gunakan harga rata-rata existing, tidak ubah average
                                    // Validasi: tidak boleh keluar lebih dari stok yang ada
                                    if ($qtyOutBase > $balanceBase) {
                                        // Jika qty keluar melebihi stok, adjust ke stok yang tersisa
                                        $qtyOutBase = $balanceBase;
                                    }
                                    
                                    $unitPriceOut = $averagePrice;
                                    $totalInBase = 0;
                                    $totalOutBase = $qtyOutBase * $unitPriceOut;
                                    
                                    // Update saldo total dan qty
                                    $balanceTotal = $balanceTotal - $totalOutBase;
                                    $balanceBase -= $qtyOutBase;
                                    
                                    // Harga rata-rata TIDAK berubah saat transaksi keluar
                                    
                                } else {
                                    // Skip transaksi dengan qty = 0
                                    $totalInBase = 0;
                                    $totalOutBase = 0;
                                    $unitPriceOut = $averagePrice;
                                }
                            }

                            // Inisialisasi variabel display yang aman
                            $totalInBase = $totalInBase ?? 0;
                            $totalOutBase = $totalOutBase ?? 0;
                            $unitPriceOut = $unitPriceOut ?? $averagePrice ?? 0;

                            $factor = $displayFactor ?? 1;

                            // Konversi harga per unit & total ke satuan tampilan (asumsi linear)
                            $unitPriceInDisplay = $unitPriceIn !== null ? $unitPriceIn / $factor : null;
                            $unitPriceOutDisplay = $unitPriceOut !== null ? $unitPriceOut / $factor : null;
                            $averagePriceDisplay = ($averagePrice ?? 0) / $factor;

                            // Konversi qty & saldo ke satuan tampilan
                            $qtyIn = $qtyInBase * $factor;
                            $qtyOut = $qtyOutBase * $factor;
                            $balanceDisplay = $balanceBase * $factor;

                            $totalInDisplay = $qtyIn * ($unitPriceInDisplay ?? 0);
                            $totalOutDisplay = $qtyOut * ($unitPriceOutDisplay ?? 0);
                            $balanceTotalDisplay = $balanceDisplay * $averagePriceDisplay;
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ 
                                $entry->date instanceof \Illuminate\Support\Carbon || $entry->date instanceof \Carbon\Carbon
                                    ? $entry->date->format('d/m/Y')
                                    : ($entry->date ?? '-')
                            }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @php
                                    $type = $entry->type ?? '';
                                @endphp
                                @if($type === 'opening')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Saldo Awal</span>
                                @elseif($type === 'in')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Masuk</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Keluar</span>
                                @endif
                            </td>
                            {{-- Masuk --}}
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyIn > 0 ? number_format($qtyIn, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyIn > 0 && $unitPriceInDisplay !== null ? 'Rp ' . number_format($unitPriceInDisplay, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyIn > 0 && $totalInDisplay > 0 ? 'Rp ' . number_format($totalInDisplay, 0, ',', '.') : '-' }}
                            </td>

                            {{-- Keluar --}}
                            <td class="px-6 py-4 whitespace-nowrap text-left border-l">
                                {{ $qtyOut > 0 ? number_format($qtyOut, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyOut > 0 && $unitPriceOutDisplay !== null ? 'Rp ' . number_format($unitPriceOutDisplay, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyOut > 0 && $totalOutDisplay > 0 ? 'Rp ' . number_format($totalOutDisplay, 0, ',', '.') : '-' }}
                            </td>

                            {{-- Saldo --}}
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-left border-l">
                                @if(($selectedMethod ?? 'average') === 'fifo')
                                    @php 
                                        $layers = $entry->fifo_layers ?? [];
                                    @endphp
                                    @if(!empty($layers))
                                        {{-- Untuk FIFO, tampilkan qty per batch (BUKAN akumulasi) --}}
                                        @foreach($layers as $layer)
                                            {{ number_format(($layer['qty'] ?? 0) * $factor, 0, ',', '.') }}<br>
                                        @endforeach
                                    @else
                                        0
                                    @endif
                                @else
                                    {{ number_format($balanceDisplay, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                @if(($selectedMethod ?? 'average') === 'fifo')
                                    @php 
                                        $layers = $entry->fifo_layers ?? [];
                                    @endphp
                                    @if(!empty($layers))
                                        {{-- Untuk FIFO, tampilkan harga per batch --}}
                                        @foreach($layers as $layer)
                                            @php $priceDisplay = ($layer['price'] ?? 0) / $factor; @endphp
                                            {{ 'Rp ' . number_format($priceDisplay, 0, ',', '.') }}<br>
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                @else
                                    {{ $balanceDisplay > 0 ? 'Rp ' . number_format($averagePriceDisplay, 0, ',', '.') : '-' }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                @if(($selectedMethod ?? 'average') === 'fifo')
                                    @php 
                                        $layers = $entry->fifo_layers ?? [];
                                    @endphp
                                    @if(!empty($layers))
                                        {{-- Untuk FIFO, tampilkan total per batch (qty * price) --}}
                                        @foreach($layers as $layer)
                                            @php $batchTotal = ($layer['qty'] ?? 0) * ($layer['price'] ?? 0); @endphp
                                            {{ 'Rp ' . number_format($batchTotal, 0, ',', '.') }}<br>
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                @else
                                    {{ $balanceDisplay > 0 && $balanceTotalDisplay > 0 ? 'Rp ' . number_format($balanceTotalDisplay, 0, ',', '.') : '-' }}
                                @endif
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-500 border-l">{{ $entry->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const unitSelect = document.getElementById('unit');
    if (unitSelect) {
        unitSelect.addEventListener('change', function() {
            // Update all unit displays dynamically
            const selectedOption = this.options[this.selectedIndex];
            const unitText = selectedOption.textContent;
            const unitCode = selectedOption.value;
            
            // Extract unit name from text (format: "Unit Name (CODE)")
            const unitName = unitText.match(/^(.+)\s+\(/);
            const displayName = unitName ? unitName[1].trim() : unitCode;
            
            // Update stock display unit
            const stockUnitSpan = document.querySelector('span.text-xs.text-gray-600.font-medium');
            if (stockUnitSpan) {
                stockUnitSpan.textContent = `(${displayName})`;
            }
            
            // Update table headers
            const tableHeaders = document.querySelectorAll('th.text-right');
            tableHeaders.forEach(header => {
                if (header.textContent.includes('Qty (')) {
                    header.textContent = `Qty (${displayName})`;
                }
            });
        });
    }
});
</script>
@endsection