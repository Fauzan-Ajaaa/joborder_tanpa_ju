<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak BTKL - {{ $payroll->kode_penggajian ?? 'BTKL-' . $payroll->id }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; margin-bottom: 16px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .right { text-align: right; }
        .muted { color: #666; }
        .meta td { border: none; padding: 2px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>Biaya Tenaga Kerja Langsung (BTKL)</h1>
            <div class="muted">Kode: {{ $payroll->kode_penggajian }}</div>
        </div>
        <div class="no-print">
            <button onclick="window.print()">Print</button>
        </div>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Pegawai</strong></td>
            <td>: {{ $payroll->employee->name ?? '-' }} ({{ $payroll->employee->employee_number ?? '-' }})</td>
        </tr>
        <tr>
            <td><strong>Produk</strong></td>
            <td>: {{ $payroll->product->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td>
            <td>: {{ optional($payroll->work_date)->format('d M Y') }}</td>
        </tr>
    </table>

    <table>
        <thead>
        <tr>
            <th>Jam Kerja</th>
            <th class="right">Gaji per Jam</th>
            <th class="right">Bonus</th>
            <th class="right">Potongan</th>
            <th class="right">Pajak (%)</th>
            <th class="right">Total Gaji / Hari</th>
            <th class="right">BTKL / Pcs</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{ number_format((float) ($payroll->total_jam_kerja ?? 0), 0, ',', '.') }} Jam</td>
            <td class="right">Rp {{ number_format((float) ($payroll->gaji_per_jam ?? 0), 0, ',', '.') }}</td>
            <td class="right">Rp {{ number_format((float) ($payroll->bonus ?? 0), 0, ',', '.') }}</td>
            <td class="right">Rp {{ number_format((float) ($payroll->potongan ?? 0), 0, ',', '.') }}</td>
            <td class="right">{{ number_format((float) ($payroll->pajak ?? 0), 2, ',', '.') }}%</td>
            <td class="right">Rp {{ number_format((float) ($payroll->total_gaji_perhari ?? 0), 0, ',', '.') }}</td>
            <td class="right">Rp {{ number_format((float) ($payroll->total_btkl ?? 0), 0, ',', '.') }}</td>
        </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top:12px;">Dokumen ini dihasilkan secara otomatis oleh sistem.</p>
</div>
</body>
</html>
