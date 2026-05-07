@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">{{ auth()->user()->nama_perusahaan ?? 'Perusahaan Manufaktur' }}</div>
                    <h1 class="text-2xl font-bold text-gray-900">Hasil Laporan Penjualan</h1>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('sales_reports.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                    <form action="{{ route('sales_reports.export_pdf') }}" method="GET" target="_blank">
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Pendapatan</dt>
                                    <dd class="text-lg font-semibold text-gray-900">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</dd>
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Item Terjual</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ $summary['total_items_sold'] }}</dd>
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Ongkir (FOB)</dt>
                                    <dd class="text-lg font-semibold text-gray-900">Rp {{ number_format($summary['total_fob'], 0, ',', '.') }}</dd>
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
                                    <dd class="text-lg font-semibold text-gray-900">Rp {{ number_format($summary['total_ppn'], 0, ',', '.') }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Detail Transaksi Penjualan</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Diskon</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ongkir</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">PPN</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($sales as $sale)
                            @foreach($sale->items as $index => $item)
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $sale->transaction_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $sale->transaction_number }}
                                        @if($index === 0 && $sale->items->count() > 1)
                                            <br><span class="text-xs text-gray-500">({{ $sale->items->count() }} produk)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $sale->customer ? $sale->customer->name : $sale->customer_name }}
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        {{ $item->product ? $item->product->name : 'Unknown' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($item->quantity, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp. {{ number_format($item->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp. {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        @if($index === 0) Rp. {{ number_format($sale->discount_amount, 0, ',', '.') }} @else - @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        @if($index === 0) Rp. {{ number_format($sale->fob_cost, 0, ',', '.') }} @else - @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        @if($index === 0) Rp. {{ number_format($sale->ppn_amount, 0, ',', '.') }} @else - @endif
                                    </td>
                                    @if($index === 0)
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right" rowspan="{{ $sale->items->count() }}">
                                            <strong>Rp. {{ number_format($sale->total_amount, 0, ',', '.') }}</strong>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="12" class="px-6 py-8 text-center text-gray-500">Tidak ada data penjualan pada periode yang dipilih</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($summary['total_transactions'] > 0)
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="6" class="px-4 py-3 text-right text-sm text-gray-900">TOTAL KESELURUHAN:</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($sales->sum(fn($s) => $s->items->sum('subtotal')), 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($summary['total_discount'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($summary['total_fob'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($summary['total_ppn'], 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-900">Rp. {{ number_format($summary['total_revenue'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
