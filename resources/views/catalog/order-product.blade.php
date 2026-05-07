@extends('layouts.catalog')

@section('title', 'Pesan ' . $product->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-amber-50 to-orange-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gradient-to-br from-amber-600 to-amber-700 rounded-full flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-amber-800">Pesan {{ $product->name }}</h1>
                    <p class="text-amber-600">Lengkapi formulir pemesanan Anda</p>
                </div>
            </div>
            <a href="{{ route('catalog.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-white border border-amber-300 rounded-lg font-medium text-amber-700 hover:bg-amber-50 transition-colors duration-200">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Katalog
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Product Info Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-amber-100 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-box mr-2"></i>Informasi Produk
                    </h2>
                </div>
                
                <div class="p-6 space-y-6">
                    <!-- Product Image -->
                    <div class="h-100 bg-gradient-to-br from-amber-100 to-orange-100 rounded-xl flex items-center justify-center">
                        @if($product->has_image)
                            <img src="{{ route('products.image', $product) }}" alt="{{ $product->name }}" 
                                 class="w-full h-full object-cover rounded-xl">
                        @else
                            <div class="text-amber-400 text-center">
                                <i class="fas fa-cookie-bite text-6xl mb-2"></i>
                                <p class="text-amber-600 font-medium">{{ $product->name }}</p>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Product Details -->
                    <div class="space-y-4">
                        <div class="bg-amber-50 rounded-lg p-3">
                            <span class="text-xs text-amber-600 font-medium">Kode Produk</span>
                            <p class="text-lg font-bold text-amber-800">{{ $product->code }}</p>
                        </div>
                        
                        <div>
                            <span class="text-sm text-gray-600">Nama Produk</span>
                            <p class="text-xl font-bold text-gray-900">{{ $product->name }}</p>
                        </div>
                        
                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-lg p-4 border border-amber-200">
                            <span class="text-sm text-amber-600 font-medium">Harga per Unit</span>
                            <p class="text-2xl font-bold text-amber-700">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        </div>
                        
                        <div class="flex items-center justify-between bg-green-50 rounded-lg p-3 border border-green-200">
                            <span class="text-sm text-green-600 font-medium">Stok Tersedia</span>
                            <span class="text-xl font-bold text-green-700">{{ number_format($product->available_stock, 0, ',', '.') }} {{ $product->stock_unit }}</span>
                        </div>
                        
                        <div>
                            <span class="text-sm text-gray-600">Deskripsi</span>
                            <p class="text-gray-700 leading-relaxed">{{ $product->description ?: 'Tidak ada deskripsi tersedia.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Form Card -->
            <div class="bg-white rounded-2xl shadow-xl border border-amber-100 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <i class="fas fa-edit mr-2"></i>Formulir Pemesanan
                    </h2>
                </div>
                
                <div class="p-6">
                    <form action="{{ route('catalog.order.store') }}" method="POST" class="space-y-6">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <!-- Quantity -->
                        <div>
                            <label for="quantity" class="block text-sm font-bold text-amber-700 mb-2">
                                <i class="fas fa-sort-numeric-up mr-1"></i>Jumlah Pesanan
                            </label>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="{{ $product->available_stock }}" required
                                   class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50/50 transition-colors duration-200"
                                   onchange="updateTotal()">
                            @error('quantity')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Customer Info -->
                        <div class="border-t border-amber-200 pt-6">
                            <h3 class="text-lg font-bold text-amber-800 mb-4 flex items-center">
                                <i class="fas fa-user mr-2"></i>Informasi Pelanggan
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="customer_name" class="block text-sm font-bold text-amber-700 mb-2">
                                        <i class="fas fa-user-circle mr-1"></i>Nama Lengkap *
                                    </label>
                                    <input type="text" name="customer_name" id="customer_name" required
                                           class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50/50 transition-colors duration-200"
                                           placeholder="Masukkan nama lengkap Anda">
                                    @error('customer_name')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="customer_phone" class="block text-sm font-bold text-amber-700 mb-2">
                                        <i class="fas fa-phone mr-1"></i>Nomor Telepon
                                    </label>
                                    <input type="tel" name="customer_phone" id="customer_phone"
                                           class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50/50 transition-colors duration-200"
                                           placeholder="08123456789">
                                    @error('customer_phone')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="customer_address" class="block text-sm font-bold text-amber-700 mb-2">
                                        <i class="fas fa-map-marker-alt mr-1"></i>Alamat Pengiriman
                                    </label>
                                    <textarea name="customer_address" id="customer_address" rows="3"
                                              class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50/50 transition-colors duration-200"
                                              placeholder="Masukkan alamat lengkap pengiriman"></textarea>
                                    @error('customer_address')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="notes" class="block text-sm font-bold text-amber-700 mb-2">
                                        <i class="fas fa-sticky-note mr-1"></i>Catatan (Opsional)
                                    </label>
                                    <textarea name="notes" id="notes" rows="3"
                                              class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 bg-amber-50/50 transition-colors duration-200"
                                              placeholder="Catatan khusus untuk pesanan Anda"></textarea>
                                    @error('notes')
                                        <p class="mt-2 text-sm text-red-600 flex items-center">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ $message }}
                                        </p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-6 border border-amber-200">
                            <h3 class="text-lg font-bold text-amber-800 mb-4 flex items-center">
                                <i class="fas fa-calculator mr-2"></i>Ringkasan Harga
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-amber-700">Harga per Unit:</span>
                                    <span class="font-bold text-amber-800">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-amber-700">Jumlah:</span>
                                    <span class="font-bold text-amber-800" id="quantity-display">1</span>
                                </div>
                                <div class="border-t border-amber-300 pt-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-lg font-bold text-amber-900">Total Harga:</span>
                                        <span class="text-xl font-bold text-amber-700" id="total-price">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-4 pt-4">
                            <a href="{{ route('catalog.index') }}" 
                               class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-white border-2 border-amber-300 rounded-xl font-bold text-amber-700 hover:bg-amber-50 transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>Batal
                            </a>
                            <button type="submit" 
                                    class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-amber-600 to-amber-700 text-white rounded-xl font-bold hover:from-amber-700 hover:to-amber-800 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <i class="fas fa-credit-card mr-2"></i>Lanjut ke Pembayaran
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateTotal() {
    const quantity = document.getElementById('quantity').value;
    const price = parseFloat({{ $product->price }});
    const total = quantity * price;
    
    document.getElementById('quantity-display').textContent = quantity;
    document.getElementById('total-price').textContent = 'Rp ' + total.toLocaleString('id-ID');
}
</script>
@endsection
