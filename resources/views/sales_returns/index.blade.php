@extends('layouts.app')

@section('title', 'Daftar Retur Penjualan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Daftar Retur Penjualan</h1>
            <p class="mt-1 text-sm text-gray-600">Penjualan: {{ $sale->transaction_number }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.show', $sale) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Kembali ke Detail</a>
        </div>
        
        @if(session('success'))
        <div class="mt-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
        @endif
    </div>

    <!-- Info Penjualan -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <span class="text-sm text-blue-600 font-medium">Tanggal Penjualan:</span>
                <p class="text-sm text-gray-900">{{ optional($sale->transaction_date)->format('d F Y') }}</p>
            </div>
            <div>
                <span class="text-sm text-blue-600 font-medium">Pelanggan:</span>
                <p class="text-sm text-gray-900">{{ $sale->customer_name ?? '-' }}</p>
            </div>
            <div>
                <span class="text-sm text-blue-600 font-medium">Total Penjualan:</span>
                <p class="text-sm font-bold text-gray-900">Rp {{ number_format($sale->grand_total ?? $sale->total_amount, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    @if($returns->count() > 0)
    <!-- Daftar Retur -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Riwayat Retur</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $returns->count() }} retur tercatat</p>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($returns as $return)
            <div class="p-6 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-4">
                            <h4 class="text-base font-medium text-gray-900">RET-{{ str_pad($return->id, 4, '0', STR_PAD_LEFT) }}</h4>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ optional($return->return_date)->format('d F Y') }}
                            </span>
                        </div>
                        
                        @if($return->reason)
                        <p class="mt-2 text-sm text-gray-600">
                            <span class="font-medium">Alasan:</span> {{ $return->reason }}
                        </p>
                        @endif

                        <!-- Item Retur -->
                        <div class="mt-3">
                            <div class="text-sm text-gray-700">
                                @foreach($return->items as $item)
                                <div class="flex justify-between items-center py-1">
                                    <span>{{ $item->product->name ?? ('#'.$item->product_id) }}</span>
                                    <span class="font-medium">{{ number_format($item->quantity, 0, ',', '.') }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }} = Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Total Retur -->
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex justify-between items-center">
                                <div class="text-sm">
                                    <span class="text-gray-600">Subtotal: Rp {{ number_format($return->subtotal, 0, ',', '.') }}</span>
                                    @if($return->ppn_amount > 0)
                                    <span class="mx-2 text-gray-400">|</span>
                                    <span class="text-gray-600">PPN: Rp {{ number_format($return->ppn_amount, 0, ',', '.') }}</span>
                                    @endif
                                </div>
                                <div class="text-lg font-bold text-red-600">
                                    Rp {{ number_format($return->grand_total, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ml-4">
                        <a href="{{ route('sales.returns.show', $return) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Detail
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Summary -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-600">Total Retur</p>
                <p class="text-2xl font-bold text-gray-900">{{ $returns->count() }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Total Nilai Retur</p>
                <p class="text-2xl font-bold text-red-600">Rp {{ number_format($returns->sum('grand_total'), 0, ',', '.') }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Sisa Nilai Penjualan</p>
                <p class="text-2xl font-bold text-green-600">Rp {{ number_format(($sale->grand_total ?? $sale->total_amount) - $returns->sum('grand_total'), 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
    @else
    <!-- Empty State -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada retur</h3>
            <p class="mt-1 text-sm text-gray-500">Penjualan ini belum memiliki riwayat retur.</p>
            @if($sale->fob_type === 'dine_in')
            <div class="mt-6">
                <a href="{{ route('sales.returns.create', $sale) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                    Buat Retur Pertama
                </a>
            </div>
            @else
            <p class="mt-3 text-sm text-gray-500 italic">Retur hanya tersedia untuk penjualan dengan metode layanan "Dine In"</p>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
