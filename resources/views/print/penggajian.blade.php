<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $item->no_transaksi_gaji }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a1a; background: #f3f4f6; }
        @page { margin: 2cm; }

        .page { display: flex; flex-direction: column; align-items: center; padding: 32px 16px; }

        .slip { background: #fff; width: 100%; max-width: 640px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.12); overflow: hidden; margin: auto; }

        /* Header */
        .slip-header { background: #1e3a5f; color: #fff; padding: 24px 32px; text-align: center; }
        .company { font-size: 17px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .slip-title { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; opacity: 0.75; margin-top: 3px; }
        .periode-badge { display: inline-block; margin-top: 10px; background: rgba(255,255,255,0.15); padding: 3px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }

        /* Info karyawan */
        .slip-info { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #e5e7eb; }
        .info-col { padding: 16px 24px; }
        .info-col:first-child { border-right: 1px solid #e5e7eb; }
        .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 2px; }
        .val { font-size: 13px; font-weight: 700; color: #111827; }
        .sub { font-size: 11px; color: #6b7280; margin-top: 1px; }

        /* Tabel */
        .slip-body { padding: 20px 24px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #f9fafb; }
        thead th { padding: 9px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        thead th:last-child { text-align: right; }
        tbody tr { border-bottom: 1px solid #f3f4f6; }
        tbody td { padding: 8px 12px; font-size: 12px; color: #374151; }
        tbody td:last-child { text-align: right; }
        .plus { color: #059669; }
        .minus { color: #dc2626; }
        .total-row td { background: #1e3a5f; color: #fff; font-weight: 700; font-size: 13px; padding: 12px; }
        .total-row td:last-child { text-align: right; font-size: 15px; }

        /* Catatan */
        .slip-note { margin: 0 24px 16px; padding: 10px 14px; background: #fffbeb; border-left: 3px solid #f59e0b; font-size: 11px; color: #92400e; border-radius: 3px; }

        /* TTD */
        .slip-footer { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid #e5e7eb; padding: 20px 24px 24px; }
        .ttd { text-align: center; }
        .ttd-lbl { font-size: 11px; color: #6b7280; }
        .ttd-space { height: 52px; }
        .ttd-name { font-size: 12px; font-weight: 700; border-top: 1px solid #374151; display: inline-block; padding-top: 4px; min-width: 110px; }

        /* Print bar */
        .print-bar { display: none; }

        @media print {
            body { background: #fff; }
            .print-bar { display: none; }
            .slip { box-shadow: none; border-radius: 0; max-width: 100%; }
            .page { padding: 0; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="print-bar no-print">
        <a href="javascript:history.back()" class="btn btn-back">← Kembali</a>
        <button onclick="window.print()" class="btn btn-print">🖨 Cetak</button>
    </div>

    <div class="slip">
        <div class="slip-header">
            <div class="company">{{ $item->employee->company->name ?? config('app.name') }}</div>
            <div class="slip-title">Slip Gaji Karyawan</div>
            <div class="periode-badge">{{ \Carbon\Carbon::parse($item->tanggal_penggajian)->translatedFormat('F Y') }}</div>
        </div>

        <div class="slip-info">
            <div class="info-col">
                <div class="lbl">Nama Karyawan</div>
                <div class="val">{{ $item->employee->name ?? '-' }}</div>
                <div class="sub">{{ $item->employee->employee_number ?? '-' }}</div>
            </div>
            <div class="info-col">
                <div class="lbl">Jabatan</div>
                <div class="val">{{ $item->employee->position ?? '-' }}</div>
                <div class="sub">{{ $item->no_transaksi_gaji }}</div>
            </div>
        </div>

        <div class="slip-body">
            <table>
                <thead>
                    <tr>
                        <th>Komponen</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gaji Pokok</td>
                        <td>Rp {{ number_format((float) $item->tarif, 0, ',', '.') }}</td>
                    </tr>
                    @if((float)$item->tunjangan_makan > 0)
                    <tr>
                        <td>Tunjangan Makan</td>
                        <td class="plus">+ Rp {{ number_format((float) $item->tunjangan_makan, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if((float)$item->tunjangan_jabatan > 0)
                    <tr>
                        <td>Tunjangan Jabatan</td>
                        <td class="plus">+ Rp {{ number_format((float) $item->tunjangan_jabatan, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if((float)($item->tunjangan_lainnya ?? 0) > 0)
                    <tr>
                        <td>Tunjangan Transportasi</td>
                        <td class="plus">+ Rp {{ number_format((float) $item->tunjangan_lainnya, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if((float)$item->bonus > 0)
                    <tr>
                        <td>Bonus</td>
                        <td class="plus">+ Rp {{ number_format((float) $item->bonus, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if((float)$item->total_bonus_kehadiran > 0)
                    <tr>
                        <td>Bonus Kehadiran</td>
                        <td class="plus">+ Rp {{ number_format((float) $item->total_bonus_kehadiran, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if((float)$item->lembur > 0)
                    <tr>
                        <td>Lembur</td>
                        <td class="plus">+ Rp {{ number_format((float) $item->lembur, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if((float)$item->potongan_gaji > 0)
                    <tr>
                        <td>Potongan</td>
                        <td class="minus">- Rp {{ number_format((float) $item->potongan_gaji, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total Gaji Bersih</td>
                        <td>Rp {{ number_format((float) $item->total_gaji_bersih, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        @if(!empty($item->detail_potongan))
        <div class="slip-note"><strong>Catatan:</strong> {{ $item->detail_potongan }}</div>
        @endif

        <div class="slip-footer">
            <div class="ttd">
                <div class="ttd-lbl">Karyawan</div>
                <div class="ttd-space"></div>
                <div class="ttd-name">{{ $item->employee->name ?? '-' }}</div>
            </div>
            <div class="ttd">
                <div class="ttd-lbl">Mengetahui</div>
                <div class="ttd-space"></div>
                <div class="ttd-name">Manajemen</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
