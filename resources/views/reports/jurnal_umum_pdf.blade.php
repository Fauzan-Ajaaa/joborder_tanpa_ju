<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Jurnal Umum - {{ $periode }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 12px; }
        .title { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; }
        th { background: #f2f2f2; }
        .right { text-align: right; }
        .left { text-align: left; }
        .center { text-align: center; }
        .muted { color: #666; font-style: italic; }
    </style>
</head>
<body>
<div class="header">
        <div style="font-weight:bold; font-size:14px;">{{ $appName ?? config('app.name') }}</div>
        <div style="color:#666; font-size:11px; margin:3px 0;">Laporan Keuangan {{ $periode }}</div>
        <div style="font-weight:bold; font-size:13px;">Jurnal Umum</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 18%">Tanggal</th>
                <th style="width: 45%">Akun</th>
                <th style="width: 10%">Ref</th>
                <th style="width: 15%" class="right">Debit</th>
                <th style="width: 15%" class="right">Kredit</th>
            </tr>
        </thead>
        <tbody>
        @php
            $lastJournalId = null; $lastDate = null;
            $formatMoney = function($v) { return $v > 0 ? 'Rp '.number_format($v, 0, ',', '.') : ''; };
        @endphp
        @foreach ($items as $item)
            @php
                $isDebit = (float) $item->debit > 0;
                $showDate = $isDebit && $item->journal_entry_id !== $lastJournalId;
                $dateStr = optional($item->journalEntry?->transaction_date)->format('d M Y');
            @endphp
            <tr>
                <td>{{ $showDate ? $dateStr : '' }}</td>
                <td style="@if($isDebit) text-align: left; @else text-align: center; @endif">
                    {{ $item->chartOfAccount?->account_name }}
                    @if(!$isDebit)
                        <div class="muted">({{ $item->journalEntry?->description }})</div>
                    @endif
                </td>
                <td>{{ $item->chartOfAccount?->code }}</td>
                <td class="right">{{ $formatMoney((float) $item->debit) }}</td>
                <td class="right">{{ $formatMoney((float) $item->credit) }}</td>
            </tr>
            @php $lastJournalId = $item->journal_entry_id; @endphp
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="right">Total</th>
                <th class="right">{{ $formatMoney($totalDebit ?? 0) }}</th>
                <th class="right">{{ $formatMoney($totalCredit ?? 0) }}</th>
            </tr>
        </tfoot>
    </table>
    <p style="margin-top:8px; font-size:9px; color:#999;">Dicetak: {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
