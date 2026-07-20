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
        <form action="{{ route('sales.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
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
                        <select name="payment_method" id="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" onchange="togglePaymentProof()">
                            <option value="cash" {{ old('payment_method','cash')=='cash'?'selected':'' }}>Tunai</option>
                            <option value="transfer" {{ old('payment_method')=='transfer'?'selected':'' }}>Transfer Bank</option>
                            <option value="ewallet" {{ old('payment_method')=='ewallet'?'selected':'' }}>E-Wallet</option>
                            <option value="cod" {{ old('payment_method')=='cod'?'selected':'' }}>COD (Cash on Delivery)</option>
                        </select>
                        @error('payment_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            @endif

            <!-- Upload Bukti Pembayaran (hanya untuk Diantar + Transfer/E-Wallet) -->
            <div id="payment-proof-section" style="display: none;">
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        <i class="fas fa-upload text-yellow-600 mr-2"></i>
                        Bukti Pembayaran
                    </h3>
                    <p class="text-sm text-yellow-700 mb-4">
                        <span class="font-semibold">Upload bukti pembayaran</span> untuk metode Transfer Bank dan E-Wallet.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            Upload Bukti Transfer/E-Wallet <span class="text-red-500" id="proof-required-indicator">*</span>
                        </label>
                        <input type="file" name="payment_proof" id="payment_proof" accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-blue-50 file:text-blue-700
                                      hover:file:bg-blue-100">
                        @error('payment_proof')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, max 2MB</p>
                    </div>
                </div>
            </div>

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
                                        <input type="text" class="w-full rounded-md border-gray-300 text-right" value="Rp {{ number_format((int) old('items.'.$index.'.unit_price', $detail->total_price / $detail->quantity), 0, ',', '.') }}" oninput="formatUnitPrice(this); updateCalculations()" id="unit_price_display_{{ $index }}" required>
                                        <input type="hidden" name="items[{{ $index }}][unit_price]" value="{{ old('items.'.$index.'.unit_price', $detail->total_price / $detail->quantity) }}" id="unit_price_raw_{{ $index }}">
                                        @php
                                            // billOfMaterials adalah hasMany, ambil yang aktif atau pertama
                                            $bom = $detail->product?->billOfMaterials()->where('is_active', true)->first() 
                                                   ?? $detail->product?->billOfMaterials()->first();
                                            $margin = $bom?->profit_margin_percentage;
                                        @endphp
                                        @if($margin)
                                        @endif
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
                                        <input type="text" class="w-full rounded-md border-gray-300 text-right" value="Rp {{ number_format((int) old('items.0.unit_price', $selectedJobOrder->product->price ?? 0), 0, ',', '.') }}" oninput="formatUnitPrice(this); updateCalculations()" id="unit_price_display_0" required>
                                        <input type="hidden" name="items[0][unit_price]" value="{{ old('items.0.unit_price', $selectedJobOrder->product->price ?? 0) }}" id="unit_price_raw_0">
                                        @php
                                            // billOfMaterials adalah hasMany, ambil yang aktif atau pertama
                                            $bom = $selectedJobOrder->product?->billOfMaterials()->where('is_active', true)->first() 
                                                   ?? $selectedJobOrder->product?->billOfMaterials()->first();
                                            $margin = $bom?->profit_margin_percentage;
                                            $totalCost = $bom ? ($bom->getTotalMaterialCost() + $bom->getTotalLaborCost() + $bom->getTotalOverheadCost()) : 0;
                                            $recommendedPrice = ($margin && $totalCost > 0) ? $totalCost * (1 + ($margin / 100)) : 0;
                                        @endphp
                                        @if($margin && $recommendedPrice > 0)
                                            <small class="block text-xs text-blue-600 mt-1">
                                                💡 Margin {{ $margin }}%: Rp {{ number_format($recommendedPrice, 0, ',', '.') }}
                                            </small>
                                        @endif
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
                    <label class="block text-sm font-medium text-gray-700">Metode Layanan</label>
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

            @if(isset($selectedJobOrder) && $selectedJobOrder->total_hpp > 0)
            @php
                // Ambil BOM dari produk untuk mendapatkan margin yang sudah diinput
                $product = null;
                if($selectedJobOrder->jobOrderDetails->count() > 0) {
                    $product = $selectedJobOrder->jobOrderDetails->first()->product;
                } elseif($selectedJobOrder->product) {
                    $product = $selectedJobOrder->product;
                }
                
                $bom = $product ? ($product->billOfMaterials()->where('is_active', true)->first() ?? $product->billOfMaterials()->first()) : null;
                $savedMargin = $bom?->profit_margin_percentage;
                
                $hpp = $selectedJobOrder->total_hpp ?? 0;
                $qty = $selectedJobOrder->jobOrderDetails->count() > 0 
                       ? $selectedJobOrder->jobOrderDetails->sum('quantity') 
                       : $selectedJobOrder->quantity;
                $hppPerUnit = $qty > 0 ? $hpp / $qty : 0;
            @endphp
            
            @if($savedMargin && $hppPerUnit > 0)
            <!-- Rekomendasi Harga Jual Berdasarkan Margin BOM -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2"> Rekomendasi Harga Jual</h3>
                @php
                    $hargaRekomendasi = $hppPerUnit * (1 + $savedMargin / 100);
                    $hargaRekomendasi = ceil($hargaRekomendasi / 100) * 100; // Pembulatan ke ratusan terdekat
                    $keuntungan = $hargaRekomendasi - $hppPerUnit;
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="text-xs font-medium text-gray-500 uppercase mb-1">HPP per Unit</div>
                        <div class="text-xl font-bold text-gray-700">Rp {{ number_format($hppPerUnit, 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                        <div class="text-xs font-medium text-gray-500 uppercase mb-1">Margin dari BOM</div>
                        <div class="text-xl font-bold text-blue-600">{{ number_format($savedMargin, 1) }}%</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border border-green-200 p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="applyRecommendedPrice({{ $hargaRekomendasi }}, 0)">
                        <div class="text-xs font-medium text-gray-500 uppercase mb-1">Harga Jual Rekomendasi</div>
                        <div class="text-xl font-bold text-green-600">Rp {{ number_format($hargaRekomendasi, 0, ',', '.') }}</div>
                        <div class="text-xs text-gray-600 mt-1">Untung: Rp {{ number_format($keuntungan, 0, ',', '.') }}/unit</div>
                        <div class="text-xs text-green-600 mt-2 font-medium">✓ Klik untuk terapkan</div>
                    </div>
                </div>
                
                <div class="p-3 bg-green-100 border border-green-200 rounded-md">
                    <p class="text-xs text-green-800">
                        <strong> Info:</strong> Rekomendasi harga ini dihitung berdasarkan margin <strong>{{ number_format($savedMargin, 1) }}%</strong> yang sudah disimpan di BOM produk. Harga dibulatkan ke ratusan terdekat. Klik pada kartu hijau untuk otomatis mengisi harga jual.
                    </p>
                </div>
            </div>
            @endif
            @endif

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
        console.log('Showing delivery containers');
        // Trigger payment proof check
        togglePaymentProof();
    } else {
        fobCostContainer.style.display = 'none';
        document.getElementById('fob_cost').value = 0;
        console.log('Hiding delivery containers');
        // Hide payment proof when not delivery
        const paymentProofSection = document.getElementById('payment-proof-section');
        const paymentProofInput = document.getElementById('payment_proof');
        if (paymentProofSection) paymentProofSection.style.display = 'none';
        if (paymentProofInput) paymentProofInput.required = false;
    }
}

function togglePaymentProof() {
    const fobType = document.getElementById('fob_type');
    const paymentMethod = document.getElementById('payment_method');
    const paymentProofSection = document.getElementById('payment-proof-section');
    const paymentProofInput = document.getElementById('payment_proof');
    const proofRequiredIndicator = document.getElementById('proof-required-indicator');
    
    if (!fobType || !paymentMethod || !paymentProofSection) {
        console.log('Payment elements not found!');
        return;
    }
    
    console.log('fob_type:', fobType.value, 'payment method:', paymentMethod.value);
    
    // Show payment proof ONLY if: Diantar (destination) AND (Transfer OR E-Wallet)
    const isDelivery = fobType.value === 'destination';
    const requiresProof = paymentMethod.value === 'transfer' || paymentMethod.value === 'ewallet';
    
    if (isDelivery && requiresProof) {
        paymentProofSection.style.display = 'block';
        if (paymentProofInput) paymentProofInput.required = true;
        if (proofRequiredIndicator) proofRequiredIndicator.style.display = 'inline';
        console.log('Payment proof required (Delivery + Transfer/E-Wallet)');
    } else {
        paymentProofSection.style.display = 'none';
        if (paymentProofInput) paymentProofInput.required = false;
        if (proofRequiredIndicator) proofRequiredIndicator.style.display = 'none';
        console.log('Payment proof not required');
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
                    
                    // Hide payment proof when not delivery
                    const paymentProofSection = document.getElementById('payment-proof-section');
                    const paymentProofInput = document.getElementById('payment_proof');
                    if (paymentProofSection) paymentProofSection.style.display = 'none';
                    if (paymentProofInput) paymentProofInput.required = false;
                }
            }
            // Trigger payment proof check
            togglePaymentProof();
        });
    }
    
    // Setup payment method toggle
    const paymentMethodSelect = document.getElementById('payment_method');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            togglePaymentProof();
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

// Apply recommended price to unit price input
function applyRecommendedPrice(price, itemIndex = 0) {
    const displayInput = document.getElementById(`unit_price_display_${itemIndex}`);
    const rawInput = document.getElementById(`unit_price_raw_${itemIndex}`);
    
    if (displayInput && rawInput) {
        const roundedPrice = Math.round(price);
        displayInput.value = 'Rp ' + roundedPrice.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        rawInput.value = roundedPrice;
        updateCalculations();
        
        // Show feedback
        displayInput.classList.add('ring-2', 'ring-green-500');
        setTimeout(() => {
            displayInput.classList.remove('ring-2', 'ring-green-500');
        }, 1000);
    }
}
</script>
@endsection

@push('scripts')
@endpush


