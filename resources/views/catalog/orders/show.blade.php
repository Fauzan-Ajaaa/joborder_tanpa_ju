@extends('layouts.catalog')

@section('title', 'Detail Pesanan')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-amber-50 to-orange-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-amber-800">Detail Pesanan</h1>
                <p class="text-sm text-amber-600">Ringkasan pesanan dan status pembayaran</p>
            </div>
            <a href="{{ route('catalog.orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 rounded-lg font-medium text-amber-700 hover:bg-amber-50 transition-colors duration-200">
                <i class="fas fa-list mr-2"></i>Riwayat Pesanan
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-md border border-amber-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-amber-100 flex justify-between items-center">
                <div>
                    <p class="text-xs text-gray-500">Kode Pesanan</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $jobOrder->kode_job }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500">Tanggal</p>
                    <p class="text-sm font-semibold text-gray-900">{{ optional($jobOrder->order_date)->format('d/m/Y H:i') ?? '-' }}</p>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-amber-100 flex justify-between items-center">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Status Pembayaran</p>
                    @php $status = strtolower($jobOrder->status); @endphp
                    @if($status === 'draft')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>Belum Bayar
                        </span>
                    @elseif($status === 'paid')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Sudah Bayar
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                            {{ ucfirst($status) }}
                        </span>
                    @endif
                </div>
                <div class="text-right text-sm text-gray-600">
                    <p><span class="font-semibold">Metode:</span> {{ $jobOrder->payment_method ? strtoupper($jobOrder->payment_method) : '-' }}</p>
                    <p><span class="font-semibold">Dibayar:</span> {{ $jobOrder->paid_at ? $jobOrder->paid_at->format('d/m/Y H:i') : '-' }}</p>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-amber-100">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Detail Produk</h2>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-lg bg-amber-50 flex items-center justify-center overflow-hidden border border-amber-100 flex-shrink-0">
                        @if(optional($jobOrder->product)->has_image)
                            <img src="{{ route('products.image', $jobOrder->product) }}" alt="{{ $jobOrder->product->name }}" class="w-full h-full object-cover">
                        @else
                            <i class="fas fa-cookie-bite text-amber-300 text-2xl"></i>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $jobOrder->product->name ?? '-' }}</p>
                        <p class="text-xs text-gray-500 mb-1">{{ $jobOrder->product->code ?? '-' }}</p>
                        <p class="text-xs text-gray-600">Jumlah: <span class="font-semibold">{{ number_format($jobOrder->quantity, 0, ',', '.') }}</span></p>
                    </div>
                    @php
                        $firstSale = $jobOrder->salesTransactions->first() ?? null;
                        if ($firstSale) {
                            $subtotalProduk = (float) ($firstSale->subtotal ?? 0);
                            $ongkir = (float) ($firstSale->fob_cost ?? 0);
                            $ppn = (float) ($firstSale->ppn_amount ?? 0);
                            $totalPembayaran = (float) ($firstSale->grand_total ?? $firstSale->total_amount ?? ($subtotalProduk + $ongkir + $ppn));
                        } else {
                            // Fallback kalau belum ada transaksi penjualan (harusnya jarang terjadi untuk pesanan katalog)
                            $subtotalProduk = (float) ($jobOrder->total_cost ?? 0);
                            $ongkir = 0;
                            $ppn = 0;
                            $totalPembayaran = $subtotalProduk;
                        }
                    @endphp
                    <div class="text-right text-xs text-gray-700 space-y-0.5">
                        <p><span class="font-medium">Subtotal Produk:</span> Rp {{ number_format($subtotalProduk, 0, ',', '.') }}</p>
                        <p><span class="font-medium">Ongkos Kirim:</span> Rp {{ number_format($ongkir, 0, ',', '.') }}</p>
                        <p><span class="font-medium">PPN:</span> Rp {{ number_format($ppn, 0, ',', '.') }}</p>
                        <p class="mt-1 text-sm">
                            <span class="text-gray-600">Total Pembayaran:</span>
                            <span class="text-lg font-bold text-amber-700">Rp {{ number_format($totalPembayaran, 0, ',', '.') }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-amber-100">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Informasi Penerima</h2>
                <div class="space-y-1 text-sm text-gray-700">
                    <p><span class="font-semibold">Nama:</span> {{ $jobOrder->customer_name }}</p>
                    <p><span class="font-semibold">Telepon:</span> {{ $jobOrder->customer_phone }}</p>
                    @if($jobOrder->customer_address)
                        <p><span class="font-semibold">Alamat:</span> {{ $jobOrder->customer_address }}</p>
                    @endif
                    @if($jobOrder->notes)
                        <p><span class="font-semibold">Catatan:</span> {{ $jobOrder->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="px-6 py-4 flex justify-between items-center bg-amber-50/70">
                <div class="flex items-center gap-2">
                    <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 rounded-lg text-sm font-medium text-amber-700 hover:bg-amber-50">
                        <i class="fas fa-store mr-2"></i>Belanja Lagi
                    </a>
                    <a href="{{ route('catalog.orders.index') }}#order-{{ $jobOrder->id }}" class="inline-flex items-center px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-semibold hover:bg-amber-700">
                        <i class="fas fa-list mr-2"></i>Lihat Semua Pesanan
                    </a>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('catalog.orders.receipt', $jobOrder) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-white border border-amber-300 rounded-lg text-xs font-semibold text-amber-700 hover:bg-amber-50">
                        <i class="fas fa-receipt mr-1"></i>Cetak Struk
                    </a>
                    <a href="{{ route('catalog.orders.invoice', $jobOrder) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-amber-700 text-white rounded-lg text-xs font-semibold hover:bg-amber-800">
                        <i class="fas fa-file-invoice mr-1"></i>Cetak Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
