<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
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
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header p {
            margin: 3px 0;
            color: #333;
            font-size: 10px;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 20px;
            font-size: 9px;
            color: #666;
        }
        
        .summary {
            margin-bottom: 25px;
        }
        
        .summary h2 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .summary-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10px;
        }
        
        .summary-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 70%;
        }
        
        .summary-table .value {
            text-align: right;
            font-weight: bold;
            width: 30%;
        }
        
        .detail-section {
            margin-bottom: 25px;
        }
        
        .detail-section h2 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        
        .detail-table th {
            border: 1px solid #000;
            background-color: #f5f5f5;
            padding: 6px 4px;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
        }
        
        .detail-table td {
            border: 1px solid #000;
            padding: 5px 4px;
            vertical-align: top;
        }
        
        .detail-table .text-right {
            text-align: right;
        }
        
        .detail-table .text-center {
            text-align: center;
        }
        
        .detail-table .no-border {
            border: none;
        }
        
        .total-section {
            margin-top: 20px;
        }
        
        .total-table {
            width: 50%;
            margin-left: auto;
            border-collapse: collapse;
        }
        
        .total-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10px;
        }
        
        .total-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .total-table .value {
            text-align: right;
            font-weight: bold;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 10px 0;
            border-top: 1px solid #ccc;
            font-size: 8px;
            color: #666;
        }
        
        .page-number {
            position: fixed;
            bottom: 5px;
            right: 15px;
            font-size: 8px;
            color: #666;
        }
        
        .separator {
            height: 1px;
            background-color: #000;
            margin: 15px 0;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .status-unpaid {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Penjualan</h1>
        <p><strong>Periode: {{ $title }}</strong></p>
        <p>Tanggal Cetak: {{ date('d F Y H:i') }}</p>
    </div>
    
    <div class="company-info">
        <p>{{ strtoupper(auth()->user()->nama_perusahaan ?? 'PT. MANUFAKTUR JOB ORDER COST') }}</p>
        <p>{{ auth()->user()->alamat_perusahaan ?? 'Jl. Contoh No. 123, Kota, Indonesia' }}</p>
    </div>

    <div class="summary">
        <h2>Ringkasan Penjualan</h2>
        <table class="summary-table">
            <tr>
                <td class="label">Total Transaksi</td>
                <td class="value">{{ number_format($summary['total_transactions'], 0, ',', '.') }} Transaksi</td>
            </tr>
            <tr>
                <td class="label">Total Pendapatan Kotor</td>
                <td class="value">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total Diskon</td>
                <td class="value">(Rp {{ number_format($summary['total_discount'], 0, ',', '.') }})</td>
            </tr>
            <tr>
                <td class="label">Total Ongkir (FOB)</td>
                <td class="value">Rp {{ number_format($summary['total_fob'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total PPN</td>
                <td class="value">Rp {{ number_format($summary['total_ppn'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total Item Terjual</td>
                <td class="value">{{ number_format($summary['total_items_sold'], 0, ',', '.') }} Items</td>
            </tr>
            <tr>
                <td class="label">Rata-rata Nilai Transaksi</td>
                <td class="value">Rp {{ number_format($summary['avg_transaction_value'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="detail-section">
        <h2>Detail Transaksi Penjualan</h2>
        <table class="detail-table">
            <thead>
                <tr>
                    <th width="8%">Tanggal</th>
                    <th width="13%">No. Transaksi</th>
                    <th width="15%">Customer</th>
                    <th width="18%">Produk</th>
                    <th width="5%" class="text-right">Qty</th>
                    <th width="10%" class="text-right">Harga</th>
                    <th width="10%" class="text-right">Subtotal</th>
                    <th width="6%" class="text-right">Diskon</th>
                    <th width="6%" class="text-right">Ongkir</th>
                    <th width="6%" class="text-right">PPN</th>
                    <th width="9%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    @foreach($sale->items as $index => $item)
                        <tr>
                            <td>{{ $sale->transaction_date->format('d/m/Y') }}</td>
                            <td>{{ $sale->transaction_number }}</td>
                            <td>{{ $sale->customer ? $sale->customer->name : $sale->customer_name }}</td>
                            <td>{{ $item->product ? $item->product->name : 'Unknown' }}</td>
                            <td class="text-right">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            <td class="text-right">@if($index===0) Rp {{ number_format($sale->discount_amount, 0, ',', '.') }} @else - @endif</td>
                            <td class="text-right">@if($index===0) Rp {{ number_format($sale->fob_cost, 0, ',', '.') }} @else - @endif</td>
                            <td class="text-right">@if($index===0) Rp {{ number_format($sale->ppn_amount, 0, ',', '.') }} @else - @endif</td>
                            <td class="text-right">@if($index===0) <strong>Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</strong> @else - @endif</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="12" class="text-center"><em>Tidak ada data penjualan pada periode yang dipilih</em></td>
                    </tr>
                @endforelse
            </tbody>
            @if($summary['total_transactions'] > 0)
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right"><strong>TOTAL:</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($sales->sum(fn($s) => $s->items->sum('subtotal')), 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($summary['total_fob'], 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($summary['total_ppn'], 0, ',', '.') }}</strong></td>
                        <td class="text-right"><strong>Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</strong></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    @if($summary['total_transactions'] > 0)
        <div class="total-section">
            <table class="total-table">
                <tr>
                    <td class="label">Total Transaksi</td>
                    <td class="value">{{ number_format($summary['total_transactions'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Total Pendapatan Bersih</td>
                    <td class="value">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Total Item Terjual</td>
                    <td class="value">{{ number_format($summary['total_items_sold'], 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>Laporan ini dicetak secara otomatis dari sistem pada {{ date('d F Y H:i') }}</p>
        <p>Halaman <span class="page-number"></span></p>
    </div>
</body>
</html>
