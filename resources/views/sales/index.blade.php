@extends('layouts.app')

@section('title', 'Transaksi Penjualan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
  <!-- Header -->
  <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 mb-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Transaksi Penjualan</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola transaksi penjualan pelanggan dan PPN</p>
      </div>
      <a href="{{ route('sales.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
          <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
        </svg>
        Buat Penjualan
      </a>
    </div>
  </div>

  <!-- Info Cards -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <div class="text-sm text-gray-500 dark:text-gray-400">Total Transaksi</div>
      <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $metrics['total'] ?? 0 }}</div>
    </div>
    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg shadow p-4">
      <div class="text-sm text-green-600 dark:text-green-400">Tunai</div>
      <div class="text-2xl font-bold text-green-800 dark:text-green-200">{{ $metrics['cash'] ?? 0 }}</div>
    </div>
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg shadow p-4">
      <div class="text-sm text-blue-600 dark:text-blue-400">Transfer</div>
      <div class="text-2xl font-bold text-blue-800 dark:text-blue-200">{{ $metrics['transfer'] ?? 0 }}</div>
    </div>
    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg shadow p-4">
      <div class="text-sm text-purple-600 dark:text-purple-400">E-Wallet</div>
      <div class="text-2xl font-bold text-purple-800 dark:text-purple-200">{{ $metrics['ewallet'] ?? 0 }}</div>
    </div>
  </div>

  <!-- Table -->
  <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">No. Penjualan</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pelanggan</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Items</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Metode Layanan</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Metode Pembayaran</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status Approval</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($sales as $sale)
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">
              {{ $sale->transaction_number }}
              @if($sale->job_order_id)
                <br><span class="text-xs text-gray-500">JO: {{ $sale->job_order_id }}</span>
              @endif
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
              {{ $sale->transaction_date->format('d/m/Y H:i') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $sale->customer_name }}</div>
              <div class="text-sm text-gray-500 dark:text-gray-400">{{ $sale->employee }}</div>
            </td>
            <td class="px-6 py-4">
              <div class="text-sm text-gray-900 dark:text-gray-100 max-w-xs">
                @foreach($sale->salesItems as $item)
                  <div class="mb-1">
                    <span class="font-medium">{{ $item->product->name ?? 'Produk dihapus' }}</span>
                    <span class="text-gray-500">({{ $item->quantity }})</span>
                  </div>
                @endforeach
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900 dark:text-white">
              Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              @php($ft = $sale->fob_type ?? 'shipping_point')
              @if($ft === 'destination')
                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                  Diantar
                </span>
              @elseif($ft === 'dine_in')
                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                  Dine In
                </span>
              @else
                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                  Take Away
                </span>
              @endif
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full 
                {{ $sale->payment_status === 'cash' 
                  ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
                  : ($sale->payment_status === 'transfer' 
                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                    : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400') }}">
                {{ $sale->payment_status === 'cash' ? 'Tunai' : ($sale->payment_status === 'transfer' ? 'Transfer' : 'E-Wallet') }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              @if($sale->payment_status === 'cash')
                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                  Approved
                </span>
              @else
                @if($sale->approval_status === 'approved')
                  <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    Approved
                  </span>
                @elseif($sale->approval_status === 'rejected')
                  <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                    Rejected
                  </span>
                @else
                  <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                    Pending
                  </span>
                @endif
              @endif
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div class="flex justify-end items-center gap-3 text-sm font-medium">
                <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank"
                   class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                  Struk
                </a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('sales.show', $sale->id) }}"
                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                  Lihat
                </a>
                @if($sale->payment_status !== 'paid')
                <span class="text-gray-300">|</span>
                <a href="{{ route('sales.edit', $sale->id) }}"
                   class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300">
                  Edit
                </a>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="9" class="px-6 py-12 text-center">
              <div class="flex flex-col items-center">
                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">Belum ada transaksi penjualan</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Klik tombol "Buat Penjualan" untuk menambahkan transaksi baru</p>
                <a href="{{ route('sales.create') }}" class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/>
                  </svg>
                  Buat Penjualan
                </a>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    @if($sales->hasPages())
    <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
      {{ $sales->links() }}
    </div>
    @endif
  </div>
</div>
@endsection