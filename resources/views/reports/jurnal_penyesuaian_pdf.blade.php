<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Penyesuaian</title>
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
        .period {
            font-size: 12px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .text-gray { color: #6b7280; }
        .italic { font-style: italic; }
        .pl-10 { padding-left: 20px; }
        .border-thick { border-bottom: 2px solid #333; }
        .bg-gray { background-color: #f8f9fa; }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
        }
        .status-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        @page {
            margin: 1cm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ auth()->user()->nama_perusahaan ?? 'PT. Manufaktur' }}</div>
        <div class="report-title">JURNAL PENYESUAIAN</div>
        <div class="period">Periode: {{ $periode }}</div>
    </div>

    @if(count($entries) > 0)
    <div class="status-info">
        <strong>Status Posting:</strong> 
        @if($isPosted)
            ✓ Sudah diposting ke Neraca Saldo
        @else
            Belum diposting
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 40%;">Keterangan</th>
                <th style="width: 8%;">Ref</th>
                <th style="width: 20%;">Debet</th>
                <th style="width: 20%;">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                {{-- Baris Debet: BOP Penyusutan --}}
                <tr>
                    <td class="text-center font-bold" rowspan="2">
                        {{ $entry['tanggal']->translatedFormat('F Y') }}
                    </td>
                    <td>
                        {{ $entry['bop_coa']->account_name ?? ('BOP-Penyusutan ' . ucfirst($entry['asset']->tipe_asset) . ' - ' . $entry['asset']->nama_asset) }}
                    </td>
                    <td class="text-center text-gray">
                        {{ $entry['bop_coa']->code ?? '-' }}
                    </td>
                    <td class="text-right text-green font-bold">
                        Rp {{ number_format($entry['beban'], 0, ',', '.') }}
                    </td>
                    <td class="text-right text-gray">-</td>
                </tr>
                {{-- Baris Kredit: Akumulasi Penyusutan --}}
                <tr class="border-thick">
                    <td class="pl-10 text-gray italic">
                        {{ $entry['akum_coa']->account_name ?? ('Akumulasi Penyusutan ' . ucfirst($entry['asset']->tipe_asset) . ' - ' . $entry['asset']->nama_asset) }}
                    </td>
                    <td class="text-center text-gray">
                        {{ $entry['akum_coa']->code ?? '-' }}
                    </td>
                    <td class="text-right text-gray">-</td>
                    <td class="text-right text-red font-bold">
                        Rp {{ number_format($entry['beban'], 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-gray">
                        Tidak ada penyusutan untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($entries) > 0)
        <tfoot class="bg-gray font-bold">
            <tr style="border-top: 2px solid #333;">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right text-green">
                    Rp {{ number_format($totalDebit, 0, ',', '.') }}
                </td>
                <td class="text-right text-red">
                    Rp {{ number_format($totalCredit, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>