@extends('layouts.catalog')

@section('title', $product->name)

@section('content')
    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <nav class="flex items-center justify-between">
                <div class="flex items-center space-x-2 text-sm">
                    <a href="{{ route('catalog.index') }}" class="text-gray-600 hover:text-blue-600">Katalog</a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900">{{ $product->name }}</span>
                </div>
                <a href="{{ route('catalog.index') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Product Image -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="h-[600px] bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center relative overflow-hidden">
                    {{-- Gambar dari database --}}
                    @if($product->has_image)
                        <img src="{{ route('products.image', $product) }}" alt="{{ $product->name }}" 
                             class="w-full h-full object-cover">
                    {{-- Fallback ke sistem kategori --}}
                    @elseif(str_contains(strtolower($product->name), 'cookies'))
                        <img src="{{ asset('images/cookies.png') }}" alt="{{ $product->name }}" 
                             class="w-full h-full object-cover">
                    @elseif(str_contains(strtolower($product->name), 'brownies'))
                        <img src="{{ asset('images/brownies.png') }}" alt="{{ $product->name }}" 
                             class="w-full h-full object-cover">
                    @else
                        <div class="text-amber-400 text-center">
                            <i class="fas fa-cookie-bite text-6xl mb-4"></i>
                            <p class="text-lg font-medium">Gambar Produk</p>
                            <p class="text-sm mt-2">{{ $product->code }}</p>
                        </div>
                    @endif
                </div>
                
                <!-- Stock Status -->
                <div class="mt-4 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        @if($product->available_stock > 0)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-2"></i>Tersedia
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-2"></i>Habis
                            </span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-warehouse mr-1"></i>
                        Stok: {{ number_format($product->available_stock, 0, ',', '.') }} {{ $product->stock_unit }}
                    </div>
                </div>
            </div>

            <!-- Product Details -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="mb-6">
                    <span class="text-sm text-gray-500 font-semibold">{{ $product->code }}</span>
                    <h1 class="text-3xl font-bold text-gray-900 mt-2">{{ $product->name }}</h1>
                </div>

                <div class="mb-6">
                    <div class="text-3xl font-bold text-blue-600 mb-2">
                        Rp {{ number_format($product->price, 0, ',', '.') }}
                    </div>
                    <p class="text-gray-600">Harga per unit</p>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Deskripsi Produk</h3>
                    <p class="text-gray-600 leading-relaxed">
                        {{ $product->description ?: 'Tidak ada deskripsi tersedia untuk produk ini.' }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-4 mt-4">
                    @if($product->stock <= 0)
                        <button disabled class="w-full bg-gray-300 text-gray-500 py-3 px-6 rounded-lg font-semibold cursor-not-allowed">
                            <i class="fas fa-times-circle mr-2"></i>Stok Habis
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Related Products -->
        @if($relatedProducts->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Produk Terkait</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                        <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden group">
                            <div class="h-32 bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center relative overflow-hidden">
                                @if($related->has_image)
                                    <img src="{{ route('products.image', $related) }}" alt="{{ $related->name }}" 
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @elseif(str_contains(strtolower($related->name), 'cookies'))
                                    <img src="{{ asset('images/cookies.png') }}" alt="{{ $related->name }}" 
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @elseif(str_contains(strtolower($related->name), 'brownies'))
                                    <img src="{{ asset('images/brownies.png') }}" alt="{{ $related->name }}" 
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <i class="fas fa-cookie-bite text-amber-400 text-2xl group-hover:animate-bounce"></i>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-2 group-hover:text-blue-600 transition line-clamp-1">
                                    {{ $related->name }}
                                </h3>
                                <p class="text-lg font-bold text-blue-600 mb-2">
                                    Rp {{ number_format($related->price, 0, ',', '.') }}
                                </p>
                                <a href="{{ route('catalog.show', $related) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
                                    Lihat Detail →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    <script>
        function changeDetailQty(delta) {
            const input = document.getElementById('detail-qty');
            const hidden = document.getElementById('detail-qty-hidden');
            if (!input) return;
            let value = parseInt(input.value || '1', 10);
            value += delta;
            if (value < 1) value = 1;
            input.value = value;
            if (hidden) {
                hidden.value = value;
            }
        }
    </script>

    @endsection
