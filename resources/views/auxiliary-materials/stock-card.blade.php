@extends('layouts.app')

@section('title', 'Kartu Stok - ' . $auxiliaryMaterial->name)

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Kartu Stok Bahan Penolong</h1>
        <a href="{{ route('auxiliary-materials.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-black uppercase tracking-widest shadow-md hover:bg-gray-50">
            Kembali
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
        <div class="mb-4 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $auxiliaryMaterial->name }}</h3>
                <p class="text-sm text-gray-500">Kode: {{ $auxiliaryMaterial->code }}</p>
                <p class="text-sm text-gray-500">
                    Metode: <span class="font-semibold text-indigo-600">
                        {{ strtoupper($selectedMethod ?? 'AVERAGE') }}
                        {{ $selectedMethod === 'fifo' ? '(First In First Out)' : '(Moving Average)' }}
                    </span>
                </p>
                <p class="text-sm text-gray-500">
                    Stok Saat Ini:
                    <span class="font-semibold">{{ number_format($currentStockDisplay ?? $auxiliaryMaterial->stock, 0, ',', '.') }}</span>
                    <span class="text-xs text-gray-400">({{ $unitMap[$selectedUnit] ?? $selectedUnit }})</span>
                </p>
            </div>
            <div class="flex flex-col md:flex-row md:items-end gap-4">
                <form method="GET" action="{{ route('auxiliary-materials.stock-card', $auxiliaryMaterial) }}" class="flex items-center gap-2">
                    <input type="hidden" name="unit" value="{{ $selectedUnit }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="year" value="{{ $year ?? now()->year }}">
                    <label for="method" class="text-sm font-medium text-gray-700">Metode</label>
                    <select id="method" name="method" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" onchange="this.form.submit()">
                        <option value="average" {{ ($selectedMethod ?? 'average') === 'average' ? 'selected' : '' }}>Average</option>
                        <option value="fifo" {{ ($selectedMethod ?? 'average') === 'fifo' ? 'selected' : '' }}>FIFO</option>
                    </select>
                </form>

                <form method="GET" action="{{ route('auxiliary-materials.stock-card', $auxiliaryMaterial) }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="method" value="{{ $selectedMethod ?? 'average' }}">

                    <label for="unit" class="text-sm font-medium text-gray-700">Satuan</label>
                    <select id="unit" name="unit" class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" onchange="this.form.submit()">
                        @foreach(($unitMap ?? []) as $code => $name)
                            <option value="{{ $code }}" {{ $code === $selectedUnit ? 'selected' : '' }}>{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>

                    @php
                        $monthNames = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
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
                        $selectedMethod = $selectedMethod ?? 'average';

                        // Sempre inizia da 0 e accumula in avanti (come FIFO e come bahan baku)
                        $balanceBase = 0;
                        $balanceTotal = 0;
                        $averagePrice = 0;
                    @endphp
                    @forelse($entries as $entry)
                        @php
                            // qty_in & qty_out datang dalam satuan dasar bahan penolong
                            $qtyInBase = (float) ($entry->qty_in ?? 0);
                            $qtyOutBase = (float) ($entry->qty_out ?? 0);
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
                                @if($type === 'opening')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Saldo Awal</span>
                                @elseif($type === 'in')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Pembelian</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Keluar</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyIn > 0 ? number_format($qtyIn, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyIn > 0 && $unitPriceInDisplay !== null ? 'Rp ' . number_format($unitPriceInDisplay, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyIn > 0 && $totalInDisplay > 0 ? 'Rp ' . number_format($totalInDisplay, 0, ',', '.') : '-' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-left border-l">
                                {{ $qtyOut > 0 ? number_format($qtyOut, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyOut > 0 && $unitPriceOutDisplay !== null ? 'Rp ' . number_format($unitPriceOutDisplay, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                {{ $qtyOut > 0 && $totalOutDisplay > 0 ? 'Rp ' . number_format($totalOutDisplay, 0, ',', '.') : '-' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-left border-l">
                                @if($selectedMethod === 'fifo')
                                    @php $layers = $entry->fifo_layers ?? []; @endphp
                                    @if(!empty($layers))
                                        @foreach($layers as $layer)
                                            {{ number_format(($layer['qty'] ?? 0) * $factor, 0, ',', '.') }}<br>
                                        @endforeach
                                    @else
                                        {{ number_format($balanceDisplay, 0, ',', '.') }}
                                    @endif
                                @else
                                    {{ number_format($balanceDisplay, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                @if($selectedMethod === 'fifo')
                                    @php $layers = $entry->fifo_layers ?? []; @endphp
                                    @if(!empty($layers))
                                        @foreach($layers as $layer)
                                            @php $priceDisplay = ($layer['price'] ?? 0) / $factor; @endphp
                                            {{ 'Rp ' . number_format($priceDisplay, 0, ',', '.') }}<br>
                                        @endforeach
                                    @else
                                        {{ $balanceDisplay > 0 ? 'Rp ' . number_format($averagePriceDisplay, 0, ',', '.') : '-' }}
                                    @endif
                                @else
                                    {{ $balanceDisplay > 0 ? 'Rp ' . number_format($averagePriceDisplay, 0, ',', '.') : '-' }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left">
                                @if($selectedMethod === 'fifo')
                                    @php $layers = $entry->fifo_layers ?? []; @endphp
                                    @if(!empty($layers))
                                        @foreach($layers as $layer)
                                            @php $layerTotal = ($layer['qty'] ?? 0) * ($layer['price'] ?? 0); @endphp
                                            {{ 'Rp ' . number_format($layerTotal, 0, ',', '.') }}<br>
                                        @endforeach
                                    @else
                                        {{ $balanceDisplay > 0 ? 'Rp ' . number_format($balanceTotalDisplay, 0, ',', '.') : '-' }}
                                    @endif
                                @else
                                    {{ $balanceDisplay > 0 ? 'Rp ' . number_format($balanceTotalDisplay, 0, ',', '.') : '-' }}
                                @endif
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-500 border-l">{{ $entry->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada transaksi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
