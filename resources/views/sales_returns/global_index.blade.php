@extends('layouts.app')

@section('title', 'Daftar Retur Penjualan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Daftar Retur Penjualan</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Semua retur penjualan dari semua transaksi</p>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <div class="text-sm text-gray-500 dark:text-gray-400">Total Retur</div>
      <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $returns->total() }}</div>
    </div>
    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow p-4">
      <div class="text-sm text-green-600 dark:text-green-400">Total Nilai Retur</div>
      <div class="text-2xl font-bold text-green-800 dark:text-green-200">Rp {{ number_format($returns->sum('grand_total'), 0, ',', '.') }}</div>
    </div>
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow p-4">
      <div class="text-sm text-blue-600 dark:text-blue-400">Retur Bulan Ini</div>
      <div class="text-2xl font-bold text-blue-800 dark:text-blue-200">{{ $returns->where('return_date', '>=', now()->startOfMonth())->count() }}</div>
    </div>
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg shadow p-4">
      <div class="text-sm text-amber-600 dark:text-amber-400">Rata-rata Nilai</div>
      <div class="text-2xl font-bold text-amber-800 dark:text-amber-200">Rp {{ number_format($returns->avg('grand_total'), 0, ',', '.') }}</div>
    </div>
  </div>

  @if($returns->count() > 0)
  <!-- Daftar Retur -->
  <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Riwayat Retur</h3>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kode Retur</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Penjualan</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pelanggan</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Alasan</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
            <th class="relative px-6 py-3 text-right">
              <span class="sr-only">Aksi</span>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @foreach($returns as $return)
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100">RET-{{ str_pad($return->id, 4, '0', STR_PAD_LEFT) }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900 dark:text-gray-100">
                <a href="{{ route('sales.show', $return->sales_transaction_id) }}" class="text-blue-600 hover:text-blue-900">
                  {{ $return->salesTransaction->transaction_number ?? '#' . $return->sales_transaction_id }}
                </a>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900 dark:text-gray-100">{{ optional($return->return_date)->format('d M Y') }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-gray-900 dark:text-gray-100">{{ $return->salesTransaction->customer_name ?? '-' }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900 dark:text-gray-100">{{ $return->reason ?: '-' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right">
              <div class="text-sm font-medium text-red-600 dark:text-red-400">Rp {{ number_format($return->grand_total, 0, ',', '.') }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <a href="{{ route('sales.returns.show', $return) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="Lihat Detail">
                Detail
              </a>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    
    <!-- Pagination -->
    <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
      {{ $returns->links() }}
    </div>
  </div>
  @else
  <!-- Empty State -->
  <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
    <div class="px-6 py-12 text-center">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Belum ada retur</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada transaksi retur penjualan.</p>
    </div>
  </div>
  @endif
</div>
@endsection
