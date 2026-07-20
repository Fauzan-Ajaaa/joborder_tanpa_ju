<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
    h1 { font-size: 16px; margin: 0; }
    h2 { font-size: 13px; margin: 0; }
    .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #333; padding-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #f0f0f0; padding: 6px 8px; text-align: left; border: 1px solid #ccc; font-size: 10px; text-transform: uppercase; }
    td { padding: 5px 8px; border: 1px solid #ddd; }
    .section-header { background: #e8f0fe; font-weight: bold; }
    .account-row td:first-child { padding-left: 24px; }
    .total-row { font-weight: bold; background: #f9f9f9; }
    .net-income { font-weight: bold; background: #e8f0fe; font-size: 12px; }
    .text-right { text-align: right; }
    .profit { color: green; }
    .loss { color: red; }
</style>
</head>
<body>
<div class="header">
    <div style="font-weight:bold; font-size:15px;">{{ $company }}</div>
    <div style="color:#666; font-size:11px; margin:3px 0;">Laporan Keuangan {{ \Carbon\Carbon::create($year, $month)->translatedFormat('F Y') }}</div>
    <div style="font-weight:bold; font-size:13px;">Laporan Laba Rugi</div>
</div>

@php $fmt = fn($n) => ($n < 0 ? '-' : '') . number_format(abs($n ?? 0), 0, ',', '.'); @endphp

<table>
    <thead>
        <tr>
            <th>Akun</th>
            <th class="text-right" style="width:180px">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        {{-- PENDAPATAN --}}
        <tr class="section-header"><td colspan="2">PENDAPATAN</td></tr>
        @foreach($revenues as $account)
        <tr class="account-row">
            <td>{{ $account['code'] }} - {{ $account['name'] }}</td>
            <td class="text-right">{{ $fmt($account['amount']) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td>Total Pendapatan</td>
            <td class="text-right">{{ $fmt($totalRevenue) }}</td>
        </tr>

        {{-- HPP (HARGA POKOK PENJUALAN) --}}
        <tr class="section-header"><td colspan="2">HARGA POKOK PENJUALAN (HPP)</td></tr>
        @foreach($cogs as $account)
        <tr class="account-row">
            <td>{{ $account['code'] }} - {{ $account['name'] }}</td>
            <td class="text-right">{{ $fmt($account['amount']) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td>Total HPP</td>
            <td class="text-right loss">({{ $fmt($totalCOGS) }})</td>
        </tr>

        {{-- LABA KOTOR --}}
        <tr class="net-income" style="background:#d4edda;">
            <td>LABA KOTOR</td>
            <td class="text-right {{ $grossProfit >= 0 ? 'profit' : 'loss' }}">
                {{ $fmt($grossProfit) }}
            </td>
        </tr>

        {{-- BEBAN OPERASIONAL --}}
        <tr class="section-header"><td colspan="2">BEBAN OPERASIONAL</td></tr>
        @foreach($expenses as $account)
        <tr class="account-row">
            <td>{{ $account['code'] }} - {{ $account['name'] }}</td>
            <td class="text-right">{{ $fmt($account['amount']) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td>Total Beban Operasional</td>
            <td class="text-right loss">({{ $fmt($totalExpense) }})</td>
        </tr>

        {{-- LABA/RUGI BERSIH --}}
        <tr class="net-income">
            <td>{{ $netIncome >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}</td>
            <td class="text-right {{ $netIncome >= 0 ? 'profit' : 'loss' }}">
                {{ $fmt($netIncome) }}
            </td>
        </tr>
    </tbody>
</table>

<p style="margin-top:8px; font-size:9px; color:#999;">Dicetak: {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
