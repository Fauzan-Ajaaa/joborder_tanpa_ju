<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Semua Penggajian</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; margin-bottom: 16px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        .right { text-align: right; }
        .muted { color: #666; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1>Daftar Penggajian</h1>
            <div class="muted">Dicetak: {{ now()->format('d M Y H:i') }}</div>
        </div>
        <div class="no-print">
            <button onclick="window.print()">Print</button>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>Tanggal</th>
            <th>No Transaksi</th>
            <th>Pegawai</th>
            <th class="right">Tarif</th>
            <th class="right">Bonus Service</th>
            <th class="right">Bonus Kehadiran</th>
            <th class="right">Tunjangan</th>
            <th class="right">Lembur</th>
            <th class="right">Potongan</th>
            <th class="right">Total Bersih</th>
        </tr>
        </thead>
        <tbody>
        @php($sum = ['tarif'=>0,'bs'=>0,'bk'=>0,'tm'=>0,'tj'=>0,'lembur'=>0,'potongan'=>0,'total'=>0])
        @forelse ($items as $p)
            @php(
                $sum['tarif'] += (float) $p->tarif;
                $sum['bs'] += (float) $p->bonus_service;
                $sum['bk'] += (float) $p->total_bonus_kehadiran;
                $sum['tm'] += (float) $p->tunjangan_makan;
                $sum['tj'] += (float) $p->tunjangan_jabatan;
                $sum['lembur'] += (float) $p->lembur;
                $sum['potongan'] += (float) $p->potongan_gaji;
                $sum['total'] += (float) $p->total_gaji_bersih;
            )
            <tr>
                <td>{{ optional($p->tanggal_penggajian)->format('d M Y') }}</td>
                <td>{{ $p->no_transaksi_gaji }}</td>
                <td>{{ $p->employee->name ?? '-' }}</td>
                <td class="right">Rp {{ number_format((float) $p->tarif, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) $p->bonus_service, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) $p->total_bonus_kehadiran, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) $p->tunjangan_makan, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) $p->lembur, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) $p->potongan_gaji, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) $p->total_gaji_bersih, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="muted">Tidak ada data</td>
            </tr>
        @endforelse
        </tbody>
        <tfoot>
        <tr>
            <th colspan="3" class="right">Total</th>
            <th class="right">Rp {{ number_format($sum['tarif'], 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sum['bs'], 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sum['bk'], 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sum['tm'], 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sum['lembur'], 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sum['potongan'], 0, ',', '.') }}</th>
            <th class="right">Rp {{ number_format($sum['total'], 0, ',', '.') }}</th>
        </tr>
        </tfoot>
    </table>
</div>
</body>
</html>
