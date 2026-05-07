@extends('layouts.app')

@section('title', 'Detail Retur Penjualan')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Detail Retur Penjualan</h1>
    <div class="flex items-center gap-2">
      <a href="{{ route('sales-returns.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Kembali ke Daftar Retur</a>
      <a href="{{ route('sales.show', $salesReturn->salesTransaction) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">Lihat Penjualan</a>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-800 shadow rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Informasi Retur</h2>
    </div>
    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Nomor Penjualan</div>
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $salesReturn->salesTransaction->transaction_number }}</div>
      </div>
      <div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Tanggal Retur</div>
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ optional($salesReturn->return_date)->format('d F Y') }}</div>
      </div>
      @if($salesReturn->reason)
      <div class="md:col-span-2">
        <div class="text-sm text-gray-500 dark:text-gray-400">Alasan</div>
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $salesReturn->reason }}</div>
      </div>
      @endif
    </div>
  </div>

  <div class="bg-white dark:bg-gray-800 shadow rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Item Retur</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700/40">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Produk</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Qty</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Harga</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Subtotal</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @foreach($salesReturn->items as $item)
          <tr>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ $item->product->name ?? ('#'.$item->product_id) }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">{{ number_format($item->quantity, 0, ',', '.') }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-800 shadow rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Ringkasan Biaya</h2>
    </div>
    <div class="px-6 py-4">
      <div class="max-w-md ml-auto space-y-2">
        <div class="flex justify-between text-sm">
          <span class="text-gray-600 dark:text-gray-300">Subtotal Retur:</span>
          <span class="font-medium text-gray-900 dark:text-gray-100">Rp {{ number_format($salesReturn->subtotal, 0, ',', '.') }}</span>
        </div>
        @if($salesReturn->ppn_amount > 0)
        <div class="flex justify-between text-sm">
          <span class="text-gray-600 dark:text-gray-300">PPN ({{ $salesReturn->salesTransaction->ppn_rate ?? 11 }}%):</span>
          <span class="font-medium text-gray-900 dark:text-gray-100">Rp {{ number_format($salesReturn->ppn_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="flex justify-between text-lg font-bold border-t pt-2">
          <span class="text-gray-900 dark:text-gray-100">TOTAL RETUR:</span>
          <span class="text-red-600">Rp {{ number_format($salesReturn->grand_total, 0, ',', '.') }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Info Saldo Penjualan -->
  <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
    <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-4">Informasi Saldo Penjualan</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
        <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">Total Penjualan Awal</div>
        <div class="text-xl font-bold text-blue-900 dark:text-blue-100">Rp {{ number_format($salesReturn->salesTransaction->grand_total ?? $salesReturn->salesTransaction->total_amount, 0, ',', '.') }}</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-red-200 dark:border-red-700">
        <div class="text-sm text-red-600 dark:text-red-400 font-medium">Total Retur</div>
        <div class="text-xl font-bold text-red-900 dark:text-red-100">Rp {{ number_format($salesReturn->grand_total, 0, ',', '.') }}</div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-green-200 dark:border-green-700">
        <div class="text-sm text-green-600 dark:text-green-400 font-medium">Sisa Saldo Penjualan</div>
        <div class="text-xl font-bold text-green-900 dark:text-green-100">Rp {{ number_format(($salesReturn->salesTransaction->grand_total ?? $salesReturn->salesTransaction->total_amount) - $salesReturn->grand_total, 0, ',', '.') }}</div>
      </div>
    </div>
    
    @if($salesReturn->salesTransaction->salesReturns && $salesReturn->salesTransaction->salesReturns->count() > 1)
    <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
      <div class="text-sm text-amber-800 dark:text-amber-200">
        <strong>Catatan:</strong> Penjualan ini memiliki {{ $salesReturn->salesTransaction->salesReturns->count() }} retur total. 
        Total semua retur: Rp {{ number_format($salesReturn->salesTransaction->salesReturns->sum('grand_total'), 0, ',', '.') }}
      </div>
    </div>
    @endif
  </div>
</div>
@endsection