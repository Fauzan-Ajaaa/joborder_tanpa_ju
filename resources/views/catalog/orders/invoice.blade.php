@extends('layouts.catalog')

@section('title', 'Invoice Pesanan')

@section('content')
<div class="min-h-screen bg-white py-6">
    <div class="max-w-3xl mx-auto bg-white shadow-md rounded-xl border border-gray-200 p-8">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">INVOICE</h1>
                <p class="text-sm text-gray-500">UMKM Desa!</p>
            </div>
            <div class="text-right text-sm text-gray-700">
                <p><span class="font-semibold">No. Invoice:</span> {{ $sale->transaction_number }}</p>
                <p><span class="font-semibold">Tanggal:</span> {{ optional($sale->transaction_date)->format('d/m/Y H:i') }}</p>
                <p><span class="font-semibold">Kode Pesanan:</span> {{ $jobOrder->kode_job }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 text-sm">
            <div>
                <h2 class="font-semibold text-gray-800 mb-2">Kepada Yth.</h2>
                <p class="text-gray-900">{{ $jobOrder->customer_name }}</p>
                <p class="text-gray-700">Telp: {{ $jobOrder->customer_phone }}</p>
                @if($jobOrder->customer_address)
                    <p class="text-gray-700">{{ $jobOrder->customer_address }}</p>
                @endif
            </div>
            <div class="md:text-right">
                <h2 class="font-semibold text-gray-800 mb-2">Informasi Pembayaran</h2>
                <p class="text-gray-700"><span class="font-medium">Metode:</span> {{ strtoupper($jobOrder->payment_method) }}</p>
                <p class="text-gray-700"><span class="font-medium">Dibayar:</span> {{ optional($jobOrder->paid_at)->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="mb-8">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Detail Pesanan</h2>
            <table class="w-full text-sm border-2 border-gray-300">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="text-left py-3 px-3 font-semibold">Produk</th>
                        <th class="text-center py-3 px-3 font-semibold">Qty</th>
                        <th class="text-right py-3 px-3 font-semibold">Harga Satuan</th>
                        <th class="text-right py-3 px-3 font-semibold">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->salesItems as $item)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="py-3 px-3 font-medium">{{ $item->product->name ?? 'Produk' }}</td>
                            <td class="py-3 px-3 text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @php
            // Hitung subtotal dari sales items
            $subtotal = 0;
            foreach($sale->salesItems as $item) {
                $subtotal += (float) ($item->subtotal ?? 0);
            }
            $ongkir = (float) ($sale->fob_cost ?? 0);
            $ppn = (float) ($sale->ppn_amount ?? 0);
            $total = (float) ($sale->grand_total ?? $sale->total_amount ?? ($subtotal + $ongkir + $ppn));
            $cashAmount = (float) ($jobOrder->cash_amount ?? 0);
            $change = $cashAmount > 0 ? $cashAmount - $total : 0;
        @endphp

        <div class="flex justify-end mb-8">
            <div class="w-full md:w-1/2 bg-gray-50 rounded-lg p-4 border-2 border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Ringkasan Pembayaran</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Subtotal Produk:</span>
                        <span class="font-medium">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($ongkir > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Ongkos Kirim:</span>
                        <span class="font-medium">Rp {{ number_format($ongkir, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($ppn > 0)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">PPN:</span>
                        <span class="font-medium">Rp {{ number_format($ppn, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center pt-3 border-t-2 border-gray-300">
                        <span class="font-bold text-gray-900">Total Pembayaran:</span>
                        <span class="font-bold text-xl text-amber-700">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                    
                    @if($jobOrder->payment_method === 'cash')
                    <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                        <span class="text-gray-600">Tunai Diterima:</span>
                        <span class="font-medium">Rp {{ number_format($cashAmount, 0, ',', '.') }}</span>
                    </div>
                    @if($change >= 0)
                    <div class="flex justify-between items-center bg-green-100 p-3 rounded-lg mt-2">
                        <span class="text-green-700 font-bold">Kembalian:</span>
                        <span class="font-bold text-green-700 text-lg">Rp {{ number_format($change, 0, ',', '.') }}</span>
                    </div>
                    @else
                    <div class="flex justify-between items-center bg-red-100 p-3 rounded-lg mt-2">
                        <span class="text-red-700 font-bold">Kurang:</span>
                        <span class="font-bold text-red-700 text-lg">Rp {{ number_format(abs($change), 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center text-xs text-gray-500">
            <p>Invoice ini sah dan diterbitkan secara elektronik.</p>
            <button onclick="window.print()" class="inline-flex items-center px-3 py-1.5 rounded-full bg-gray-900 text-white font-semibold text-xs hover:bg-gray-800">
                <i class="fas fa-print mr-1"></i>Cetak Invoice
            </button>
        </div>
    </div>
</div>
@endsection
