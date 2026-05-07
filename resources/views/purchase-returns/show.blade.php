@extends('layouts.app')

@section('title', 'Detail Retur Pembelian')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Retur #{{ $purchaseReturn->return_number }}</h1>
            <p class="mt-1 text-sm text-gray-600">Detail retur pembelian bahan baku</p>
        </div>
        <div class="flex gap-2">
            @if($purchaseReturn->status === 'pending')
                <form action="{{ route('purchase-returns.approve', $purchaseReturn) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-green-700 shadow-md" onclick="return confirm('Approve retur ini? Stok bahan baku akan dikurangi.')">
                        Approve Retur
                    </button>
                </form>
            @endif
            <a href="{{ route('purchase-returns.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-gray-700 shadow-md">Kembali</a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Informasi Retur</h3>
            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                @if($purchaseReturn->status == 'pending') bg-yellow-100 text-yellow-800
                @elseif($purchaseReturn->status == 'approved' || $purchaseReturn->status == 'completed') bg-green-100 text-green-800
                @else bg-gray-100 text-gray-800
                @endif">
                {{ ucfirst($purchaseReturn->status) }}
            </span>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">No Retur</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ $purchaseReturn->return_number }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tanggal Retur</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchaseReturn->return_date->format('d F Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Purchase Order</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchaseReturn->purchase?->purchase_number }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchaseReturn->purchase?->supplier?->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Alasan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $purchaseReturn->reason)) }}</dd>
                </div>
                @if($purchaseReturn->notes)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Catatan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchaseReturn->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Item yang Diretur</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $purchaseReturn->purchase?->purchase_type === 'auxiliary_material' ? 'Bahan Penolong' : 'Bahan Baku' }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty Retur</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal Retur</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($purchaseReturn->items as $item)
                        @php
                            $qtyVal = (float) $item->quantity;
                            $qtyDisplay = $qtyVal == floor($qtyVal) ? (string) (int) $qtyVal : number_format($qtyVal, 2, ',', '.');
                        @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->item_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $qtyDisplay }} {{ $item->unit ?? $item->display_unit }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg max-w-md ml-auto">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Ringkasan Retur</h3>
        </div>
        <div class="px-6 py-4">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Total Retur (tanpa PPN & FOB):</span>
                <span class="font-bold text-red-600">Rp {{ number_format($purchaseReturn->total_return_amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
