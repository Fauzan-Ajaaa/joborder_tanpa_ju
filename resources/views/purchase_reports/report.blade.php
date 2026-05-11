@extends('layouts.app')

@section('title', 'Laporan Pembelian')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">{{ auth()->user()->nama_perusahaan ?? 'Perusahaan Manufaktur' }}</div>
                    <h1 class="text-2xl font-bold text-gray-900">Hasil Laporan Pembelian</h1>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('purchase_reports.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                    <form action="{{ route('purchase_reports.export_pdf') }}" method="GET" target="_blank">
                        @csrf
                        @foreach(request()->all() as $key => $value)
                            @if($key !== '_token')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            <i class="fas fa-file-pdf mr-2"></i>Export PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-blue-50 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-shopping-cart text-blue-600 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Transaksi</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ $summary['total_transactions'] }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-money-bill-wave text-green-600 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Pembelian</dt>
                                    <dd class="text-lg font-semibold text-gray-900">Rp {{ number_format($summary['total_purchase'], 0, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-box text-purple-600 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Item Dibeli</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ number_format($summary['total_items_purchased'], floor($summary['total_items_purchased']) == $summary['total_items_purchased'] ? 0 : 2, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-orange-50 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chart-line text-orange-600 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Rata-rata Transaksi</dt>
                                    <dd class="text-lg font-semibold text-gray-900">Rp {{ number_format($summary['avg_transaction_value'], 0, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <div class="bg-red-50 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-percentage text-red-600 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Diskon</dt>
                                    <dd class="text-lg font-semibold text-gray-900">Rp {{ number_format($summary['total_discount'], 0, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-50 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-truck text-indigo-600 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Ongkir</dt>
                                    <dd class="text-lg font-semibold text-gray-900">Rp {{ number_format($summary['total_shipping'], 0, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-receipt text-yellow-600 text-2xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total PPN</dt>
                                    <dd class="text-lg font-semibold text-gray-900">Rp {{ number_format($summary['total_tax'], 0, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Table -->
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Detail Transaksi Pembelian</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty/Satuan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Diskon</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ongkir</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">PPN</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($purchases as $purchase)
                            <!-- Transaction Header Row -->
                            <tr class="bg-gray-50/50">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $purchase->purchase_date->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-blue-600">
                                    {{ $purchase->purchase_number }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $purchase->supplier ? $purchase->supplier->name : 'Unknown' }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ $purchase->total_quantity_with_unit }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp. {{ number_format($purchase->average_unit_price, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right"></td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    Rp. {{ number_format($purchase->discount_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    Rp. {{ number_format($purchase->fob_cost, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                    Rp. {{ number_format($purchase->ppn_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">
                                    Rp. {{ number_format($purchase->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $purchase->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $purchase->payment_status === 'paid' ? 'LUNAS' : 'BELUM' }}
                                    </span>
                                </td>
                            </tr>
                            
                            <!-- Transaction Items Rows -->
                            @foreach($purchase->items as $item)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-400"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 pl-8">
                                        - {{ $item->item_name }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-400"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($item->quantity, floor($item->quantity) == $item->quantity ? 0 : 2, ',', '.') }} {{ $item->unit_name }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp. {{ number_format($item->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp. {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-400 text-right"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-400 text-right"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-400 text-right"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-400 text-right"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center text-gray-400">-</td>
                                </tr>
                            @endforeach
                            <!-- Separator for better readability -->
                            <tr class="border-b-2 border-gray-100"><td colspan="11" class="p-0"></td></tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-lg font-medium">Tidak ada data pembelian</span>
                                        <span class="text-sm text-gray-400 mt-1">Pada periode yang dipilih</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($summary['total_transactions'] > 0)
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right text-sm text-gray-900">TOTAL KESELURUHAN:</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($purchases->sum(fn($purchase) => $purchase->items->sum('subtotal')), 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($summary['total_discount'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($summary['total_shipping'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($summary['total_tax'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($summary['total_purchase'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-900">-</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
