<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan HPP Job Order</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #000; padding: 4px; }
        th { background-color: #f3f4f6; font-size: 9px; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="title">Laporan HPP Job Order</div>
    <div style="font-size:9px; margin-bottom: 8px;">
        Periode:
        @if($from) {{ date('d/m/Y', strtotime($from)) }} @else - @endif
        s/d
        @if($to) {{ date('d/m/Y', strtotime($to)) }} @else - @endif
    </div>

    @forelse($jobs as $job)
        <table>
            <tr>
                <td colspan="4" class="text-left">
                    <strong>Kode Job:</strong> {{ $job->kode_job }}<br>
                    <strong>Tanggal Selesai:</strong> {{ optional($job->selesai_job_at)->format('d/m/Y') ?? '-' }}<br>
                    <strong>Customer:</strong> {{ $job->customer_name ?? '-' }}
                </td>
                <td colspan="3" class="text-right">
                    <strong>BBB Total Job:</strong> Rp {{ number_format($job->total_bbb ?? 0, 0, ',', '.') }}<br>
                    <strong>BTKL Total Job:</strong> Rp {{ number_format($job->total_btkl ?? 0, 0, ',', '.') }}<br>
                    <strong>BOP Total Job:</strong> Rp {{ number_format($job->total_bop ?? 0, 0, ',', '.') }}<br>
                    <strong>Total HPP Job:</strong> Rp {{ number_format($job->total_hpp ?? 0, 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <th class="text-left">Produk</th>
                <th class="text-right">Qty</th>
                <th class="text-right">BBB</th>
                <th class="text-right">BTKL</th>
                <th class="text-right">BOP</th>
                <th class="text-right">Total HPP</th>
                <th class="text-right">HPP / Unit</th>
            </tr>
            @forelse($job->items as $item)
                <tr>
                    <td class="text-left">{{ $item->product->code ?? '-' }} - {{ $item->product->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->quantity ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->bbb_total ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->btkl_total ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->bop_total ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->hpp_total ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->hpp_per_unit ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada produk untuk job order ini.</td>
                </tr>
            @endforelse
        </table>
    @empty
        <p class="text-center">Belum ada job order selesai pada periode ini.</p>
    @endforelse
</body>
</html>
