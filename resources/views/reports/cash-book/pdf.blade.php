<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Buku Kas</title>
    <style>
        @page {
            margin: 1.5cm;
            size: A4;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .period {
            font-size: 11px;
            margin-bottom: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: top;
        }
        th {
            background: #f5f5f5;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary {
            margin-top: 20px;
            width: 40%;
            margin-left: auto;
        }
        .summary td {
            border: none;
            padding: 3px 8px;
        }
        .summary td:first-child {
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            font-style: italic;
            padding: 20px;
        }
        .footer {
            position: absolute;
            bottom: 1.5cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $appName ?? config('app.name') }}</div>
        <div style="color:#666; font-size:11px; margin:3px 0;">Laporan Keuangan</div>
        <div class="report-title">Buku Kas</div>
        <div class="period">
            @if($startDate && $endDate)
                Periode {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s.d. {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            @else
                Semua Periode
            @endif
        </div>
    </div>

    @if(count($transactionData) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 11%">Tanggal</th>
                    <th style="width: 35%">Keterangan</th>
                    <th style="width: 13%">No. Bukti</th>
                    <th style="width: 12%" class="text-right">Debit (Rp)</th>
                    <th style="width: 12%" class="text-right">Kredit (Rp)</th>
                    <th style="width: 12%" class="text-right">Saldo (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactionData as $i => $row)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $row['transaction_date'] }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td>{{ $row['reference'] ?: '-' }}</td>
                        <td class="text-right">{{ $row['debit'] }}</td>
                        <td class="text-right">{{ $row['credit'] }}</td>
                        <td class="text-right"><strong>{{ $row['running_balance'] }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td colspan="2"><strong>Total Debit</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['total_debit'] ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td colspan="2"><strong>Total Kredit</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['total_credit'] ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td colspan="2"><strong>Saldo Akhir</strong></td>
                <td class="text-right"><strong>{{ number_format($summary['final_balance'] ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    @else
        <div class="no-data">
            Tidak ada data transaksi pada periode yang dipilih.
        </div>
    @endif

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
