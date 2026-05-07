@extends('layouts.catalog')

@section('title', 'Riwayat Pesanan')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-amber-50 to-orange-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-amber-800">Riwayat Pesanan</h1>
                <p class="text-sm text-amber-600">Daftar pesanan yang pernah dibuat di katalog ini</p>
            </div>
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 rounded-lg font-medium text-amber-700 hover:bg-amber-50 transition-colors duration-200">
                <i class="fas fa-store mr-2"></i>Kembali ke Katalog
            </a>
        </div>

        @if($orders->isEmpty())
            <div class="bg-white rounded-2xl shadow-md p-8 text-center border border-dashed border-amber-200">
                <i class="fas fa-receipt text-4xl text-amber-300 mb-3"></i>
                <h2 class="text-lg font-semibold text-amber-800 mb-1">Belum ada pesanan</h2>
                <p class="text-sm text-amber-600 mb-4">Mulai pesan produk favoritmu dari katalog.</p>
                <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-amber-600 to-amber-700 text-white rounded-lg font-semibold hover:from-amber-700 hover:to-amber-800 transition-all duration-200">
                    <i class="fas fa-shopping-basket mr-2"></i>Lihat Katalog
                </a>
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-amber-100">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-amber-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-amber-800 uppercase tracking-wider">Kode Pesanan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-amber-800 uppercase tracking-wider">Produk</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-amber-800 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-amber-800 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-amber-800 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-amber-800 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($orders as $order)
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                    {{ $order->kode_job }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800">
                                    {{ $order->product->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-center text-gray-700">
                                    {{ optional($order->order_date)->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    @php
                                        $status = strtolower($order->status);
                                    @endphp
                                    @if($status === 'draft')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Belum Bayar</span>
                                    @elseif($status === 'paid')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Sudah Bayar</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">{{ ucfirst($status) }}</span>
                                    @endif
                                </td>
                                @php
                                    $firstSale = $order->salesTransactions->first() ?? null;
                                    $totalDisplay = $firstSale
                                        ? (float) ($firstSale->grand_total ?? $firstSale->total_amount ?? 0)
                                        : (float) ($order->total_cost ?? 0);
                                @endphp
                                <td class="px-4 py-3 text-sm text-right font-semibold text-amber-800">
                                    Rp {{ number_format($totalDisplay, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <a href="{{ route('catalog.orders.show', $order) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 hover:bg-amber-200">
                                        <i class="fas fa-receipt mr-1"></i>Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
