@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
  <div class="bg-white dark:bg-gray-800 shadow rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div>
          <div class="text-sm text-gray-500 dark:text-gray-400">Formulir</div>
          <h1 class="mt-0.5 text-lg font-semibold text-gray-900 dark:text-gray-100">Retur Penjualan</h1>
          <div class="mt-1 inline-flex items-center gap-2">
            <span class="px-2.5 py-0.5 text-xs rounded-full bg-indigo-600/10 text-indigo-700 dark:text-indigo-300 ring-1 ring-inset ring-indigo-600/20">{{ $sale->transaction_number }}</span>
          </div>
        </div>
        <a href="{{ route('sales.show', $sale) }}" class="text-sm px-3 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">Kembali</a>
      </div>
    </div>

    <form method="POST" action="{{ route('sales.returns.store', $sale) }}" class="px-6 py-6 space-y-8">
      @csrf

      <section>
        <h2 class="text-sm font-medium text-gray-700 dark:text-gray-200">Informasi Retur</h2>
        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm text-gray-600 dark:text-gray-300">Tanggal Retur</label>
            <input type="date" name="return_date" value="{{ date('Y-m-d') }}" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm text-gray-600 dark:text-gray-300">Alasan</label>
            <textarea name="reason" rows="2" placeholder="Opsional" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tambahkan catatan singkat jika diperlukan.</p>
          </div>
        </div>
      </section>

      <section>
        <h2 class="text-sm font-medium text-gray-700 dark:text-gray-200">Item Retur</h2>
        <div class="mt-3 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/40">
              <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-gray-600 dark:text-gray-300 w-2/5">Produk</th>
                <th class="px-5 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 dark:text-gray-300 w-1/6">Qty Jual</th>
                <th class="px-5 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 dark:text-gray-300 w-1/6">Qty Retur</th>
                <th class="px-5 py-3 text-right text-xs font-semibold tracking-wider text-gray-600 dark:text-gray-300 w-1/5">Harga Satuan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
              @foreach($sale->salesItems as $i => $si)
              @php
                  $discountRate = (float)($sale->discount_rate ?? 0);
                  // Harga satuan = setelah diskon, BELUM termasuk PPN
                  $unitPriceAfterDiscount = $discountRate > 0
                      ? round($si->unit_price * (1 - $discountRate / 100), 2)
                      : (float)$si->unit_price;
              @endphp
              <tr class="odd:bg-white even:bg-gray-50/60 dark:odd:bg-gray-800 dark:even:bg-gray-800/60 hover:bg-indigo-50/40 dark:hover:bg-gray-700/40 transition-colors">
                <td class="px-5 py-4 align-top">
                  <div class="font-medium text-gray-900 dark:text-gray-100">{{ $si->product->name ?? ('#'.$si->product_id) }}</div>
                  <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $si->product_id }}">
                  <input type="hidden" name="items[{{ $i }}][sales_item_id]" value="{{ $si->id }}">
                </td>
                <td class="px-5 py-4 text-right whitespace-nowrap text-gray-700 dark:text-gray-200">{{ number_format((float)$si->quantity, 0, ',', '.') }}</td>
                <td class="px-5 py-4">
                  <input type="number" step="1" min="0" max="{{ (int)$si->quantity }}" name="items[{{ $i }}][quantity]" value="0" class="block w-full text-right rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500" />
                </td>
                <td class="px-5 py-4">
                  <div class="flex items-stretch">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm">Rp</span>
                    <input type="number" step="0.01" min="0" name="items[{{ $i }}][unit_price]" value="{{ $unitPriceAfterDiscount }}" class="block w-full text-right rounded-r-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500" />
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </section>

      <div class="px-0 sm:px-0 flex items-center justify-end gap-3">
        <a href="{{ route('sales.show', $sale) }}" class="px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">Batal</a>
        <button class="px-4 py-2 rounded-md bg-indigo-600 text-white shadow hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500">Simpan Retur</button>
      </div>
    </form>
  </div>
</div>
@endsection