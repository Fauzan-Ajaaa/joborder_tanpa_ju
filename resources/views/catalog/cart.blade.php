@extends('layouts.catalog')

@section('title', 'Keranjang Belanja')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-amber-50 to-orange-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-amber-800">Keranjang Belanja</h1>
                <p class="text-sm text-amber-600">Review pesanan Anda sebelum checkout</p>
            </div>
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 rounded-lg font-medium text-amber-700 hover:bg-amber-50 transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Katalog
            </a>
        </div>

        @if(empty($items))
            <div class="bg-white rounded-2xl shadow-md p-8 text-center border border-dashed border-amber-200">
                <i class="fas fa-shopping-cart text-4xl text-amber-300 mb-3"></i>
                <h2 class="text-lg font-semibold text-amber-800 mb-1">Keranjang Masih Kosong</h2>
                <p class="text-sm text-amber-600 mb-4">Yuk pilih produk favoritmu dulu di katalog.</p>
                <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-amber-600 to-amber-700 text-white rounded-lg font-semibold hover:from-amber-700 hover:to-amber-800 transition-all duration-200">
                    <i class="fas fa-store mr-2"></i>Lihat Katalog
                </a>
            </div>
        @else
        <form action="{{ route('catalog.cart.update') }}" method="POST" class="space-y-6">
            @csrf
            <div class="bg-white rounded-2xl shadow-md overflow-hidden border border-amber-100">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-amber-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-amber-800 uppercase tracking-wider">Produk</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-amber-800 uppercase tracking-wider">Harga Satuan</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-amber-800 uppercase tracking-wider">Jumlah</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-amber-800 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-amber-800 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($items as $index => $row)
                        <tr class="hover:bg-amber-50/40 transition-colors">
                            <td class="px-4 py-3 flex items-center gap-3">
                                <div class="w-16 h-16 rounded-lg bg-amber-50 flex items-center justify-center overflow-hidden border border-amber-100">
                                    @if($row['product']->has_image)
                                        <img src="{{ route('products.image', $row['product']) }}" alt="{{ $row['product']->name }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="fas fa-cookie-bite text-amber-300 text-2xl"></i>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $row['product']->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $row['product']->code }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-800">
                                Rp {{ number_format($row['unit_price'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="inline-flex items-center border border-amber-200 rounded-lg bg-amber-50/60">
                                    <button type="button" class="px-2 py-1 text-amber-700 hover:bg-amber-100" onclick="changeQty({{ $index }}, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" name="items[{{ $index }}][quantity]" id="qty-{{ $index }}" value="{{ $row['quantity'] }}" min="0" class="w-14 text-center border-x border-amber-200 bg-white text-sm py-1" onchange="qtyInputChanged({{ $index }})">
                                    <button type="button" class="px-2 py-1 text-amber-700 hover:bg-amber-100" onclick="changeQty({{ $index }}, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $row['product']->id }}">
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold text-amber-800">
                                <span id="line-total-{{ $index }}" data-unit-price="{{ $row['unit_price'] }}">
                                    Rp {{ number_format($row['line_total'], 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" class="text-xs text-red-500 hover:text-red-600 font-medium" onclick="removeItem({{ $index }})">
                                    <i class="fas fa-trash-alt mr-1"></i>Hapus
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Ringkasan & Checkout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <div class="lg:col-span-2 flex flex-wrap gap-3">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 rounded-lg text-sm font-medium text-amber-700 hover:bg-amber-50">
                        <i class="fas fa-sync-alt mr-2"></i>Update Keranjang
                    </button>
                    <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-plus-circle mr-2"></i>Tambah Produk Lain
                    </a>
                </div>

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
                            <span class="text-lg font-bold text-amber-700" id="grandtotal-display">Rp {{ number_format($subtotalAfterDiscount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">Ongkos kirim dan detail pembayaran akan dihitung pada langkah berikutnya.</p>

                    <button type="button" onclick="document.getElementById('checkout-form').classList.remove('hidden'); this.disabled=true;" class="mt-4 w-full inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-amber-600 to-amber-700 text-white text-sm font-semibold rounded-lg hover:from-amber-700 hover:to-amber-800 transition-all duration-200 shadow-md">
                        Lanjut Checkout
                    </button>
                </div>
            </div>
        </form>

        <!-- Checkout Form (basic, Mode A) -->
        <div id="checkout-form" class="mt-8 hidden">
            <div class="bg-white rounded-2xl shadow-md p-6 border border-amber-100">
                <h2 class="text-lg font-semibold text-amber-800 mb-4">Data Pemesan</h2>
                <form action="{{ route('catalog.checkout') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
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
                        <button type="button" onclick="document.getElementById('checkout-form').classList.add('hidden');" class="px-6 py-3 text-sm bg-white border-2 border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-semibold transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Batal
                        </button>
                        <button type="submit" class="px-6 py-3 text-sm bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg font-semibold hover:from-green-700 hover:to-green-800 transition-all shadow-md hover:shadow-lg">
                            <i class="fas fa-arrow-right mr-2"></i>Lanjut ke Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
</div>

<script>
const cartDiscountRate = {{ (float) $discountRate }};

function formatRupiah(num) {
    return 'Rp ' + (num || 0).toLocaleString('id-ID');
}

function recalcRow(index) {
    const qtyInput = document.getElementById('qty-' + index);
    const lineEl = document.getElementById('line-total-' + index);
    if (!qtyInput || !lineEl) return;

    let qty = parseInt(qtyInput.value || '0', 10);
    if (qty < 0) qty = 0;
    qtyInput.value = qty;

    const unitPrice = parseFloat(lineEl.dataset.unitPrice || '0');
    const lineTotal = unitPrice * qty;
    lineEl.textContent = formatRupiah(lineTotal);
}

function recalcSummary() {
    let subtotal = 0;
    const rows = document.querySelectorAll('[id^="line-total-"]');
    rows.forEach(el => {
        const index = el.id.replace('line-total-', '');
        const qtyInput = document.getElementById('qty-' + index);
        if (!qtyInput) return;
        const qty = parseInt(qtyInput.value || '0', 10);
        const unitPrice = parseFloat(el.dataset.unitPrice || '0');
        subtotal += unitPrice * qty;
    });

    const discount = subtotal * (cartDiscountRate / 100);
    const grand = subtotal - discount;

    const subtotalEl = document.getElementById('subtotal-display');
    const discountEl = document.getElementById('discount-display');
    const grandEl = document.getElementById('grandtotal-display');
    if (subtotalEl) subtotalEl.textContent = formatRupiah(subtotal);
    if (discountEl) discountEl.textContent = '- ' + formatRupiah(discount);
    if (grandEl) grandEl.textContent = formatRupiah(grand);
}

function changeQty(index, delta) {
    const input = document.getElementById('qty-' + index);
    if (!input) return;
    let value = parseInt(input.value || '0', 10);
    value += delta;
    if (value < 0) value = 0;
    input.value = value;
    recalcRow(index);
    recalcSummary();
}

function qtyInputChanged(index) {
    recalcRow(index);
    recalcSummary();
}

function removeItem(index) {
    const input = document.getElementById('qty-' + index);
    if (!input) return;
    input.value = 0;
    recalcRow(index);
    recalcSummary();
}

// Initial recalculation to ensure consistency
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.querySelectorAll('[id^="line-total-"]');
    rows.forEach(el => {
        const index = el.id.replace('line-total-', '');
        recalcRow(index);
    });
    recalcSummary();
});
</script>
@endsection
