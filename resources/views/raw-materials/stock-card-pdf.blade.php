<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Stok - {{ $rawMaterial->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .material-info {
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border: 1px solid #ddd;
        }
        .material-info h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: bold;
        }
        .material-info p {
            margin: 5px 0;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px 4px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .bg-gray { background-color: #f8f9fa; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-opening { background-color: #f3f4f6; color: #374151; }
        .badge-in { background-color: #d1fae5; color: #065f46; }
        .badge-out { background-color: #fee2e2; color: #991b1b; }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
        }
        @page {
            margin: 1cm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ auth()->user()->nama_perusahaan ?? 'PT. Manufaktur' }}</div>
        <div class="report-title">KARTU STOK BAHAN BAKU</div>
        @if($month && $year)
            <div>Periode: {{ \Carbon\Carbon::create($year, $month)->translatedFormat('F Y') }}</div>
        @endif
    </div>

    <div class="material-info">
        <h3>{{ $rawMaterial->name }}</h3>
        <p><strong>Kode:</strong> {{ $rawMaterial->code }}</p>
        <p><strong>Metode:</strong> {{ strtoupper($selectedMethod ?? 'AVERAGE') }} {{ $selectedMethod === 'fifo' ? '(First In First Out)' : '(Moving Average)' }}</p>
        <p><strong>Satuan:</strong> {{ $unitMap[$selectedUnit] ?? $selectedUnit }}</p>
        <p><strong>Stok Saat Ini:</strong> {{ number_format((int) round($currentStockDisplay ?? $rawMaterial->stock ?? 0), 0, ',', '.') }} {{ $unitMap[$selectedUnit] ?? $selectedUnit }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 8%;">Tanggal</th>
                <th rowspan="2" style="width: 8%;">Tipe</th>
                <th colspan="3" style="width: 24%;">Masuk</th>
                <th colspan="3" style="width: 24%;">Keluar</th>
                <th colspan="3" style="width: 24%;">Saldo</th>
                <th rowspan="2" style="width: 12%;">Keterangan</th>
            </tr>
            <tr>
                <th>Qty</th>
                <th>Harga/Unit</th>
                <th>Total</th>
                <th>Qty</th>
                <th>Harga/Unit</th>
                <th>Total</th>
                <th>Qty</th>
                <th>Harga Rata-rata</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $selectedMethod = $selectedMethod ?? 'average';
                if ($selectedMethod !== 'fifo') {
                    $balanceBase = 0;
                    $balanceTotal = 0;
                    $averagePrice = 0;
                } else {
                    $balanceBase = 0;
                    $balanceTotal = 0;
                    $averagePrice = 0;
                }
            @endphp
            @forelse($entries as $entry)
                @php
                    $qtyInBase = $entry->qty_in ?? 0;
                    $qtyOutBase = $entry->qty_out ?? 0;
                    $type = $entry->type ?? '';
                    
                    if ($qtyInBase == 0 && $qtyOutBase == 0 && $type !== 'opening') {
                        continue;
                    }

                    $unitPriceIn = $entry->unit_price_in ?? null;

                    if ($selectedMethod === 'fifo') {
                        $totalInBase = $qtyInBase * ($entry->unit_price_in ?? 0);
                        $totalOutBase = $qtyOutBase * ($entry->unit_price_out ?? 0);
                        $unitPriceOut = $entry->unit_price_out ?? 0;
                        $balanceBase = $entry->fifo_balance_qty ?? 0;
                        $balanceTotal = $entry->fifo_balance_total ?? 0;
                        $averagePrice = $entry->fifo_balance_avg_price ?? 0;
                    } else {
                        if ($type === 'opening') {
                            $balanceBase = (float) ($entry->fifo_balance_qty ?? 0);
                            $balanceTotal = (float) ($entry->fifo_balance_total ?? 0);
                            $averagePrice = (float) ($entry->fifo_balance_avg_price ?? 0);
                            $totalInBase = 0;
                            $totalOutBase = 0;
                            $unitPriceOut = $averagePrice;
                        } elseif ($qtyInBase > 0 && $unitPriceIn !== null) {
                            $totalInBase = $qtyInBase * $unitPriceIn;
                            $totalOutBase = 0;
                            $balanceTotal = $balanceTotal + $totalInBase;
                            $balanceBase += $qtyInBase;
                            if ($balanceBase > 0) {
                                $averagePrice = $balanceTotal / $balanceBase;
                            }
                            $unitPriceOut = $averagePrice;
                        } elseif ($qtyOutBase > 0) {
                            if ($qtyOutBase > $balanceBase) {
                                $qtyOutBase = $balanceBase;
                            }
                            $unitPriceOut = $averagePrice;
                            $totalInBase = 0;
                            $totalOutBase = $qtyOutBase * $unitPriceOut;
                            $balanceTotal = $balanceTotal - $totalOutBase;
                            $balanceBase -= $qtyOutBase;
                        } else {
                            $totalInBase = 0;
                            $totalOutBase = 0;
                            $unitPriceOut = $averagePrice;
                        }
                    }

                    $totalInBase = $totalInBase ?? 0;
                    $totalOutBase = $totalOutBase ?? 0;
                    $unitPriceOut = $unitPriceOut ?? $averagePrice ?? 0;
                    $factor = $displayFactor ?? 1;

                    $unitPriceInDisplay = $unitPriceIn !== null ? $unitPriceIn / $factor : null;
                    $unitPriceOutDisplay = $unitPriceOut !== null ? $unitPriceOut / $factor : null;
                    $averagePriceDisplay = ($averagePrice ?? 0) / $factor;

                    $qtyIn = $qtyInBase * $factor;
                    $qtyOut = $qtyOutBase * $factor;
                    $balanceDisplay = $balanceBase * $factor;

                    $totalInDisplay = $qtyIn * ($unitPriceInDisplay ?? 0);
                    $totalOutDisplay = $qtyOut * ($unitPriceOutDisplay ?? 0);
                    $balanceTotalDisplay = $balanceDisplay * $averagePriceDisplay;
                @endphp
                <tr>
                    <td class="text-center">
                        {{ $entry->date instanceof \Illuminate\Support\Carbon || $entry->date instanceof \Carbon\Carbon
                            ? $entry->date->format('d/m/Y')
                            : ($entry->date ?? '-') }}
                    </td>
                    <td class="text-center">
                        @if($type === 'opening')
                            <span class="badge badge-opening">Saldo Awal</span>
                        @elseif($type === 'in')
                            <span class="badge badge-in">Masuk</span>
                        @else
                            <span class="badge badge-out">Keluar</span>
                        @endif
                    </td>
                    {{-- Masuk --}}
                    <td class="text-right">{{ $qtyIn > 0 ? number_format($qtyIn, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $qtyIn > 0 && $unitPriceInDisplay !== null ? number_format($unitPriceInDisplay, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $qtyIn > 0 && $totalInDisplay > 0 ? number_format($totalInDisplay, 0, ',', '.') : '-' }}</td>
                    {{-- Keluar --}}
                    <td class="text-right">{{ $qtyOut > 0 ? number_format($qtyOut, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $qtyOut > 0 && $unitPriceOutDisplay !== null ? number_format($unitPriceOutDisplay, 0, ',', '.') : '-' }}</td>
                    <td class="text-right">{{ $qtyOut > 0 && $totalOutDisplay > 0 ? number_format($totalOutDisplay, 0, ',', '.') : '-' }}</td>
                    {{-- Saldo --}}
                    <td class="text-right font-bold">
                        @if(($method ?? 'average') === 'fifo')
                            @php $layers = $entry->fifo_layers ?? []; @endphp
                            @if(!empty($layers))
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
                    <td class="text-right">
                        @if(($method ?? 'average') === 'fifo')
                            @php $layers = $entry->fifo_layers ?? []; @endphp
                            @if(!empty($layers))
                                @foreach($layers as $layer)
                                    @php $priceDisplay = ($layer['price'] ?? 0) / $factor; @endphp
                                    {{ number_format($priceDisplay, 0, ',', '.') }}<br>
                                @endforeach
                            @else
                                -
                            @endif
                        @else
                            {{ $balanceDisplay > 0 ? number_format($averagePriceDisplay, 0, ',', '.') : '-' }}
                        @endif
                    </td>
                    <td class="text-right">
                        @if(($method ?? 'average') === 'fifo')
                            @php $layers = $entry->fifo_layers ?? []; @endphp
                            @if(!empty($layers))
                                @foreach($layers as $layer)
                                    @php $layerTotal = ($layer['qty'] ?? 0) * ($layer['price'] ?? 0); @endphp
                                    {{ number_format($layerTotal, 0, ',', '.') }}<br>
                                @endforeach
                            @else
                                -
                            @endif
                        @else
                            {{ $balanceDisplay > 0 && $balanceTotalDisplay > 0 ? number_format($balanceTotalDisplay, 0, ',', '.') : '-' }}
                        @endif
                    </td>
                    <td>{{ $entry->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">Belum ada transaksi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>