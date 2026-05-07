@extends('layouts.catalog')

@section('title', 'Pembayaran')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Pembayaran Pesanan</h1>
        <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali ke Katalog
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Order Summary -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Ringkasan Pesanan</h2>
                    
                    <!-- Product Details -->
                    <div class="border-b pb-4 mb-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $jobOrder->product->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $jobOrder->product->code }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ $jobOrder->notes }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Jumlah: {{ $jobOrder->quantity }}</p>
                                <p class="text-lg font-semibold text-blue-600">Rp {{ number_format($jobOrder->product->price * $jobOrder->quantity, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="border-b pb-4 mb-4">
                        <h3 class="text-md font-medium text-gray-900 mb-3">Informasi Pelanggan</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Nama:</span>
                                <span class="text-gray-900">{{ $jobOrder->customer_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Telepon:</span>
                                <span class="text-gray-900">{{ $jobOrder->customer_phone }}</span>
                            </div>
                            @if($jobOrder->customer_address)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Alamat:</span>
                                <span class="text-gray-900">{{ $jobOrder->customer_address }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    @php
                        $subtotalProduk = (float) ($jobOrder->total_cost ?? ($jobOrder->product->price * $jobOrder->quantity));
                        $defaultOngkir = 0;
                        $ongkirDelivery = 10000; // ongkir tetap untuk opsi antar
                    @endphp

                    <!-- Total + Ongkir -->
                    <div class="bg-gray-50 rounded-lg p-4" id="summary-box" data-subtotal="{{ $subtotalProduk }}" data-ongkir-delivery="{{ $ongkirDelivery }}">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Subtotal Produk:</span>
                            <span class="font-semibold" id="subtotal-display">Rp {{ number_format($subtotalProduk, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Ongkos Kirim:</span>
                            <span class="font-semibold" id="ongkir-display">Rp {{ number_format($defaultOngkir, 0, ',', '.') }}</span>
                        </div>
                        <div class="border-t pt-2 mt-2">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Total Pembayaran:</span>
                                <span class="text-xl font-bold text-blue-600" id="total-display">Rp {{ number_format($subtotalProduk + $defaultOngkir, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Form -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Metode Pembayaran</h2>
                    
                    <form action="{{ route('catalog.payment.process', $jobOrder) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <!-- Payment Method -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Pilih Metode Pembayaran</label>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="radio" name="payment_method" value="transfer" required
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">Transfer Bank</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="payment_method" value="cash" required
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">Tunai (COD)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="payment_method" value="ewallet" required
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">E-Wallet</span>
                                </label>
                            </div>
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Shipping Option -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Opsi Pengiriman</label>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="radio" name="shipping_option" value="pickup" checked
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">Ambil di Toko (Tanpa Ongkir)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="shipping_option" value="delivery"
                                           class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <span class="ml-2 text-sm text-gray-700">Diantar (Ongkir Rp {{ number_format($ongkirDelivery, 0, ',', '.') }})</span>
                                </label>
                            </div>
                            @error('shipping_option')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Cash Payment Section (for COD) -->
                        <div id="cash-payment-section" class="hidden">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <h3 class="text-sm font-medium text-green-900 mb-3">Pembayaran Tunai</h3>
                                
                                <!-- Total Amount Display -->
                                <div class="mb-4 p-3 bg-white rounded-lg border border-green-200">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-700">Total Pembayaran:</span>
                                        <span class="text-lg font-bold text-green-700" id="cash-total-amount">Rp {{ number_format($subtotalProduk + $defaultOngkir, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                                
                                <!-- Cash Input -->
                                <div class="mb-4">
                                    <label for="cash_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                        Jumlah Uang Tunai
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">Rp</span>
                                        <input type="number" 
                                               id="cash_amount" 
                                               name="cash_amount" 
                                               class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-sm font-medium"
                                               placeholder="0"
                                               min="0"
                                               step="1000"
                                               oninput="calculateChange()">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Masukkan jumlah uang yang dibayarkan</p>
                                </div>
                                
                                <!-- Change Calculation -->
                                <div id="change-section" class="hidden">
                                    <div class="p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-gray-700">Kembalian:</span>
                                            <span class="text-lg font-bold text-yellow-700" id="change-amount">Rp 0</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Quick Amount Buttons -->
                                <div class="mt-3">
                                    <p class="text-xs text-gray-600 mb-2">Jumlah Cepat:</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        <button type="button" onclick="setCashAmount(50000)" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-medium hover:bg-gray-50 transition-colors">
                                            Rp 50.000
                                        </button>
                                        <button type="button" onclick="setCashAmount(100000)" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-medium hover:bg-gray-50 transition-colors">
                                            Rp 100.000
                                        </button>
                                        <button type="button" onclick="setCashAmount(150000)" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-medium hover:bg-gray-50 transition-colors">
                                            Rp 150.000
                                        </button>
                                        <button type="button" onclick="setCashAmount(200000)" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-medium hover:bg-gray-50 transition-colors">
                                            Rp 200.000
                                        </button>
                                        <button type="button" onclick="setCashAmount(500000)" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-medium hover:bg-gray-50 transition-colors">
                                            Rp 500.000
                                        </button>
                                        <button type="button" onclick="calculateExactAmount()" class="px-3 py-2 bg-green-100 border border-green-300 rounded-lg text-xs font-medium text-green-700 hover:bg-green-200 transition-colors">
                                            Uang Pas
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Proof (for transfer and e-wallet) -->
                        <div id="payment-proof-section" class="hidden">
                            <label for="payment_proof" class="block text-sm font-medium text-gray-700">Upload Bukti Pembayaran</label>
                            <input type="file" name="payment_proof" id="payment_proof" accept="image/*"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="mt-1 text-xs text-gray-500">Format: JPEG, PNG, JPG, GIF (Maks. 2MB)</p>
                            @error('payment_proof')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bank Information (for transfer) -->
                        <div id="bank-info" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-blue-900 mb-2">Informasi Transfer</h3>
                            <div class="text-xs text-blue-800 space-y-1">
                                <p><strong>Bank:</strong> BCA</p>
                                <p><strong>No. Rekening:</strong> 123-456-7890</p>
                                <p><strong>Atas Nama:</strong> {{ $companyContact->nama_perusahaan ?? $company->name ?? 'PT. Manufaktur' }}</p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button type="submit" class="w-full bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 py-3 px-4">
                                <i class="fas fa-check mr-2"></i>Konfirmasi Pembayaran
                            </button>
                        </div>
                    </form>

                    <!-- Order Info -->
                    <div class="mt-6 pt-6 border-t">
                        <div class="text-xs text-gray-500 space-y-1">
                            <p><strong>No. Pesanan:</strong> #{{ str_pad($jobOrder->id, 6, '0', STR_PAD_LEFT) }}</p>
                            <p><strong>Tanggal:</strong> {{ $jobOrder->order_date->format('d/m/Y') }}</p>
                            <p><strong>Status:</strong> <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-semibold">Menunggu Pembayaran</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const proofSection = document.getElementById('payment-proof-section');
    const bankInfo = document.getElementById('bank-info');
    const cashPaymentSection = document.getElementById('cash-payment-section');
    const shippingOptions = document.querySelectorAll('input[name="shipping_option"]');
    const summaryBox = document.getElementById('summary-box');
    const subtotal = parseFloat(summaryBox.dataset.subtotal || '0');
    const ongkirDelivery = parseFloat(summaryBox.dataset.ongkirDelivery || '0');
    const subtotalDisplay = document.getElementById('subtotal-display');
    const ongkirDisplay = document.getElementById('ongkir-display');
    const totalDisplay = document.getElementById('total-display');
    const cashTotalAmount = document.getElementById('cash-total-amount');

    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Hide all sections first
            proofSection.classList.add('hidden');
            bankInfo.classList.add('hidden');
            cashPaymentSection.classList.add('hidden');
            
            if (this.value === 'transfer' || this.value === 'ewallet') {
                proofSection.classList.remove('hidden');
            }

            if (this.value === 'transfer') {
                bankInfo.classList.remove('hidden');
            }

            if (this.value === 'cash') {
                cashPaymentSection.classList.remove('hidden');
                updateCashTotalAmount();
            }
        });
    });

    function formatRupiah(value) {
        return 'Rp ' + value.toLocaleString('id-ID');
    }

    function updateOngkir() {
        let ongkir = 0;
        const selected = document.querySelector('input[name="shipping_option"]:checked');
        if (selected && selected.value === 'delivery') {
            ongkir = ongkirDelivery;
        }

        const total = subtotal + ongkir;
        ongkirDisplay.textContent = formatRupiah(ongkir);
        subtotalDisplay.textContent = formatRupiah(subtotal);
        totalDisplay.textContent = formatRupiah(total);
        
        // Update cash total amount if cash section is visible
        updateCashTotalAmount();
    }

    function updateCashTotalAmount() {
        if (cashTotalAmount) {
            const ongkir = document.querySelector('input[name="shipping_option"]:checked')?.value === 'delivery' ? ongkirDelivery : 0;
            const total = subtotal + ongkir;
            cashTotalAmount.textContent = formatRupiah(total);
        }
    }

    shippingOptions.forEach(opt => {
        opt.addEventListener('change', updateOngkir);
    });

    // Inisialisasi tampilan awal
    updateOngkir();
});

// Cash payment functions
function calculateChange() {
    const cashAmount = parseFloat(document.getElementById('cash_amount').value) || 0;
    const totalText = document.getElementById('cash-total-amount').textContent;
    const total = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;
    
    const changeSection = document.getElementById('change-section');
    const changeAmount = document.getElementById('change-amount');
    
    if (cashAmount > 0) {
        const change = cashAmount - total;
        
        if (change >= 0) {
            changeSection.classList.remove('hidden');
            changeAmount.textContent = 'Rp ' + change.toLocaleString('id-ID');
            changeAmount.className = 'text-lg font-bold text-green-700';
        } else {
            changeSection.classList.remove('hidden');
            changeAmount.textContent = 'Kurang Rp ' + Math.abs(change).toLocaleString('id-ID');
            changeAmount.className = 'text-lg font-bold text-red-700';
        }
    } else {
        changeSection.classList.add('hidden');
    }
}

function setCashAmount(amount) {
    document.getElementById('cash_amount').value = amount;
    calculateChange();
}

function calculateExactAmount() {
    const totalText = document.getElementById('cash-total-amount').textContent;
    const total = parseFloat(totalText.replace(/[^\d]/g, '')) || 0;
    document.getElementById('cash_amount').value = total;
    calculateChange();
}
</script>
@endsection
