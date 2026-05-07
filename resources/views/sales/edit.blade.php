@extends('layouts.app')

@section('title', 'Edit Penjualan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Edit Penjualan</h1>
    <a href="{{ route('sales.index') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">Kembali</a>
  </div>

  <div class="overflow-hidden rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 bg-white dark:bg-gray-800">
    <form action="{{ route('sales.update', $sale) }}" method="POST" class="p-6 space-y-6" id="sales-edit-form">
      @csrf
      @method('PUT')

      <!-- Informasi Job Order (Read-only) -->
      @if($sale->job_order_id)
      <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Job Order</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nomor Penjualan</label>
            <input type="text" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 font-bold text-blue-600" value="{{ $sale->transaction_number }}">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Job Order</label>
            <input type="text" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 text-purple-600" value="{{ $sale->job_order_id }}">
          </div>
        </div>
      </div>
      @endif

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Pelanggan</label>
          <input type="text" name="customer_name" value="{{ old('customer_name', $sale->customer_name) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
          @error('customer_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Karyawan</label>
          <select name="employee_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">- Pilih Karyawan -</option>
            @foreach($employees ?? [] as $emp)
              <option value="{{ $emp->id }}" {{ (old('employee_id') == $emp->id) ? 'selected' : '' }}>{{ $emp->name }}</option>
            @endforeach
          </select>
          @error('employee_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>  
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Alamat</label>
          <textarea name="customer_address" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">{{ old('customer_address', $sale->customer_address) }}</textarea>
          @error('customer_address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Transaksi</label>
          <input type="date" name="transaction_date" value="{{ old('transaction_date', optional($sale->transaction_date)->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500" required>
          @error('transaction_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">PPN (%)</label>
          <input type="number" step="0.01" min="0" max="100" id="ppn_rate" name="ppn_rate" value="{{ old('ppn_rate', $sale->ppn_rate ?? 11) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500" required>
          @error('ppn_rate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Diskon (%)</label>
          <input type="number" step="0.01" min="0" max="100" id="discount_rate" name="discount_rate" value="{{ old('discount_rate', $sale->discount_rate ?? 0) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
          @error('discount_rate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Opsi Pengiriman</label>
          @php($ft = old('fob_type', $sale->fob_type ?? 'shipping_point'))
          <select name="fob_type" id="fob_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500" onchange="toggleFobCost()">
            <option value="shipping_point" {{ $ft=='shipping_point'?'selected':'' }}>Take Away</option>
            <option value="dine_in" {{ $ft=='dine_in'?'selected':'' }}>Dine In</option>
            <option value="destination" {{ $ft=='destination'?'selected':'' }}>Diantar (Biaya Pengiriman dibayar pembeli</option>
          </select>
          @error('fob_type')<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
        <div id="fob-cost-container">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Biaya Pengiriman</label>
          <input type="number" step="0.01" min="0" name="fob_cost" id="fob_cost" value="{{ old('fob_cost', (float)($sale->fob_cost ?? 0)) }}" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
          @error('fob_cost')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Metode Pembayaran</label>
          <select name="payment_status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500">
            @php($ps = old('payment_status', $sale->payment_status ?? 'cash'))
            <option value="cash" {{ $ps=='cash'?'selected':'' }}>Tunai</option>
            <option value="transfer" {{ $ps=='transfer'?'selected':'' }}>Transfer</option>
            <option value="ewallet" {{ $ps=='ewallet'?'selected':'' }}>E-Wallet</option>
          </select>
          @error('payment_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
      </div>

      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Item Penjualan</h2>
          <button type="button" id="add-row" class="px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Tambah Item</button>
        </div>

        <div class="overflow-x-auto rounded-md ring-1 ring-gray-200 dark:ring-gray-700">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="items-table">
            <thead class="bg-gray-50 dark:bg-gray-700/40">
              <tr>
                <th class="px-3 py-2 text-left text-sm font-medium text-gray-700 dark:text-gray-200">Produk</th>
                <th class="px-3 py-2 text-right text-sm font-medium text-gray-700 dark:text-gray-200">Qty</th>
                <th class="px-3 py-2 text-right text-sm font-medium text-gray-700 dark:text-gray-200">Harga Satuan</th>
                <th class="px-3 py-2"></th>
              </tr>
            </thead>
            <tbody id="items-body" class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
              @php($idx = 0)
              @foreach($sale->salesItems as $si)
              <tr>
                <td class="px-3 py-2">
                  <select name="items[{{ $idx }}][product_id]" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    @foreach($products as $product)
                      <option value="{{ $product->id }}" {{ $product->id == $si->product_id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                  </select>
                </td>
                <td class="px-3 py-2">
                  @if($sale->job_order_id)
                    <input type="number" step="1" min="1" name="items[{{ $idx }}][quantity]" value="{{ (int)$si->quantity }}" class="w-full rounded-md border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-right" readonly>
                  @else
                    <input type="number" step="1" min="1" name="items[{{ $idx }}][quantity]" value="{{ (int)$si->quantity }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-right">
                  @endif
                </td>
                <td class="px-3 py-2">
                  <input type="number" step="0.01" min="0" name="items[{{ $idx }}][unit_price]" value="{{ (float)$si->unit_price }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-right bg-gray-100" readonly>
                </td>
                <td class="px-3 py-2 text-right">
                  <button type="button" class="px-3 py-1 rounded bg-red-600 text-white remove-row">Hapus</button>
                </td>
              </tr>
              @php($idx++)
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 overflow-hidden rounded-md ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Ringkasan Biaya</h3>
        </div>
        <div class="px-6 py-4">
          <div class="max-w-md ml-auto">
            @if($sale->job_order_id)
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 dark:border-gray-700 hidden">
              <span class="text-gray-600 dark:text-gray-300">Total HPP:</span>
              <span class="font-medium text-red-600">Rp {{ number_format($sale->jobOrder->total_hpp ?? 0, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 dark:border-gray-700">
              <span class="text-gray-600 dark:text-gray-300">Subtotal Items:</span>
              <span class="font-medium" id="display-subtotal">Rp 0</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 dark:border-gray-700">
              <span class="text-gray-600 dark:text-gray-300">Diskon:</span>
              <span class="font-medium text-red-600" id="display-discount">Rp 0</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 dark:border-gray-700">
              <span class="text-gray-600 dark:text-gray-300">Subtotal Setelah Diskon:</span>
              <span class="font-medium" id="display-subtotal-after-discount">Rp 0</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 dark:border-gray-700">
              <span class="text-gray-600 dark:text-gray-300">Biaya Pengiriman:</span>
              <span class="font-medium" id="display-fob">Rp 0</span>
            </div>
            <div class="flex justify-between text-sm py-2 border-b border-gray-200 dark:border-gray-700">
              <span class="text-gray-600 dark:text-gray-300">PPN:</span>
              <span class="font-medium" id="display-ppn">Rp 0</span>
            </div>
            <div class="flex justify-between text-lg font-bold pt-3">
              <span class="text-gray-900 dark:text-gray-100">TOTAL:</span>
              <span class="text-blue-600" id="display-total">Rp 0</span>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-2 mt-4">
        <a href="{{ route('sales.index') }}" class="inline-flex items-center px-4 py-2 rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">Batal</a>
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const body = document.getElementById('items-body');
  const addBtn = document.getElementById('add-row');
  function nextIndex(){ return body.querySelectorAll('tr').length; }
  function formatRupiah(number){ return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(number || 0)); }
  function rowTemplate(i){
    return `
      <tr>
        <td class="px-3 py-2">
          <select name="items[${i}][product_id]" class="w-full rounded-md border-gray-300">
            @foreach($products as $product)
              <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
          </select>
        </td>
        <td class="px-3 py-2">
          <input type="number" step="1" min="1" name="items[${i}][quantity]" class="w-full rounded-md border-gray-300 text-right" value="1">
        </td>
        <td class="px-3 py-2">
          <input type="number" step="0.01" min="0" name="items[${i}][unit_price]" class="w-full rounded-md border-gray-300 text-right" value="0">
        </td>
        <td class="px-3 py-2 text-right"><button type="button" class="px-3 py-1 bg-red-600 text-white rounded remove-row">Hapus</button></td>
      </tr>`;
  }

  function updateRowSubtotalAndTotals(){ calculateTotals(); }

  function calculateTotals(){
    let subtotal = 0;
    const qtyInputs = document.querySelectorAll('input[name^="items["][name$="[quantity]"]');
    qtyInputs.forEach(qtyInput => {
      const m = qtyInput.name.match(/items\[(\d+)\]\[quantity\]/);
      if(!m) return;
      const idx = m[1];
      const priceInput = document.querySelector(`input[name="items[${idx}][unit_price]"]`);
      const qty = parseFloat(qtyInput.value || 0);
      const price = parseFloat(priceInput?.value || 0);
      subtotal += qty * price;
    });

    const fobCost = parseFloat(document.getElementById('fob_cost')?.value || 0);
    const discountRate = parseFloat(document.getElementById('discount_rate')?.value || 0);
    const ppnRate = parseFloat(document.getElementById('ppn_rate')?.value || 0);
    
    // Calculate totals dengan diskon
    const discountAmount = subtotal * (discountRate / 100);
    const subtotalAfterDiscount = subtotal - discountAmount;
    const ppn = subtotalAfterDiscount * (ppnRate / 100); // PPN hanya dari harga setelah diskon (ongkir tidak kena PPN)
    const total = subtotalAfterDiscount + ppn + fobCost;

    document.getElementById('display-subtotal').textContent = formatRupiah(subtotal);
    document.getElementById('display-discount').textContent = formatRupiah(discountAmount);
    document.getElementById('display-subtotal-after-discount').textContent = formatRupiah(subtotalAfterDiscount);
    document.getElementById('display-fob').textContent = formatRupiah(fobCost);
    document.getElementById('display-ppn').textContent = formatRupiah(ppn);
    document.getElementById('display-total').textContent = formatRupiah(total);
  }

  function toggleFobCost(){
    const type = document.getElementById('fob_type')?.value;
    const container = document.getElementById('fob-cost-container');
    const input = document.getElementById('fob_cost');
    if(!type || !container || !input) return;
    if(type === 'destination'){
      container.style.display = 'block';
    }else{
      container.style.display = 'none';
      input.value = 0;
    }
    calculateTotals();
  }

  addBtn?.addEventListener('click', function(){
    const i = nextIndex();
    body.insertAdjacentHTML('beforeend', rowTemplate(i));
  });

  body?.addEventListener('click', function(e){
    if(e.target.classList.contains('remove-row')){
      const tr = e.target.closest('tr');
      if(tr){ tr.remove(); calculateTotals(); }
    }
  });

  body?.addEventListener('input', function(e){
    if(e.target.name){
      const m = e.target.name.match(/items\[(\d+)\]\[(quantity|unit_price)\]/);
      if(m){ updateRowSubtotalAndTotals(); }
    }
    if(e.target.id === 'discount_rate' || e.target.id === 'ppn_rate' || e.target.id === 'fob_cost'){
      calculateTotals();
    }
  });

  // Initialize calculation on page load
  document.addEventListener('DOMContentLoaded', function(){
    calculateTotals();
    toggleFobCost();
    document.getElementById('discount_rate')?.addEventListener('input', calculateTotals);
    document.getElementById('ppn_rate')?.addEventListener('input', calculateTotals);
    document.getElementById('fob_cost')?.addEventListener('input', calculateTotals);
    document.getElementById('fob_type')?.addEventListener('change', function(){
      toggleFobCost();
      calculateTotals();
    });
  });
})();
</script>
@endsection