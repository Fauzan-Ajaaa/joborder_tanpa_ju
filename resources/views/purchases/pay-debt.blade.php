@extends('layouts.app')

@section('title', 'Pelunasan Hutang Purchase Order')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Pelunasan Hutang</h1>
            <p class="mt-1 text-sm text-gray-600">Bayar hutang purchase order yang belum lunas</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali
        </a>
    </div>

    <!-- Purchase Order Info -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Informasi Purchase Order</h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nomor PO</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $purchase->purchase_number }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Supplier</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $purchase->supplier->name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tanggal Pembelian</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $purchase->purchase_date->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Jatuh Tempo</label>
                    <p class="mt-1 text-sm text-gray-900">{{ $purchase->due_date ? $purchase->due_date->format('d/m/Y') : '-' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Pembelian</label>
                    <p class="mt-1 text-sm text-gray-900">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status Pembayaran</label>
                    <p class="mt-1">
                        @if($purchase->payment_status === 'paid')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Lunas
                            </span>
                        @elseif($purchase->payment_status === 'partial')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Sebagian
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Belum Lunas
                            </span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Form -->
    @if($purchase->payment_status !== 'paid')
    <form action="{{ route('purchases.store-debt-payment', $purchase) }}" method="POST" class="space-y-6">
        @csrf

        <!-- Payment Details -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Detail Pembayaran</h3>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="payment_date" class="block text-sm font-medium text-gray-700">Tanggal Pembayaran *</label>
                        <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('payment_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">Metode Pembayaran *</label>
                        <select name="payment_method" id="payment_method" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Pilih Metode</option>
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="giro">Giro</option>
                            <option value="cek">Cek</option>
                        </select>
                        @error('payment_method')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Jumlah Pembayaran *</label>
                        <input type="number" name="amount" id="amount" value="{{ old('amount') }}" min="0" max="{{ $purchase->remaining_amount }}" step="0.01" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="updateRemaining()">
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Maksimal: Rp {{ number_format($purchase->remaining_amount, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <label for="bank_account" class="block text-sm font-medium text-gray-700">Rekening Tujuan</label>
                        <input type="text" name="bank_account" id="bank_account" value="{{ old('bank_account') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Nomor rekening">
                        @error('bank_account')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Catatan Pembayaran</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Ringkasan Pembayaran</h3>
            </div>
            <div class="px-6 py-4">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Total Hutang:</span>
                        <span class="font-medium">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Sudah Dibayar:</span>
                        <span class="font-medium text-green-600">Rp {{ number_format($purchase->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Sisa Hutang:</span>
                        <span class="font-medium text-red-600" id="remaining-before">Rp {{ number_format($purchase->remaining_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm border-t pt-2">
                        <span class="text-gray-600">Pembayaran Ini:</span>
                        <span class="font-medium text-blue-600" id="payment-amount">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t-2 pt-2">
                        <span class="text-gray-900">Sisa Setelah Pembayaran:</span>
                        <span class="text-red-600" id="remaining-after">Rp {{ number_format($purchase->remaining_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('purchases.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                Proses Pembayaran
            </button>
        </div>
    </form>
    @else
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">Hutang Sudah Lunas</h3>
                <p class="mt-1 text-sm text-gray-500">Purchase order ini sudah dibayar penuh.</p>
                <div class="mt-6">
                    <a href="{{ route('purchases.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                        Kembali ke Daftar Purchase Order
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
// Set initial remaining amount from server
var remainingAmount = {!! json_encode($purchase->remaining_amount ?? 0) !!};

function updateRemaining() {
    var amount = parseFloat(document.getElementById('amount').value || 0);
    var remainingBefore = parseFloat(remainingAmount);
    var remainingAfter = remainingBefore - amount;
    
    document.getElementById('payment-amount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
    document.getElementById('remaining-after').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(remainingAfter);
}
</script>
@endsection
