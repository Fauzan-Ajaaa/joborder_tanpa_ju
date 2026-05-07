<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Penjualan</title>
    <style>
        body {
            font-family: monospace, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 10px;
        }
        .receipt {
            max-width: 270px; /* kira-kira lebar kertas thermal 80mm */
            margin: 0 auto;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .mt-1 { margin-top: 4px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
    </style>
</head>
<body>
<div class="receipt">
    @php
        $company = [
            'name' => auth()->user()->nama_perusahaan ?? 'PT. Manufaktur Job Order Cost',
            'address' => auth()->user()->alamat_perusahaan ?? 'Jl. Industri No. 123, Jakarta',
            'phone' => '+62 21 1234 5678',
            'email' => 'info@manufaktur.com',
            'tax_id' => '123.456.789.0-123.000',
        ];

        $customerName = data_get($sale, 'jobOrder.customer.name')
            ?? $sale->customer_name
            ?? '-';
        $customerAddress = data_get($sale, 'jobOrder.customer.address')
            ?? $sale->customer_address
            ?? '-';
        $customerPhone = data_get($sale, 'jobOrder.customer.phone')
            ?? '-';
        $customerCode = data_get($sale, 'jobOrder.customer.code')
            ?? null;

        $paymentLabel = $sale->payment_status === 'cash'
            ? 'Tunai'
            : ($sale->payment_status === 'transfer'
                ? 'Transfer'
                : 'E-Wallet');

        $salesName = data_get($sale, 'jobOrder.labors.0.employee.name')
            ?? $sale->employee
            ?? '-';

        $invoiceNumber = 'INV-' . date('Ymd', strtotime($sale->transaction_date)) . '-' . str_pad($sale->id, 4, '0', STR_PAD_LEFT);
    @endphp

    <div class="center bold">{{ $company['name'] }}</div>
    <div class="center">{{ $company['address'] }}</div>

    <div class="divider"></div>

    @php
        $rawType = $sale->fob_type;
        $typeLabel = '-';
        if ($rawType) {
            switch ($rawType) {
                case 'shipping_point':
                    // Defaultkan ke "Take Away" untuk pengambilan di tempat
                    $typeLabel = 'Take Away';
                    break;
                case 'destination':
                    $typeLabel = 'Delivery';
                    break;
                case 'dine_in':
                    $typeLabel = 'Dine In';
                    break;
                case 'take_away':
                    $typeLabel = 'Take Away';
                    break;
                default:
                    // Untuk nilai custom lain, tampilkan apa adanya
                    $typeLabel = $rawType;
            }
        }
    @endphp

    <div>
        <div>No. Invoice: {{ $invoiceNumber }}</div>
        <div>No. Transaksi: {{ $sale->transaction_number }}</div>
        <div>Tanggal: {{ optional($sale->transaction_date)->format('d/m/Y H:i') }}</div>
        <div>Pelanggan: {{ $customerName }}</div>
        <div>Alamat: {{ $customerAddress }}</div>
        <div>Telp: {{ $customerPhone }}</div>
        @if($customerCode)
            <div>Kode: {{ $customerCode }}</div>
        @endif
        <div>Sales: {{ $salesName }}</div>
        <div>Bayar: {{ $paymentLabel }}</div>
        <div>Tipe: {{ $typeLabel }}</div>
    </div>

    <div class="divider"></div>

    <table>
        @foreach($sale->salesItems as $index => $item)
            <tr>
                <td colspan="2">{{ $index + 1 }}. {{ $item->product->name ?? 'Produk' }}</td>
            </tr>
            <tr>
                <td>{{ number_format($item->quantity, 0) }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    @php
        $subtotal = (float) ($sale->subtotal ?? 0);
        $discountRate = (float) ($sale->discount_rate ?? 0);
        $discountAmount = (float) ($sale->discount_amount ?? 0);
        $fobCost = (float) ($sale->fob_cost ?? 0);
        $ppnRate = (float) ($sale->ppn_rate ?? 0);
        $ppnAmount = (float) ($sale->ppn_amount ?? ($subtotal + $fobCost) * $ppnRate / 100);
        $grandTotal = (float) ($sale->grand_total ?? $sale->total_amount ?? $subtotal - $discountAmount + $fobCost + $ppnAmount);
    @endphp

    <table class="mt-1">
        <tr>
            <td>Subtotal</td>
            <td class="right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($discountAmount > 0)
        <tr>
            <td>Diskon @if($discountRate > 0)({{ number_format($discountRate, 0) }}%)@endif</td>
            <td class="right">-Rp {{ number_format($discountAmount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($fobCost > 0)
        <tr>
            <td>FOB ({{ $typeLabel }})</td>
            <td class="right">Rp {{ number_format($fobCost, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($ppnAmount > 0)
        <tr>
            <td>PPN ({{ number_format($ppnRate, 0) }}%)</td>
            <td class="right">Rp {{ number_format($ppnAmount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
            <td class="bold">TOTAL</td>
            <td class="right bold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if($sale->notes)
        <div class="divider mt-2"></div>
        <div>
            <div class="bold">Catatan:</div>
            <div>{{ $sale->notes }}</div>
        </div>
    @endif

    <div class="divider mt-2"></div>

    <div class="center mt-2">
        Terima kasih
        <br> Selamat Menikmati!
    </div>
</div>
</body>
</html>
