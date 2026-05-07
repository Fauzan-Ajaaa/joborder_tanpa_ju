@extends('layouts.catalog')

@section('title', 'Checkout')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-amber-50 to-orange-50 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-amber-800">Checkout</h1>
                <p class="text-sm text-amber-600">Selesaikan pesanan untuk produk berikut</p>
            </div>
            <a href="{{ route('catalog.show', $product) }}" class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 rounded-lg font-medium text-amber-700 hover:bg-amber-50 transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Produk
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Produk -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl shadow-md p-5 border border-amber-100 flex gap-4">
                    <div class="w-20 h-20 rounded-lg bg-amber-50 flex items-center justify-center overflow-hidden border border-amber-100 flex-shrink-0">
                        @if($product->has_image)
                            <img src="{{ route('products.image', $product) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                        @else
                            <i class="fas fa-cookie-bite text-amber-300 text-2xl"></i>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $product->name }}</p>
                        <p class="text-xs text-gray-500 mb-2">{{ $product->code }}</p>
                        <p class="text-sm text-gray-700">Harga: <span class="font-semibold">Rp {{ number_format($unitPrice, 0, ',', '.') }}</span></p>
                        <p class="text-sm text-gray-700 mt-1">Jumlah: <span class="font-semibold" id="qty-display">{{ $quantity }}</span></p>
                    </div>
                </div>

                <!-- Form Data Pemesan -->
                <div class="bg-white rounded-2xl shadow-md p-6 border border-amber-100 mt-4">
                    <h2 class="text-lg font-semibold text-amber-800 mb-4">Data Pemesan</h2>
                    <form action="{{ route('catalog.checkout.single') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" id="qty-input" name="quantity" value="{{ $quantity }}">

                        <div class="md:col-span-1">
                            <label class="block text-xs font-semibold text-amber-700 mb-1">Nama Lengkap <span class="text-red-600">*</span></label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" class="w-full border border-amber-200 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" placeholder="Masukkan nama lengkap Anda" required>
                            @error('customer_name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-xs font-semibold text-amber-700 mb-1">No. Telepon <span class="text-red-600">*</span></label>
                            <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}" class="w-full border border-amber-200 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" placeholder="08xx-xxxx-xxxx" pattern="[0-9]{10,13}" required>
                            @error('customer_phone')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-amber-700 mb-1">Alamat Pengiriman <span class="text-red-600">*</span></label>
                            <textarea name="customer_address" rows="3" class="w-full border border-amber-200 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten" required>{{ old('customer_address') }}</textarea>
                            @error('customer_address')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-amber-700 mb-1">Catatan (Opsional)</label>
                            <textarea name="notes" rows="2" class="w-full border border-amber-200 rounded-lg px-3 py-2 text-sm focus:ring-amber-500 focus:border-amber-500" placeholder="Catatan khusus untuk pesanan Anda">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2 flex justify-end gap-3 mt-4">
                            <a href="{{ route('catalog.show', $product) }}" class="px-6 py-3 text-sm bg-white border-2 border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-semibold transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Batal
                            </a>
                            <button type="submit" class="px-6 py-3 text-sm bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg font-semibold hover:from-green-700 hover:to-green-800 transition-all shadow-md hover:shadow-lg">
                                <i class="fas fa-arrow-right mr-2"></i>Lanjut ke Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ringkasan -->
            <div class="bg-white rounded-2xl shadow-md p-5 border border-amber-100">
                <h2 class="text-lg font-semibold text-amber-800 mb-3">Ringkasan Pesanan</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-900" id="subtotal-display">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Diskon @if($discountRate > 0) ({{ number_format($discountRate, 0) }}%) @endif</span>
                        <span class="font-semibold text-red-500" id="discount-display">- Rp {{ number_format($discount, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t pt-2 mt-2 flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-800">Total Setelah Diskon</span>
                        <span class="text-lg font-bold text-amber-700" id="grandtotal-display">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-500">Ongkos kirim dan detail pembayaran akan dihitung pada langkah berikutnya.</p>

                <!-- Kontrol jumlah di sisi ringkasan -->
                <div class="mt-4">
                    <label class="block text-xs font-semibold text-amber-700 mb-1">Ubah Jumlah</label>
                    <div class="inline-flex items-center border border-amber-200 rounded-lg bg-amber-50/60">
                        <button type="button" class="px-3 py-1 text-amber-700 hover:bg-amber-100" onclick="adjustQty(-1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="qty-control" value="{{ $quantity }}" min="1" class="w-16 text-center border-x border-amber-200 bg-white text-sm py-1">
                        <button type="button" class="px-3 py-1 text-amber-700 hover:bg-amber-100" onclick="adjustQty(1)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const unitPrice = {{ (int) $unitPrice }};
    const discountRate = {{ (float) $discountRate }};

    function formatRupiah(num) {
        return 'Rp ' + (num || 0).toLocaleString('id-ID');
    }

    function syncQty(qty) {
        if (qty < 1) qty = 1;
        const qtyInput = document.getElementById('qty-input');
        const qtyDisplay = document.getElementById('qty-display');
        const qtyControl = document.getElementById('qty-control');
        if (qtyInput) qtyInput.value = qty;
        if (qtyDisplay) qtyDisplay.textContent = qty;
        if (qtyControl) qtyControl.value = qty;

        const subtotal = unitPrice * qty;
        const discount = subtotal * (discountRate / 100);
        const grand = subtotal - discount;

        const subtotalEl = document.getElementById('subtotal-display');
        const discountEl = document.getElementById('discount-display');
        const grandEl = document.getElementById('grandtotal-display');
        if (subtotalEl) subtotalEl.textContent = formatRupiah(subtotal);
        if (discountEl) discountEl.textContent = '- ' + formatRupiah(discount).replace('Rp ', 'Rp ');
        if (grandEl) grandEl.textContent = formatRupiah(grand);
    }

    function adjustQty(delta) {
        const control = document.getElementById('qty-control');
        if (!control) return;
        let value = parseInt(control.value || '1', 10);
        value += delta;
        if (value < 1) value = 1;
        syncQty(value);
    }
</script>
@endsection
