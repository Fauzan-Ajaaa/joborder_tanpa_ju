@extends('layouts.catalog')

@section('title', 'Struk Pesanan')

@section('content')
<div class="min-h-screen bg-white py-6">
    <div class="max-w-md mx-auto bg-white shadow-md rounded-xl border border-amber-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-lg font-bold text-amber-800">Struk Pesanan</h1>
                <p class="text-xs text-gray-500">UMKM Desa!</p>
            </div>
            <div class="text-right text-xs text-gray-500">
                <p>No. Penjualan</p>
                <p class="font-semibold text-gray-800">{{ $sale->transaction_number }}</p>
                <p class="mt-1">Tanggal: {{ optional($sale->transaction_date)->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="border-t border-b border-dashed border-gray-300 py-3 mb-3 text-xs text-gray-700">
            <p><span class="font-semibold">Kode Pesanan:</span> {{ $jobOrder->kode_job }}</p>
            <p><span class="font-semibold">Nama:</span> {{ $jobOrder->customer_name }}</p>
            <p><span class="font-semibold">Telepon:</span> {{ $jobOrder->customer_phone }}</p>
            @if($jobOrder->customer_address)
                <p><span class="font-semibold">Alamat:</span> {{ $jobOrder->customer_address }}</p>
            @endif
            <p class="mt-2"><span class="font-semibold">Metode Pembayaran:</span> {{ strtoupper($jobOrder->payment_method) }}</p>
            <p><span class="font-semibold">Status:</span> 
                @if(strtolower($jobOrder->status) === 'paid')
                    <span class="text-green-600">✓ Sudah Dibayar</span>
                @else
                    <span class="text-yellow-600">⏰ Menunggu Pembayaran</span>
                @endif
            </p>
        </div>

        <div class="mb-4">
            <h2 class="text-sm font-bold text-gray-800 mb-2">Detail Pesanan</h2>
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b-2 border-gray-300 bg-gray-50">
                        <th class="text-left py-2 px-1">Produk</th>
                        <th class="text-center py-2 px-1">Qty</th>
                        <th class="text-right py-2 px-1">Harga</th>
                        <th class="text-right py-2 px-1">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->salesItems as $item)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-2 px-1 font-medium">
                                {{ $item->product->name ?? 'Produk' }}
                            </td>
                            <td class="py-2 px-1 text-center">
                                {{ number_format($item->quantity, 0, ',', '.') }}
                            </td>
                            <td class="py-2 px-1 text-right">
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="py-2 px-1 text-right font-semibold">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
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

        <div class="border-t-2 border-b-2 border-gray-300 py-3 mb-3">
            <h3 class="text-sm font-bold text-gray-800 mb-3">Ringkasan Pembayaran</h3>
            <div class="space-y-2 text-sm">
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
                <div class="flex justify-between items-center pt-2 border-t border-dashed border-gray-300">
                    <span class="font-bold text-gray-900">Total Pembayaran:</span>
                    <span class="font-bold text-lg text-amber-700">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
                
                @if($jobOrder->payment_method === 'cash')
                <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                    <span class="text-gray-600">Tunai Diterima:</span>
                    <span class="font-medium">Rp {{ number_format($cashAmount, 0, ',', '.') }}</span>
                </div>
                @if($change >= 0)
                <div class="flex justify-between items-center bg-green-50 p-2 rounded">
                    <span class="text-green-700 font-bold">Kembalian:</span>
                    <span class="font-bold text-green-700">Rp {{ number_format($change, 0, ',', '.') }}</span>
                </div>
                @else
                <div class="flex justify-between items-center bg-red-50 p-2 rounded">
                    <span class="text-red-700 font-bold">Kurang:</span>
                    <span class="font-bold text-red-700">Rp {{ number_format(abs($change), 0, ',', '.') }}</span>
                </div>
                @endif
                @endif
            </div>
        </div>

        <div class="mt-4 flex justify-between items-center text-xs text-gray-500">
            <p>Terima kasih telah berbelanja!</p>
            <button onclick="window.print()" class="inline-flex items-center px-3 py-1.5 rounded-full bg-amber-600 text-white font-semibold text-xs hover:bg-amber-700">
                <i class="fas fa-print mr-1"></i>Cetak
            </button>
        </div>
    </div>
</div>
@endsection
