<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur Penjualan - INV-{{ date('Ymd', strtotime($sales->transaction_date)) }}-{{ str_pad($sales->id, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        @media print {
            body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; margin: 0; padding: 20px; }
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }
        @media screen {
            body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; margin: 20px; padding: 0; }
        }
        .header { text-align: center; margin-bottom: 30px; }
        .company-info { margin-bottom: 20px; }
        .invoice-info { margin-bottom: 20px; }
        .customer-info { margin-bottom: 20px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .items-table th { background-color: #f5f5f5; font-weight: bold; }
        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }
        .totals { margin-left: auto; width: 300px; }
        .totals td { border: 1px solid #000; padding: 8px; }
        .totals .total-row { font-weight: bold; border-top: 2px solid #000; }
        .footer { margin-top: 40px; }
        .signature-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .signature-box { text-align: center; }
        .signature-line { margin-top: 40px; border-bottom: 1px solid #000; height: 20px; }
    </style>
</head>
<body>
    <!-- Print Button -->
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
            Cetak Faktur
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; margin-left: 10px;">
            Tutup
        </button>
    </div>

    <!-- Header -->
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">FAKTUR PENJUALAN</h1>
        <p style="margin: 5px 0; font-size: 18px; font-weight: bold;">
            No. INV-{{ date('Ymd', strtotime($sales->transaction_date)) }}-{{ str_pad($sales->id, 4, '0', STR_PAD_LEFT) }}
        </p>
        <p style="margin: 5px 0;">Tanggal: {{ date('d F Y', strtotime($sales->transaction_date)) }}</p>
    </div>

    <!-- Company Info -->
    <div class="company-info">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>{{ $company['name'] }}</strong><br>
                    {{ $company['address'] }}<br>
                    Telp: {{ $company['phone'] }}<br>
                    Email: {{ $company['email'] }}<br>
                    NPWP: {{ $company['tax_id'] }}
                </td>
                <td style="width: 50%; vertical-align: top; text-align: right;">
                    <strong>Kepada Yth:</strong><br>
                    {{ $sales->jobOrder->customer->name ?? $sales->customer_name ?? '-' }}<br>
                    {{ $sales->jobOrder->customer->address ?? $sales->customer_address ?? '-' }}<br>
                    {{ $sales->jobOrder->customer->phone ?? '-' }}<br>
                    @if($sales->jobOrder && $sales->jobOrder->customer)
                        Kode Pelanggan: {{ $sales->jobOrder->customer->code }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Transaction Info -->
    <div class="invoice-info">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <strong>No. Transaksi:</strong> {{ $sales->transaction_number }}<br>
                    <strong>Sales:</strong> {{ $sales->jobOrder->labors->first()->employee->name ?? $sales->employee ?? '-' }}
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Metode Pembayaran:</strong> 
                    <span style="padding: 2px 8px; border-radius: 12px; font-size: 11px; 
                        {{ $sales->payment_status == 'cash' ? 'background: #d4edda; color: #155724;' : 
                           ($sales->payment_status == 'transfer' ? 'background: #d1ecf1; color: #0c5460;' : 
                           'background: #e2e3e5; color: #383d41;') }}">
                        {{ $sales->payment_status == 'cash' ? 'Tunai' : 
                           ($sales->payment_status == 'transfer' ? 'Transfer' : 'E-Wallet') }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <h3 style="margin-bottom: 10px;">Detail Barang</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Nama Produk</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 20%; text-align: right;">Harga Satuan</th>
                <th style="width: 20%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales->salesItems as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td class="text-center">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <table>
            <tr>
                <td style="width: 70%; text-align: right;">Subtotal:</td>
                <td style="width: 30%; text-align: right;">Rp {{ number_format($sales->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($sales->fob_cost > 0)
                <tr>
                    <td style="text-align: right;">FOB Cost ({{ $sales->fob_type }}):</td>
                    <td style="text-align: right;">Rp {{ number_format($sales->fob_cost, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($sales->ppn_amount > 0)
                <tr>
                    <td style="text-align: right;">PPN ({{ $sales->ppn_rate }}%):</td>
                    <td style="text-align: right;">Rp {{ number_format($sales->ppn_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td style="text-align: right; font-size: 14px;">TOTAL:</td>
                <td style="text-align: right; font-size: 14px;">Rp {{ number_format($sales->grand_total, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <!-- Notes -->
    @if($sales->notes)
        <div style="margin: 20px 0;">
            <h3>Catatan:</h3>
            <p>{{ $sales->notes }}</p>
        </div>
    @endif

    <!-- Footer Signatures -->
    <div class="footer">
        <div class="signature-grid">
            <div class="signature-box">
                <p>Penerima Barang,</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p>Mengetahui,</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p>{{ $company['name'] }},</p>
                <div class="signature-line"></div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto-print if requested
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === '1') {
                setTimeout(() => {
                    window.print();
                }, 500);
            }
        };
    </script>
</body>
</html>
