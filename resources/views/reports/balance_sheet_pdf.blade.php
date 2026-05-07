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
    .section-header td { background: #e8f0fe; font-weight: bold; }
    .sub-header td { background: #f5f5f5; font-weight: bold; padding-left: 16px; }
    .account-row td:first-child { padding-left: 32px; }
    .total-row td { font-weight: bold; background: #f9f9f9; }
    .grand-total td { font-weight: bold; background: #e8f0fe; }
    .text-right { text-align: right; }
    .balanced { color: green; font-weight: bold; }
    .unbalanced { color: red; font-weight: bold; }
</style>
</head>
<body>
<div class="header">
    <div style="font-weight:bold; font-size:15px;">{{ $company }}</div>
    <div style="color:#666; font-size:11px; margin:3px 0;">Laporan Keuangan {{ $period['formatted'] ?? ($month . '/' . $year) }}</div>
    <div style="font-weight:bold; font-size:13px;">Laporan Posisi Keuangan</div>
</div>

@php
$sectionLabels = [
    'assets'      => 'ASET',
    'liabilities' => 'KEWAJIBAN',
    'equity'      => 'EKUITAS',
];
$subLabels = [
    'current_assets'        => 'Aset Lancar',
    'fixed_assets'          => 'Aset Tetap',
    'contra_assets'         => 'Akumulasi Penyusutan',
    'other_assets'          => 'Aset Lainnya',
    'current_liabilities'   => 'Kewajiban Lancar',
    'long_term_liabilities' => 'Kewajiban Jangka Panjang',
    'capital'               => 'Modal',
    'retained_earnings'     => 'Laba Ditahan',
];
$fmt = fn($n) => ($n < 0 ? '-' : '') . number_format(abs($n ?? 0), 0, ',', '.');
@endphp

<table>
    <thead>
        <tr>
            <th>Akun</th>
            <th class="text-right" style="width:180px">Jumlah (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @foreach(['assets','liabilities','equity'] as $section)
        @if(isset($balanceSheet[$section]))
        <tr class="section-header"><td colspan="2">{{ $sectionLabels[$section] }}</td></tr>
        @foreach($balanceSheet[$section] as $subKey => $subData)
            @if($subKey === 'total') @continue @endif
            @if(isset($subData['accounts']) && count($subData['accounts']) > 0)
            <tr class="sub-header"><td colspan="2">{{ $subLabels[$subKey] ?? $subKey }}</td></tr>
            @foreach($subData['accounts'] as $account)
            <tr class="account-row">
                <td>{{ $account['code'] ?? '' }} - {{ $account['name'] ?? '' }}</td>
                <td class="text-right">{{ $fmt($account['balance'] ?? 0) }}</td>
            </tr>
            @endforeach
            @endif
        @endforeach
        <tr class="total-row">
            <td>Total {{ $sectionLabels[$section] }}</td>
            <td class="text-right">{{ $fmt($balanceSheet[$section]['total'] ?? 0) }}</td>
        </tr>
        @endif
        @endforeach

        <tr class="grand-total">
            <td>Total Kewajiban + Ekuitas</td>
            <td class="text-right">
                {{ $fmt(($balanceSheet['liabilities']['total'] ?? 0) + ($balanceSheet['equity']['total'] ?? 0)) }}
            </td>
        </tr>
    </tbody>
</table>

<p style="margin-top:12px; font-size:10px;" class="{{ $isBalanced ? 'balanced' : 'unbalanced' }}">
    {{ $isBalanced ? 'Laporan Seimbang' : 'Laporan Tidak Seimbang' }}
</p>
<p style="margin-top:8px; font-size:9px; color:#999;">Dicetak: {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
