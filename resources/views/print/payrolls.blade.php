<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Semua BTKL</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; }
        .container { max-width: 1000px; margin: 0 auto; }
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
            <h1>Daftar BTKL</h1>
            <div class="muted">Dicetak: {{ now()->format('d M Y H:i') }}</div>
        </div>
        <div class="no-print">
            <button onclick="window.print()">Print</button>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>Kode</th>
            <th>Pegawai</th>
            <th>ID Pegawai</th>
            <th>Produk</th>
            <th>Tanggal</th>
            <th class="right">Jam Kerja</th>
            <th class="right">Gaji/Jam</th>
            <th class="right">Bonus</th>
            <th class="right">Potongan</th>
            <th class="right">Pajak (%)</th>
            <th class="right">Total Gaji/Hari</th>
            <th class="right">BTKL/Pcs</th>
        </tr>
        </thead>
        <tbody>
        @php
            $sumJam = 0; $sumGaji = 0; $sumBonus = 0; $sumPotongan = 0; $sumGajiHari = 0; $sumBtkl = 0;
        @endphp
        @forelse ($payrolls as $p)
            @php
                $sumJam += (float) ($p->total_jam_kerja ?? 0);
                $sumGaji += (float) ($p->gaji_per_jam ?? 0);
                $sumBonus += (float) ($p->bonus ?? 0);
                $sumPotongan += (float) ($p->potongan ?? 0);
                $sumGajiHari += (float) ($p->total_gaji_perhari ?? 0);
                $sumBtkl += (float) ($p->total_btkl ?? 0);
            @endphp
            <tr>
                <td>{{ $p->kode_penggajian }}</td>
                <td>{{ $p->employee->name ?? '-' }}</td>
                <td>{{ $p->employee->employee_number ?? '-' }}</td>
                <td>{{ $p->product->name ?? '-' }}</td>
                <td>{{ optional($p->work_date)->format('d M Y') }}</td>
                <td class="right">{{ number_format((float) ($p->total_jam_kerja ?? 0), 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) ($p->gaji_per_jam ?? 0), 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) ($p->bonus ?? 0), 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) ($p->potongan ?? 0), 0, ',', '.') }}</td>
                <td class="right">{{ number_format((float) ($p->pajak ?? 0), 2, ',', '.') }}%</td>
                <td class="right">Rp {{ number_format((float) ($p->total_gaji_perhari ?? 0), 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) ($p->total_btkl ?? 0), 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="12" class="muted">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr>
            <th colspan="5" class="right">Total</th>
            <th class="right">{{ number_format($sumJam, 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sumGaji, 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sumBonus, 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sumPotongan, 0, ',', '.') }}</th>
            <th class="right">—</th>
            <th class="right">Rp {{ number_format($sumGajiHari, 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sumBtkl, 0, ',', '.') }}</th>
        </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
