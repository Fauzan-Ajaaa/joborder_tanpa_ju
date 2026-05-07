@extends('layouts.catalog')

@section('title', 'Buat Pesanan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Buat Pesanan</h1>
        <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali ke Katalog
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Pilih Produk</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($products as $product)
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $product->code }}</p>
                            </div>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold">
                                Stok: {{ number_format($product->stock, 2, ',', '.') == number_format($product->stock, 0, ',', '.') . ',00' ? number_format($product->stock, 0, ',', '.') : number_format($product->stock, 2, ',', '.').' == number_format($product->stock, 0, ',', '.') . ',00' ? number_format($product->stock, 0, ',', '.') : number_format($product->stock, 2, ',', '.') == number_format($product->stock, 0, ',', '.') . ',00' ? number_format($product->stock, 0, ',', '.') : number_format($product->stock, 2, ',', '.').' }}
                            </span>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-3">{{ $product->description ?: 'Tidak ada deskripsi' }}</p>
                        
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-2xl font-bold text-blue-600">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                        </div>
                        
                        <form action="{{ route('catalog.order.product', $product) }}" method="GET">
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg text-center font-semibold hover:bg-blue-700 transition duration-200">
                                <i class="fas fa-shopping-cart mr-2"></i>Pesan Produk Ini
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8">
                        <i class="fas fa-box text-gray-300 text-5xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">Tidak ada produk tersedia</h3>
                        <p class="text-gray-500">Semua produk sedang habis stok</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
