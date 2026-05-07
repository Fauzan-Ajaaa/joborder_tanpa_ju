@extends('layouts.app')

@section('content')

@section('title', 'Buat Penjualan')

@push('styles')
<style>
    .bg-gray-50 {
        background-color: #f9fafb;
    }
    .hidden {
        display: none;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    @if(isset($selectedJobOrder))
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-purple-900">Membuat Penjualan dari Job Order: {{ $selectedJobOrder->kode_job }}</p>
                        <p class="text-sm text-purple-700">Produk: 
                            @if($selectedJobOrder->jobOrderDetails->count() > 0)
                                @foreach($selectedJobOrder->jobOrderDetails as $detail)
                                    {{ $detail->product->name }}{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            @elseif($selectedJobOrder->product)
                                {{ $selectedJobOrder->product->name }}
                            @else
                                -
                            @endif
                            | Customer: {{ $selectedJobOrder->customer_name ?? '-' }}</p>
                    </div>
                </div>
                <a href="{{ route('job-orders.show', $selectedJobOrder->id) }}" class="text-purple-600 hover:text-purple-800 text-sm font-medium">Lihat Job Order</a>
            </div>
        </div>
    @endif
    
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Buat Penjualan</h1>
        <a href="{{ route('sales.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>


    <!-- Form Section -->
    @if(isset($selectedJobOrder))
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <form action="{{ route('sales.store') }}" method="POST" class="p-6 space-y-6">
            @csrf
            
            <!-- Hidden input untuk job order data -->
            <input type="hidden" name="job_order_kode" value="{{ $selectedJobOrder->kode_job ?? '' }}">
            <input type="hidden" name="job_order_customer_id" value="{{ $selectedJobOrder->customer_id ?? '' }}">
            
            <!-- Informasi Pelanggan (Auto-fill dari Job Order) -->
            @if(isset($selectedJobOrder))
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Pelanggan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Pelanggan</label>
                        <input type="text" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100" value="{{ $selectedJobOrder->customer_name ?? '-' }}">
                        <input type="hidden" name="customer_id" value="{{ $selectedJobOrder->customer_id ?? '' }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">No. Telepon</label>
                        <input type="text" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100" value="{{ $selectedJobOrder->customer_phone ?? '-' }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Alamat</label>
                        <input type="text" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100" value="{{ $selectedJobOrder->customer_address ?? '-' }}">
                    </div>
                </div>
            </div>
            @endif

            <!-- Informasi Job Order -->
            @if(isset($selectedJobOrder))
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Penjualan</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tanggal Penjualan</label>
                        <input type="date" name="transaction_date" 
                               value="{{ old('transaction_date', isset($selectedJobOrder) && $selectedJobOrder->selesai_job_at ? $selectedJobOrder->selesai_job_at->format('Y-m-d') : date('Y-m-d')) }}" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        @error('transaction_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Jam: 
                            @if(isset($selectedJobOrder) && $selectedJobOrder->selesai_job_at)
                                <span class="font-medium text-gray-700">{{ $selectedJobOrder->selesai_job_at->format('H:i:s') }}</span>
                                <span class="text-gray-400">(selesai job)</span>
                            @else
                                <span id="realtime-clock" class="font-medium text-gray-700"></span>
                                <script>
                                    function updateClock() {
                                        const now = new Date();
                                        document.getElementById('realtime-clock').textContent =
                                            now.toLocaleTimeString('id-ID');
                                    }
                                    updateClock();
                                    setInterval(updateClock, 1000);
                                </script>
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Karyawan</label>
                        <select name="employee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">- Pilih Karyawan -</option>
                            @foreach($employees ?? [] as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id', $selectedJobOrder->labors->first()->employee_id ?? '') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                        @error('employee_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Metode Pembayaran</label>
                        <select name="payment_status" id="payment_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="cash" {{ old('payment_status','cash')=='cash'?'selected':'' }}>Tunai</option>
                            <option value="transfer" {{ old('payment_status')=='transfer'?'selected':'' }}>Transfer Bank</option>
                            <option value="ewallet" {{ old('payment_status')=='ewallet'?'selected':'' }}>E-Wallet</option>
                        </select>
                        @error('payment_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            @endif

            <!-- Item Penjualan (Auto-fill dari Job Order) -->
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Item Penjualan</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-sm font-medium text-gray-700">Produk</th>
                            <th class="px-3 py-2 text-right text-sm font-medium text-gray-700">Qty</th>
                            <th class="px-3 py-2 text-right text-sm font-medium text-gray-700">Harga Jual</th>
                            <th class="px-3 py-2 text-right text-sm font-medium text-gray-700">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($selectedJobOrder))
                            @if($selectedJobOrder->jobOrderDetails->count() > 0)
                                @foreach($selectedJobOrder->jobOrderDetails as $index => $detail)
                                @php
                                    $totalQty = $selectedJobOrder->jobOrderDetails->sum('quantity');
                                    $hppPerUnit = ($totalQty > 0 && $selectedJobOrder->total_hpp)
                                        ? $selectedJobOrder->total_hpp / $totalQty : 0;
                                @endphp
                                <tr>
                                    <td class="px-3 py-2">
                                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $detail->product_id }}">
                                        <input type="hidden" name="items[{{ $index }}][hpp]" value="{{ (int) $hppPerUnit }}">
                                        <input type="text" readonly class="w-full rounded-md border-gray-300 bg-gray-100" value="{{ $detail->product->name ?? '-' }}">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="1" name="items[{{ $index }}][quantity]"
                                            class="w-full rounded-md border-gray-300 text-right bg-gray-100"
                                            value="{{ (int) $detail->quantity }}" readonly>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" class="w-full rounded-md border-gray-300 text-right" value="Rp {{ number_format((int) old('items.'.$index.'.unit_price', $detail->total_price / $detail->quantity), 0, ',', '.') }}" oninput="formatUnitPrice(this); updateCalculations()" id="unit_price_display_{{ $index }}">
                                        <input type="hidden" name="items[{{ $index }}][unit_price]" value="{{ old('items.'.$index.'.unit_price', $detail->total_price / $detail->quantity) }}" id="unit_price_raw_{{ $index }}">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" readonly class="w-full rounded-md border-gray-300 bg-gray-100 text-right" id="subtotal-{{ $index }}" value="Rp {{ number_format((int) $detail->total_price, 0, ',', '.') }}">
                                    </td>
                                </tr>
                                @endforeach
                            @elseif($selectedJobOrder->product)
                                @php
                                    $hppPerUnit = ($selectedJobOrder->quantity > 0 && $selectedJobOrder->total_hpp)
                                        ? $selectedJobOrder->total_hpp / $selectedJobOrder->quantity : 0;
                                @endphp
                                <tr>
                                    <td class="px-3 py-2">
                                        <input type="hidden" name="items[0][product_id]" value="{{ $selectedJobOrder->product_id }}">
                                        <input type="hidden" name="items[0][hpp]" value="{{ (int) $hppPerUnit }}">
                                        <input type="text" readonly class="w-full rounded-md border-gray-300 bg-gray-100" value="{{ $selectedJobOrder->product->name ?? '-' }}">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="1" name="items[0][quantity]"
                                            class="w-full rounded-md border-gray-300 text-right bg-gray-100"
                                            value="{{ (int) $selectedJobOrder->quantity }}" readonly>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" class="w-full rounded-md border-gray-300 text-right" value="Rp {{ number_format((int) old('items.0.unit_price', $selectedJobOrder->product->price ?? 0), 0, ',', '.') }}" oninput="formatUnitPrice(this); updateCalculations()" id="unit_price_display_0">
                                        <input type="hidden" name="items[0][unit_price]" value="{{ old('items.0.unit_price', $selectedJobOrder->product->price ?? 0) }}" id="unit_price_raw_0">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" readonly class="w-full rounded-md border-gray-300 bg-gray-100 text-right" id="subtotal-0" value="Rp {{ number_format((int) (($selectedJobOrder->product->price ?? 0) * $selectedJobOrder->quantity), 0, ',', '.') }}">
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="4" class="px-3 py-2 text-center text-gray-500">Tidak ada produk dalam job order</td>
                                </tr>
                            @endif
                        @else
                        <tr>
                            <td class="px-3 py-2">
                                <select name="items[0][product_id]" class="w-full rounded-md border-gray-300" onchange="updateCalculations()">
                                    <option value="">-- Pilih Produk --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}" {{ old('items.0.product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.01" name="items[0][quantity]" class="w-full rounded-md border-gray-300 text-right" value="{{ old('items.0.quantity', 1) }}" onchange="updateCalculations()">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" readonly class="w-full rounded-md border-gray-300 bg-gray-100 text-right" value="Rp 0">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" name="items[0][unit_price]" class="w-full rounded-md border-gray-300 text-right" value="Rp {{ (int) old('items.0.unit_price', 0) }}" oninput="formatUnitPrice(this); updateCalculations()" placeholder="Rp 0" id="unit_price_display_0">
                                <input type="hidden" name="items[0][unit_price]" value="{{ old('items.0.unit_price', 0) }}" id="unit_price_raw_0">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" readonly class="w-full rounded-md border-gray-300 bg-gray-100 text-right" id="subtotal-0" value="0">
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- PPN, Diskon, dan FOB -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Diskon (%)</label>
                    <input type="number" step="0.01" min="0" max="100" id="discount_rate" name="discount_rate" value="{{ old('discount_rate', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateCalculations()">
                    @error('discount_rate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">PPN (%)</label>
                    <input type="number" step="0.01" min="0" max="100" id="ppn_rate" name="ppn_rate" value="{{ old('ppn_rate', 11) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateCalculations()" required>
                    @error('ppn_rate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Opsi Pengiriman</label>
                    <select name="fob_type" id="fob_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="toggleFobCost(); updateCalculations()">
                        <option value="shipping_point" {{ old('fob_type','shipping_point')=='shipping_point'?'selected':'' }}>Take Away</option>
                        <option value="dine_in" {{ old('fob_type')=='dine_in'?'selected':'' }}>Dine In</option>
                        <option value="destination" {{ old('fob_type')=='destination'?'selected':'' }}>Diantar (Biaya Pengiriman dibayar pembeli)</option>
                    </select>
                    @error('fob_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <!-- Biaya Pengiriman (mucul jika diantar) -->
            <div id="fob-cost-container" style="display: {{ old('fob_type') == 'destination' ? 'block' : 'none' }};">
                <label class="block text-sm font-medium text-gray-700">Biaya Pengiriman</label>
                <input type="number" step="0.01" min="0" name="fob_cost" id="fob_cost" value="{{ old('fob_cost', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="updateCalculations()">
                @error('fob_cost')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Ringkasan Biaya -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Ringkasan Biaya</h3>
                <div class="max-w-md ml-auto">
                    <?php 
                    $initialSubtotal = 0;
                    if(isset($selectedJobOrder)) {
                        if($selectedJobOrder->jobOrderDetails->count() > 0) {
                            foreach($selectedJobOrder->jobOrderDetails as $detail) {
                                $initialSubtotal += $detail->total_price;
                            }
                        } elseif($selectedJobOrder->product && $selectedJobOrder->quantity) {
                            $initialSubtotal = ($selectedJobOrder->product->price ?? 0) * $selectedJobOrder->quantity;
                        }
                    }
                    ?>
                    {{-- Total HPP hanya untuk internal/jurnal, tidak ditampilkan ke pelanggan --}}
                    <input type="hidden" name="total_hpp" value="{{ (int) ($selectedJobOrder->total_hpp ?? 0) }}">
                    <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                        <span class="text-gray-600">Subtotal Items:</span>
                        <span class="font-medium" id="display-subtotal">Rp {{ number_format((int) $initialSubtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                        <span class="text-gray-600">Diskon:</span>
                        <span class="font-medium text-red-600" id="display-discount">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                        <span class="text-gray-600">Subtotal Setelah Diskon:</span>
                        <span class="font-medium" id="display-subtotal-after-discount">Rp {{ number_format((int) $initialSubtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                        <span class="text-gray-600">Biaya Pengiriman:</span>
                        <span class="font-medium" id="display-fob">Rp {{ number_format((int) old('fob_cost', 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                        <span class="text-gray-600">PPN:</span>
                        <span class="font-medium" id="display-ppn">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-3">
                        <span class="text-gray-900">TOTAL:</span>
                        <span class="text-blue-600" id="display-total">Rp {{ number_format((int) $initialSubtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-2">
                <a href="{{ route('sales.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Batal</a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="text-center">
                <h3 class="text-lg font-medium text-yellow-800 mb-2">Pilih Job Order Terlebih Dahulu</h3>
                <p class="text-yellow-600 mb-4">Untuk membuat transaksi penjualan, Anda perlu memilih job order yang telah selesai diproduksi dari daftar yang tersedia.</p>
                <a href="{{ route('job-orders.index') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700">
                   PILIH JOB ORDER
                </a>
            </div>
        </div>
    @endif
</div>

<script>
function formatUnitPrice(input) {
    let value = input.value.replace(/[^\d]/g, '');
    const index = input.id.replace('unit_price_display_', '');
    
    if (value === '') {
        input.value = '';
        document.getElementById(`unit_price_raw_${index}`).value = '';
        return;
    }
    let number = parseFloat(value);
    if (!isNaN(number)) {
        input.value = 'Rp ' + number.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        document.getElementById(`unit_price_raw_${index}`).value = number;
    }
}

function enforceMaxQty(input) {
    const max = parseFloat(input.getAttribute('data-max')) || null;
    const value = parseFloat(input.value) || 0;
    if (max !== null && value > max) {
        input.value = max;
        alert('Quantity melebihi stok tersedia. Maksimal: ' + max);
    }
}

function toggleFobCost() {
    const fobType = document.getElementById('fob_type');
    const fobCostContainer = document.getElementById('fob-cost-container');
    
    console.log('fobType element:', fobType);
    console.log('fobCostContainer element:', fobCostContainer);
    
    if (!fobType || !fobCostContainer) {
        console.log('Elements not found!');
        return;
    }
    
    console.log('fobType value:', fobType.value);
    
    if (fobType.value === 'destination') {
        fobCostContainer.style.display = 'block';
        console.log('Showing container');
    } else {
        fobCostContainer.style.display = 'none';
        document.getElementById('fob_cost').value = 0;
        console.log('Hiding container');
    }
}

function updateCalculations() {
    console.log('updateCalculations called');
    try {
    // Calculate subtotal from all items
    let subtotal = 0;
    const itemInputs = document.querySelectorAll('[name^="items["][name*="[quantity]"]');
    
    console.log('Found quantity inputs:', itemInputs.length);
    
    // Jika tidak ada quantity inputs (job order mode), coba cara lain
    if (itemInputs.length === 0) {
        // Cari semua unit_price_raw inputs
        const priceInputs = document.querySelectorAll('[id^="unit_price_raw_"]');
        console.log('Found price inputs:', priceInputs.length);
        
        priceInputs.forEach((input, index) => {
            const unitPrice = parseFloat(input.value) || 0;
            // Untuk job order, quantity ada di input dengan name items[index][quantity]
            const qtyInput = document.querySelector(`[name="items[${index}][quantity]"]`);
            const quantity = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;
            const itemSubtotal = quantity * unitPrice;
            subtotal += itemSubtotal;
            console.log(`Item ${index}: qty=${quantity}, price=${unitPrice}, subtotal=${itemSubtotal}`);
        });
    } else {
        itemInputs.forEach((input, index) => {
            const quantity = parseFloat(input.value) || 0;
            const unitPriceRaw = document.getElementById(`unit_price_raw_${index}`)?.value || 0;
            const unitPrice = parseFloat(unitPriceRaw) || 0;
            const itemSubtotal = quantity * unitPrice;
            subtotal += itemSubtotal;
            console.log(`Item ${index}: qty=${quantity}, price=${unitPrice}, subtotal=${itemSubtotal}`);
        });
    }
        
        // Update individual subtotal displays
        const subtotalDisplays = document.querySelectorAll('[id^="subtotal-"]');
        subtotalDisplays.forEach((display, index) => {
            const unitPriceRaw = document.getElementById(`unit_price_raw_${index}`)?.value || 0;
            const unitPrice = parseFloat(unitPriceRaw) || 0;
            const qtyInput = document.querySelector(`[name="items[${index}][quantity]"]`);
            const quantity = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;
            const itemSubtotal = quantity * unitPrice;
            display.value = 'Rp ' + itemSubtotal.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        });
    
    const fobCost = parseFloat(document.getElementById('fob_cost').value) || 0;
    const discountRate = parseFloat(document.getElementById('discount_rate').value) || 0;
    const ppnRate = parseFloat(document.getElementById('ppn_rate').value) || 0;
    
    // Calculate totals dengan diskon
    const discountAmount = subtotal * (discountRate / 100);
    const subtotalAfterDiscount = subtotal - discountAmount;
    const ppn = subtotalAfterDiscount * (ppnRate / 100); // PPN hanya dari harga setelah diskon (ongkir tidak kena PPN)
    const total = subtotalAfterDiscount + ppn + fobCost;
    
    // Update display
    if (document.getElementById('display-subtotal')) {
        document.getElementById('display-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }
    if (document.getElementById('display-discount')) {
        document.getElementById('display-discount').textContent = 'Rp ' + discountAmount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }
    if (document.getElementById('display-subtotal-after-discount')) {
        document.getElementById('display-subtotal-after-discount').textContent = 'Rp ' + subtotalAfterDiscount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }
    if (document.getElementById('display-fob')) {
        document.getElementById('display-fob').textContent = 'Rp ' + fobCost.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }
    if (document.getElementById('display-ppn')) {
        document.getElementById('display-ppn').textContent = 'Rp ' + ppn.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }
    if (document.getElementById('display-total')) {
        document.getElementById('display-total').textContent = 'Rp ' + total.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }
    } catch (error) {
        console.error('Error in updateCalculations:', error);
    }
}

// Initialize calculations on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initial calculation
    updateCalculations();
    
    // Setup FOB toggle
    const fobTypeSelect = document.getElementById('fob_type');
    if (fobTypeSelect) {
        fobTypeSelect.addEventListener('change', function() {
            const fobCostContainer = document.getElementById('fob-cost-container');
            if (fobCostContainer) {
                if (this.value === 'destination') {
                    fobCostContainer.style.display = 'block';
                } else {
                    fobCostContainer.style.display = 'none';
                    document.getElementById('fob_cost').value = 0;
                }
            }
        });
    }
    
    const salesFormSection = document.getElementById('sales-form-section');
    const itemSection = document.getElementById('item-penjualan-section');
    const bottomSections = document.getElementById('bottom-sections');
    
    if (salesFormSection) salesFormSection.classList.remove('hidden');
    if (itemSection) itemSection.classList.remove('hidden');
    if (bottomSections) bottomSections.classList.remove('hidden');
    
    updateCalculations();
});
</script>
@endsection

@push('scripts')
@endpush


