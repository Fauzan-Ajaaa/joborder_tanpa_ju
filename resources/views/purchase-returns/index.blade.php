@extends('layouts.app')

@section('title', 'Retur Pembelian Bahan Baku')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Retur Pembelian Bahan Baku</h1>
            <p class="mt-1 text-sm text-gray-600">Daftar retur pembelian bahan baku</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-white rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 shadow-md">
            Kembali ke Pembelian
        </a>
    </div>

    <div class="bg-white shadow overflow-x-auto sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Retur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Retur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($returns as $return)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $return->return_number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $return->return_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $return->purchase?->purchase_number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $return->purchase?->supplier?->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">Rp {{ number_format($return->total_return_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($return->status == 'pending') bg-yellow-100 text-yellow-800
                                @elseif($return->status == 'approved' || $return->status == 'completed') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($return->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('purchase-returns.show', $return) }}" class="text-blue-600 hover:text-blue-900">Lihat</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-sm text-gray-500">Belum ada data retur pembelian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($returns->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $returns->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
