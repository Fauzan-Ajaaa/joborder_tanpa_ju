@extends('layouts.app')

@section('title', 'Detail Penjualan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Detail Penjualan</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Kembali</a>
            @if($sale->salesReturns->isEmpty() && $sale->fob_type === 'dine_in')
            <a href="{{ route('sales.returns.create', $sale) }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700">Retur Penjualan</a>
            @endif
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Informasi Penjualan</h3>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nomor Penjualan</dt>
                    <dd class="mt-1 text-sm font-bold text-blue-600">{{ $sale->transaction_number }}</dd>
                </div>
                @if($sale->job_order_id)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Job Order Terkait</dt>
                    <dd class="mt-1">
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-purple-900">{{ $sale->jobOrder->kode_job ?? 'Job Order #' . $sale->job_order_id }}</p>
                                </div>
                            </div>
                        </div>
                    </dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tanggal Penjualan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ optional($sale->transaction_date)->format('d F Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nama Pelanggan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $sale->customer_name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Karyawan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $sale->employee ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Opsi Pengiriman</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @php($ft = $sale->fob_type ?? 'shipping_point')
                        @if($ft === 'destination')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Diantar (Biaya Pengiriman dibayar pembeli)</span>
                        @elseif($ft === 'dine_in')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Dine In</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Take Away</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Metode Pembayaran</dt>
                    <dd class="mt-1">
                        @if($sale->payment_method === 'cash')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Tunai</span>
                        @elseif($sale->payment_method === 'transfer')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Transfer Bank</span>
                        @elseif($sale->payment_method === 'ewallet')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">E-Wallet</span>
                        @elseif($sale->payment_method === 'cod')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">COD (Cash on Delivery)</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">-</span>
                        @endif
                    </dd>
                </div>
                
                <!-- Bukti Pembayaran (jika ada) -->
                @if($sale->payment_proof)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Bukti Pembayaran</dt>
                    <dd class="mt-1">
                        <a href="{{ route('sales.payment-proof', $sale) }}" 
                           target="_blank"
                           class="inline-flex items-center px-3 py-2 border border-blue-300 rounded-md text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Lihat Bukti Pembayaran
                        </a>
                    </dd>
                </div>
                @endif
                
                @if($sale->salesReturns && $sale->salesReturns->count() > 0)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Riwayat Retur</dt>
                    <dd class="mt-1">
                        <div class="space-y-2">
                            @foreach($sale->salesReturns as $return)
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-amber-900">RET-{{ str_pad($return->id, 4, '0', STR_PAD_LEFT) }}</p>
                                        <p class="text-sm text-gray-600">{{ optional($return->return_date)->format('d F Y') }} - {{ $return->reason ?? '-' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-red-600">Rp {{ number_format($return->grand_total, 0, ',', '.') }}</p>
                                        <a href="{{ route('sales.returns.show', $return) }}" class="text-xs text-blue-600 hover:text-blue-800">Detail</a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    @if($sale->salesItems && $sale->salesItems->count() > 0)
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Item Penjualan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($sale->salesItems as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($item->product_id && $item->product)
                                {{ $item->product->name }}
                            @elseif($item->product_id)
                                <span class="text-red-600">Produk #{{ $item->product_id }} (tidak ditemukan)</span>
                            @else
                                <span class="text-red-600">Produk tidak ditentukan</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Ringkasan Biaya -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Ringkasan Biaya</h3>
        </div>
        <div class="px-6 py-4">
            <div class="max-w-md ml-auto space-y-2">

                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-medium">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">Diskon:</span>
                    <span class="font-medium text-red-600">
                        @if($sale->discount_amount > 0)
                            Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </span>
                </div>
                
                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">Subtotal Setelah Diskon:</span>
                    <span class="font-medium">Rp {{ number_format(($sale->subtotal ?? 0) - ($sale->discount_amount ?? 0), 0, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">Biaya Pengiriman:</span>
                    <span class="font-medium">
                        @if($sale->fob_cost > 0)
                            Rp {{ number_format((float)$sale->fob_cost, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </span>
                </div>
                
                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">PPN:</span>
                    <span class="font-medium">
                        @if($sale->ppn_amount > 0)
                            Rp {{ number_format($sale->ppn_amount, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </span>
                </div>
                
                <div class="flex justify-between text-lg font-bold pt-3 border-t-2 border-gray-300">
                    <span class="text-gray-900">TOTAL:</span>
                    <span class="text-blue-600">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
