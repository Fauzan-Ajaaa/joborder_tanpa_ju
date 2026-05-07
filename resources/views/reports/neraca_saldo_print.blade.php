<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>NERACA SALDO - {{ $period['formatted'] }} {{ $period['year'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header h2 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .header .period {
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .status-section {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #000;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            text-align: center;
        }
        
        .status-item {
            padding: 5px;
        }
        
        .status-value {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .status-label {
            font-size: 10px;
        }
        
        .balance {
            color: #006600;
        }
        
        .not-balance {
            color: #CC0000;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .bg-gray {
            background-color: #f0f0f0;
        }
        
        .bg-red {
            background-color: #ffe6e6;
            color: #CC0000;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
        }
        
        @media print {
            .container {
                padding: 10px;
            }
            
            .header {
                margin-bottom: 15px;
            }
            
            .status-section {
                margin-bottom: 15px;
            }
            
            table {
                margin-bottom: 15px;
            }
            
            .footer {
                margin-top: 20px;
            }
            
            @page {
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $appName ?? config('app.name') }}</h1>
            <p style="color:#666; font-size:12px; margin:4px 0;">Laporan Keuangan {{ $period['formatted'] }} {{ $period['year'] }}</p>
            <h2>Neraca Saldo</h2>
        </div>

        <!-- Balance Status -->
        <div class="status-section">
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-value {{ $isBalanced ? 'balance' : 'not-balance' }}">
                        {{ $isBalanced ? '✅ BALANCE' : '❌ TIDAK BALANCE' }}
                    </div>
                    <div class="status-label">Status Neraca</div>
                </div>
                <div class="status-item">
                    <div class="status-value">
                        {{ number_format($trialBalance['total_debit'], 0, ',', '.') }}
                    </div>
                    <div class="status-label">Total Debit</div>
                </div>
                <div class="status-item">
                    <div class="status-value">
                        {{ number_format($trialBalance['total_credit'], 0, ',', '.') }}
                    </div>
                    <div class="status-label">Total Kredit</div>
                </div>
            </div>
            
            @if(!$isBalanced)
            <div style="margin-top: 10px; padding: 5px; background-color: #ffe6e6; text-align: center; color: #CC0000;">
                <strong>Selisih:</strong> {{ number_format(abs($difference), 0, ',', '.') }}
            </div>
            @endif
        </div>

        <!-- Trial Balance Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 60%;">AKUN</th>
                    <th style="width: 20%;">DEBIT (Rp)</th>
                    <th style="width: 20%;">KREDIT (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trialBalance['accounts'] as $account)
                    <tr>
                        <td>{{ $account['code'] }} - {{ $account['name'] }}</td>
                        <td class="text-right">
                            @if($account['debit'] > 0)
                                {{ number_format($account['debit'], 0, ',', '.') }}
                            @else
                                0
                            @endif
                        </td>
                        <td class="text-right">
                            @if($account['credit'] > 0)
                                {{ number_format($account['credit'], 0, ',', '.') }}
                            @else
                                0
                            @endif
                        </td>
                    </tr>
                @endforeach

                <!-- Totals -->
                <tr class="bold bg-gray">
                    <td class="text-center">TOTAL</td>
                    <td class="text-right">{{ number_format($trialBalance['total_debit'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($trialBalance['total_credit'], 0, ',', '.') }}</td>
                </tr>

                @if(!$isBalanced)
                <tr class="bold bg-red">
                    <td class="text-center">SELISIH</td>
                    <td class="text-right" colspan="2">{{ number_format($difference, 0, ',', '.') }}</td>
                </tr>
                @endif
            </tbody>
        </table>

        <!-- Footer -->
        <div class="footer">
            <p>Dicetak melalui Sistem Akuntansi Manufaktur pada {{ now()->format('d F Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
