@extends('layouts.app')

@section('title', 'Daftar Faktur Penjualan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Faktur Penjualan</h1>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Faktur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                INV-{{ date('Ymd', strtotime($invoice->transaction_date)) }}-{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ date('d/m/Y', strtotime($invoice->transaction_date)) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $invoice->customer_name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $invoice->payment_status == 'cash' ? 'bg-green-100 text-green-800' : 
                                       ($invoice->payment_status == 'transfer' ? 'bg-blue-100 text-blue-800' : 
                                       'bg-purple-100 text-purple-800') }}">
                                    {{ $invoice->payment_status == 'cash' ? 'Tunai' : 
                                       ($invoice->payment_status == 'transfer' ? 'Transfer' : 'E-Wallet') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center" style="width: 200px;">
                                <div style="display: inline-block;">
                                    <a href="{{ route('invoices.show', $invoice->id) }}" 
                                       style="display: inline-block; margin: 2px; padding: 6px 12px; font-size: 12px; font-weight: 500; color: white; background-color: #3b82f6; border-radius: 4px; text-decoration: none;">
                                        Lihat
                                    </a>
                                    <a href="{{ route('invoices.print', $invoice->id) }}" 
                                       style="display: inline-block; margin: 2px; padding: 6px 12px; font-size: 12px; font-weight: 500; color: white; background-color: #10b981; border-radius: 4px; text-decoration: none;"
                                       target="_blank">
                                        Cetak
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                Belum ada faktur penjualan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="mt-4">
                {{ $invoices->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
