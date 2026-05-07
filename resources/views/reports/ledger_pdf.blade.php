
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Buku Besar - {{ $account->account_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .header p { margin: 5px 0; }
        .info { margin-bottom: 20px; }
        .info p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; }
        th { background-color: #f0f0f0; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div style="font-weight:bold; font-size:14px;">{{ $appName ?? config('app.name') }}</div>
        <div style="color:#666; font-size:11px; margin:3px 0;">Laporan Keuangan {{ $period }}</div>
        <div style="font-weight:bold; font-size:13px;">Buku Besar</div>
    </div>

    <div class="info">
        <p><strong>Kode Akun:</strong> {{ $account->code }}</p>
        <p><strong>Nama Akun:</strong> {{ $account->account_name }}</p>
        @if($openingBalance !== null && $openingBalance != 0)
        <p><strong>Saldo Awal:</strong> {{ number_format($openingBalance, 0, ',', '.') }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="10%">Tanggal</th>
                <th width="40%">Keterangan</th>
                <th width="15%" class="text-right">Debit</th>
                <th width="15%" class="text-right">Kredit</th>
                <th width="20%" class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $runningBalance = $openingBalance ?? 0;
            @endphp
            
            @if($openingBalance !== null && $openingBalance != 0)
            <tr>
                <td class="text-center">{{ \Carbon\Carbon::create($year, $month, 1)->subDay()->format('d/m/Y') }}</td>
                <td>Saldo Awal</td>
                <td class="text-right">{{ $openingBalance >= 0 ? number_format($openingBalance, 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ $openingBalance < 0 ? number_format(abs($openingBalance), 0, ',', '.') : '' }}</td>
                <td class="text-right">{{ number_format($openingBalance, 0, ',', '.') }}</td>
            </tr>
            @endif
            @foreach($transactions as $transaction)
                @php
                    $debit  = (float) $transaction->debit;
                    $credit = (float) $transaction->credit;
                    $runningBalance += $debit - $credit;
                @endphp
                <tr>
                    <td class="text-center">{{ \Carbon\Carbon::parse($transaction->journalEntry->transaction_date)->format('d/m/Y') }}</td>
                    <td>{{ $transaction->description ?: $transaction->journalEntry->description }}</td>
                    <td class="text-right">{{ $debit  > 0 ? number_format($debit, 0, ',', '.')  : '' }}</td>
                    <td class="text-right">{{ $credit > 0 ? number_format($credit, 0, ',', '.') : '' }}</td>
                    <td class="text-right">{{ number_format($runningBalance, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top:8px; font-size:9px; color:#999;">Dicetak: {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>