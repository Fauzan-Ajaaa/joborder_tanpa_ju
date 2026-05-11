<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 15mm 10mm 15mm 10mm;
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
            margin-bottom: 15px;
        }
        
        .detail-table th {
            background-color: #f5f5f5;
            border: 1px solid #000;
            padding: 4px 2px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
        }
        
        .detail-table td {
            border: 1px solid #000;
            padding: 3px 2px;
            font-size: 8px;
        }
        
        .detail-table .text-right {
            text-align: right;
        }
        
        .detail-table .text-center {
            text-align: center;
        }
        
        .detail-table .no-data {
            text-align: center;
            font-style: italic;
            padding: 20px;
        }
        
        .footer {
            position: fixed;
            bottom: 20mm;
            left: 15mm;
            right: 15mm;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
        
        .page-number {
            position: fixed;
            bottom: 15mm;
            right: 15mm;
            font-size: 8px;
            color: #666;
        }
        
        .status-lunas {
            background-color: #d4edda;
            color: #155724;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 7px;
            white-space: nowrap;
        }
        
        .status-belum {
            background-color: #fff3cd;
            color: #856404;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 7px;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Tanggal Cetak: {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <div class="summary">
        <h2>Ringkasan Laporan</h2>
        <table class="summary-table">
            <tr>
                <td class="label">Total Transaksi</td>
                <td class="value">{{ $summary['total_transactions'] }}</td>
            </tr>
            <tr>
                <td class="label">Total Pembelian</td>
                <td class="value">Rp {{ number_format($summary['total_purchase'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total Item Dibeli</td>
                <td class="value">{{ number_format($summary['total_items_purchased'], floor($summary['total_items_purchased']) == $summary['total_items_purchased'] ? 0 : 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Rata-rata Transaksi</td>
                <td class="value">Rp {{ number_format($summary['avg_transaction_value'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total Diskon</td>
                <td class="value">Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total Ongkir</td>
                <td class="value">Rp {{ number_format($summary['total_shipping'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total PPN</td>
                <td class="value">Rp {{ number_format($summary['total_tax'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="detail-section">
        <h2>Detail Transaksi Pembelian</h2>
        <table class="detail-table">
            <thead>
                <tr>
                    <th width="8%">Tanggal</th>
                    <th width="12%">No. Transaksi</th>
                    <th width="10%">Supplier</th>
                    <th width="10%" class="text-right">Qty/Satuan</th>
                    <th width="10%" class="text-right">Harga</th>
                    <th width="10%" class="text-right">Subtotal</th>
                    <th width="8%" class="text-right">Diskon</th>
                    <th width="8%" class="text-right">Ongkir</th>
                    <th width="8%" class="text-right">PPN</th>
                    <th width="10%" class="text-right">Total</th>
                    <th width="6%">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                    <!-- Transaction Header Row -->
                    <tr style="background-color: #f9f9f9;">
                        <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                        <td style="font-weight: bold; color: #2563eb;">{{ $purchase->purchase_number }}</td>
                        <td>{{ $purchase->supplier ? $purchase->supplier->name : 'Unknown' }}</td>
                        <td class="text-right">{{ $purchase->total_quantity_with_unit }}</td>
                        <td class="text-right">Rp {{ number_format($purchase->average_unit_price, 0, ',', '.') }}</td>
                        <td class="text-right"></td>
                        <td class="text-right">Rp {{ number_format($purchase->discount_amount, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($purchase->fob_cost, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($purchase->ppn_amount, 0, ',', '.') }}</td>
                        <td class="text-right" style="font-weight: bold;">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="{{ $purchase->payment_status === 'paid' ? 'status-lunas' : 'status-belum' }}">
                                {{ $purchase->payment_status === 'paid' ? 'LUNAS' : 'BELUM' }}
                            </span>
                        </td>
                    </tr>
                    
                    <!-- Transaction Items Rows -->
                    @foreach($purchase->items as $item)
                        <tr>
                            <td style="color: #ccc;"></td>
                            <td style="padding-left: 20px; color: #555;">
                                - {{ $item->item_name }}
                            </td>
                            <td></td>
                            <td class="text-right">{{ number_format($item->quantity, floor($item->quantity) == $item->quantity ? 0 : 2, ',', '.') }} {{ $item->unit_name }}</td>
                            <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-center">-</td>
                        </tr>
                    @endforeach
                    <!-- Separator Line -->
                    <tr style="border-bottom: 1px solid #000;"><td colspan="11" style="padding: 0; border: none; height: 1px;"></td></tr>
                @empty
                    <tr>
                        <td colspan="11" class="no-data">
                            Tidak ada data pembelian pada periode yang dipilih
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($summary['total_transactions'] > 0)
                <tfoot>
                    <tr style="font-weight: bold; background-color: #f5f5f5;">
                        <td colspan="5" class="text-right">TOTAL KESELURUHAN:</td>
                        <td class="text-right">Rp {{ number_format($purchases->sum(fn($purchase) => $purchase->items->sum('subtotal')), 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($summary['total_shipping'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($summary['total_tax'], 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($summary['total_purchase'], 0, ',', '.') }}</td>
                        <td class="text-center">-</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <div class="footer">
        <p>Laporan Pembelian - Sistem Manufaktur</p>
    </div>
</body>
</html>
