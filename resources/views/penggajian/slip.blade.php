<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - {{ $penggajian->no_transaksi_gaji }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 13px; color: #1a1a1a; background: #f3f4f6; }

        .page-wrapper { display: flex; flex-direction: column; align-items: center; padding: 32px 16px; min-height: 100vh; }

        .slip { background: #fff; width: 100%; max-width: 680px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.1); overflow: hidden; }

        /* Header */
        .slip-header { background: #1e3a5f; color: #fff; padding: 28px 32px; text-align: center; }
        .slip-header .company-name { font-size: 18px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
        .slip-header .slip-title { font-size: 13px; margin-top: 4px; opacity: 0.8; letter-spacing: 2px; text-transform: uppercase; }
        .slip-header .periode { margin-top: 8px; font-size: 14px; font-weight: 600; background: rgba(255,255,255,0.15); display: inline-block; padding: 3px 14px; border-radius: 20px; }

        /* Info karyawan */
        .slip-info { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border-bottom: 1px solid #e5e7eb; }
        .info-block { padding: 18px 32px; }
        .info-block:first-child { border-right: 1px solid #e5e7eb; }
        .info-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .info-value { font-size: 14px; font-weight: 600; color: #111827; }
        .info-sub { font-size: 12px; color: #6b7280; margin-top: 1px; }

        /* Tabel komponen */
        .slip-body { padding: 24px 32px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        table thead tr { background: #f9fafb; }
        table thead th { padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 700; color: #374151; border-bottom: 2px solid #e5e7eb; }
        table thead th:last-child { text-align: right; }
        table tbody tr { border-bottom: 1px solid #f3f4f6; }
        table tbody tr:last-child { border-bottom: none; }
        table tbody td { padding: 9px 14px; font-size: 13px; color: #374151; }
        table tbody td:last-child { text-align: right; }
        .plus { color: #059669; }
        .minus { color: #dc2626; }

        /* Total */
        .total-row { background: #1e3a5f; }
        .total-row td { padding: 14px 14px; color: #fff; font-weight: 700; font-size: 14px; }
        .total-row td:last-child { text-align: right; font-size: 16px; }

        /* Catatan */
        .slip-note { margin: 0 32px 20px; padding: 12px 16px; background: #fffbeb; border-left: 3px solid #f59e0b; border-radius: 4px; font-size: 12px; color: #92400e; }

        /* TTD */
        .slip-footer { display: grid; grid-template-columns: 1fr 1fr; border-top: 1px solid #e5e7eb; padding: 24px 32px; }
        .ttd-block { text-align: center; }
        .ttd-label { font-size: 12px; color: #6b7280; }
        .ttd-space { height: 56px; }
        .ttd-name { font-size: 13px; font-weight: 600; border-top: 1px solid #374151; display: inline-block; padding-top: 4px; min-width: 120px; }

        /* Print button */
        .print-bar { width: 100%; max-width: 680px; display: flex; justify-content: flex-end; gap: 10px; margin-bottom: 16px; }
        .btn { padding: 8px 20px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; }
        .btn-print { background: #1e3a5f; color: #fff; }
        .btn-back { background: #fff; color: #374151; border: 1px solid #d1d5db; text-decoration: none; display: inline-flex; align-items: center; }

        @media print {
            body { background: #fff; }
            .print-bar { display: none; }
            .slip { box-shadow: none; border-radius: 0; max-width: 100%; }
            .page-wrapper { padding: 0; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="print-bar">
        <a href="{{ route('penggajian.show', $penggajian->id_gaji) }}" class="btn btn-back">← Kembali</a>
        <button onclick="window.print()" class="btn btn-print">🖨 Cetak</button>
    </div>

    <div class="slip">
        {{-- Header --}}
        <div class="slip-header">
            <div class="company-name">{{ $penggajian->employee->company->name ?? config('app.name') }}</div>
            <div class="slip-title">Slip Gaji Karyawan</div>
            <div class="periode">{{ \Carbon\Carbon::parse($penggajian->tanggal_penggajian)->translatedFormat('F Y') }}</div>
        </div>

        {{-- Info --}}
        <div class="slip-info">
            <div class="info-block">
                <div class="info-label">Nama Karyawan</div>
                <div class="info-value">{{ $penggajian->employee->name }}</div>
                <div class="info-sub">{{ $penggajian->employee->employee_number }}</div>
            </div>
            <div class="info-block">
                <div class="info-label">Jabatan</div>
                <div class="info-value">{{ $penggajian->employee->position ?? '-' }}</div>
                <div class="info-sub">{{ $penggajian->no_transaksi_gaji }}</div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="slip-body">
            <div class="section-title">Rincian Komponen Gaji</div>
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
                        <td>Rp {{ number_format($penggajian->tarif, 0, ',', '.') }}</td>
                    </tr>
                    @if($penggajian->tunjangan_makan > 0)
                    <tr>
                        <td>Tunjangan Makan</td>
                        <td class="plus">+ Rp {{ number_format($penggajian->tunjangan_makan, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($penggajian->tunjangan_jabatan > 0)
                    <tr>
                        <td>Tunjangan Jabatan</td>
                        <td class="plus">+ Rp {{ number_format($penggajian->tunjangan_jabatan, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if(($penggajian->tunjangan_lainnya ?? 0) > 0)
                    <tr>
                        <td>Tunjangan Transportasi</td>
                        <td class="plus">+ Rp {{ number_format($penggajian->tunjangan_lainnya, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($penggajian->bonus > 0)
                    <tr>
                        <td>Bonus</td>
                        <td class="plus">+ Rp {{ number_format($penggajian->bonus, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($penggajian->total_bonus_kehadiran > 0)
                    <tr>
                        <td>Bonus Kehadiran</td>
                        <td class="plus">+ Rp {{ number_format($penggajian->total_bonus_kehadiran, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($penggajian->lembur > 0)
                    <tr>
                        <td>Lembur</td>
                        <td class="plus">+ Rp {{ number_format($penggajian->lembur, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    @if($penggajian->potongan_gaji > 0)
                    <tr>
                        <td>Potongan</td>
                        <td class="minus">- Rp {{ number_format($penggajian->potongan_gaji, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td>Total Gaji Bersih</td>
                        <td>Rp {{ number_format($penggajian->total_gaji_bersih, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Catatan --}}
        @if($penggajian->detail_potongan)
        <div class="slip-note">
            <strong>Catatan:</strong> {{ $penggajian->detail_potongan }}
        </div>
        @endif

        {{-- TTD --}}
        <div class="slip-footer">
            <div class="ttd-block">
                <div class="ttd-label">Karyawan</div>
                <div class="ttd-space"></div>
                <div class="ttd-name">{{ $penggajian->employee->name }}</div>
            </div>
            <div class="ttd-block">
                <div class="ttd-label">Mengetahui</div>
                <div class="ttd-space"></div>
                <div class="ttd-name">Manajemen</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
