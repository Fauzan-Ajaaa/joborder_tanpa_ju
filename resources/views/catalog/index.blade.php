@extends('layouts.catalog')

@section('title', 'Katalog Produk')

@section('content')
<!-- Promo / Iklan Bakery -->
<section class="pt-0">
    <div class="mx-auto px-0 sm:px-0">
        <div class="catalog-surface overflow-hidden border-y border-gray-100 shadow-sm">
            <div class="relative">
                <!-- Carousel Container -->
                <div class="relative h-screen max-h-[600px] overflow-hidden">

                    @forelse($carouselProducts as $i => $cp)
                    <div class="absolute inset-0 transition-opacity duration-1000 {{ $i === 0 ? 'opacity-100' : 'opacity-0' }}" id="slide{{ $i+1 }}">
                        <div class="absolute inset-0 bg-gradient-to-r from-black/50 to-black/20 z-10"></div>
                        <img src="{{ route('products.image', $cp) }}" alt="{{ $cp->name }}"
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 z-20 p-6 sm:p-10 flex items-center">
                            <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8">
                                <div class="max-w-xl">
                                    <p class="text-xs uppercase tracking-widest font-semibold text-white/90 drop-shadow-lg">{{ $companyContact->nama_perusahaan ?? $company->name ?? config('app.name') }}</p>
                                    <h2 class="text-3xl sm:text-5xl font-bold mt-2 text-white drop-shadow-xl">{{ $cp->name }}</h2>
                                    @if($cp->description)
                                    <p class="text-sm sm:text-base text-white/90 mt-3 drop-shadow-lg">{{ Str::limit($cp->description, 100) }}</p>
                                    @endif
                                    <p class="text-2xl font-bold text-amber-300 mt-3 drop-shadow-lg">Rp {{ number_format($cp->price, 0, ',', '.') }}</p>
                                    <div class="mt-5 flex flex-wrap gap-3">
                                        <a href="#produk" class="china-btn inline-flex items-center px-5 py-2.5 bg-red-800 hover:bg-red-900 text-white rounded-xl font-semibold shadow-sm">
                                            Belanja Sekarang
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="absolute inset-0 bg-gradient-to-br from-red-900 to-amber-800 flex items-center justify-center">
                        <div class="text-center text-white">
                            <i class="fas fa-store text-6xl mb-4 opacity-50"></i>
                            <p class="text-2xl font-bold">{{ $companyContact->nama_perusahaan ?? $company->name ?? config('app.name') }}</p>
                        </div>
                    </div>
                    @endforelse

                    <!-- Navigation Dots -->
                    @if($carouselProducts->count() > 1)
                    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2 z-30">
                        @foreach($carouselProducts as $i => $cp)
                        <button onclick="showSlide({{ $i+1 }})" class="w-3 h-3 rounded-full transition-colors {{ $i === 0 ? 'bg-red-800/80' : 'bg-red-800/50' }} hover:bg-red-800" id="dot{{ $i+1 }}"></button>
                        @endforeach
                    </div>

                    <!-- Arrow Navigation -->
                    <button onclick="previousSlide()" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-red-800/80 hover:bg-red-800 text-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg transition-colors z-30">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button onclick="nextSlide()" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-red-800/80 hover:bg-red-800 text-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg transition-colors z-30">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    @endif
                    <!-- Navigation Dots -->
                    @if($carouselProducts->count() > 1)
                    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2 z-30">
                        @foreach($carouselProducts as $i => $cp)
                        <button onclick="showSlide({{ $i+1 }})" class="w-3 h-3 rounded-full transition-colors {{ $i === 0 ? 'bg-red-800/80' : 'bg-red-800/50' }} hover:bg-red-800" id="dot{{ $i+1 }}"></button>
                        @endforeach
                    </div>

                    <!-- Arrow Navigation -->
                    <button onclick="previousSlide()" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-red-800/80 hover:bg-red-800 text-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg transition-colors z-30">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button onclick="nextSlide()" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-red-800/80 hover:bg-red-800 text-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg transition-colors z-30">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Header / Featured Section -->
<section class="py-16 sm:py-20 relative">
    <!-- Maroon Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-maroon-800 via-maroon-700 to-red-900"></div>
    <div class="absolute inset-0 bg-[url('{{ asset('images/batik-merah.jpg') }}')] bg-cover bg-center opacity-10"></div>
    
    <div class="relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="catalog-surface china-outline fx-3d-card rounded-3xl border border-amber-200 shadow-2xl px-8 py-12 sm:px-12 sm:py-16 shimmer bg-white/95">
                <div class="text-center">
                    <h2 class="text-4xl sm:text-6xl font-bold text-maroon-900 mb-3">{{ $companyContact->nama_perusahaan ?? $company->name ?? config('app.name') }}</h2>
                    <div class="mt-4 h-1 bg-gradient-to-r from-transparent via-amber-400/60 to-transparent rounded-full"></div>

                <!-- Search Form -->
                <form method="GET" action="{{ route('catalog.index') }}" class="max-w-4xl mx-auto mt-10">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1 flex items-center bg-white/95 rounded-2xl border-2 border-amber-200 px-6 py-4 shadow-lg hover:shadow-xl transition-shadow">
                            <i class="fas fa-search text-amber-600 mr-4 text-lg"></i>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari produk kegemaran Anda..."
                                class="w-full bg-transparent placeholder-gray-500 focus:outline-none text-gray-900 text-lg font-medium">
                        </div>
                        <button type="submit" class="fx-3d-btn fx-3d-press inline-flex justify-center items-center px-8 py-4 rounded-2xl bg-gradient-to-r from-red-700 to-red-800 hover:from-red-800 hover:to-red-900 text-white font-bold text-lg shadow-xl hover:shadow-2xl transition-all">
                            <i class="fas fa-search mr-3"></i>
                            Cari Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Filter Section -->
<section class="py-2">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="catalog-surface china-outline-tight fx-3d-card flex flex-wrap gap-4 items-center rounded-xl p-4 shadow-md border border-amber-100 shimmer">
            <input type="hidden" name="search" value="{{ request('search') }}">

            <!-- Decorative filter icon -->
            <div class="relative">
                <div class="w-10 h-10 bg-gradient-to-r from-red-600 to-amber-600 rounded-full flex items-center justify-center animate-pulse">
                    <i class="fas fa-filter text-white text-sm"></i>
                </div>
                <div class="absolute inset-0 rounded-full border-2 border-red-300 animate-ping opacity-20"></div>
            </div>

            <div class="flex items-center gap-3 bg-white/90 px-4 py-2 rounded-lg border border-amber-200 hover:border-amber-400 transition-colors group">
                <label class="text-sm font-bold text-gray-800 group-hover:text-amber-700 transition-colors">Harga:</label>
                <input type="number" name="min_price" value="{{ request('min_price') }}"
                    placeholder="Min"
                    class="w-20 px-2 py-1 border border-amber-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white text-gray-800 font-medium hover:border-amber-400 transition-colors">
                <span class="text-gray-700 font-bold">-</span>
                <input type="number" name="max_price" value="{{ request('max_price') }}"
                    placeholder="Max"
                    class="w-20 px-2 py-1 border border-amber-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white text-gray-800 font-medium hover:border-amber-400 transition-colors">
            </div>

            <button type="submit" class="china-btn fx-3d-btn fx-3d-press bg-gradient-to-r from-red-800 to-red-900 text-white px-6 py-2 rounded-lg text-sm font-bold hover:from-red-900 hover:to-red-950 transition-all duration-300 shadow-md hover:shadow-lg border border-red-800/40 group relative overflow-hidden">
                <span class="relative z-10"><i class="fas fa-filter mr-2"></i>Filter</span>
                <div class="absolute inset-0 bg-gradient-to-r from-amber-400 to-amber-500 opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
            </button>

            @if(request()->hasAny(['search', 'min_price', 'max_price']))
            <a href="{{ route('catalog.index') }}" class="text-red-800 hover:text-red-900 text-sm font-bold flex items-center hover:underline group">
                <i class="fas fa-times-circle mr-1 group-hover:animate-spin"></i>Reset Filter
            </a>
            @endif
        </form>
    </div>
</section>

<!-- Main Content -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
        @forelse($products as $product)
        <div class="catalog-surface china-outline fx-3d-card rounded-3xl shadow-xl hover:shadow-2xl transition-all duration-500 overflow-hidden group border-2 border-maroon-100 hover:border-red-300 hover:-translate-y-3 bg-white">
            <!-- Product Image -->
            <div class="h-80 bg-gray-100 flex items-center justify-center relative overflow-hidden">
                @if($product->has_image)
                <img src="{{ route('products.image', $product) }}" alt="{{ $product->name }}"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                @else
                <div class="text-gray-400 text-center">
                    <i class="fas fa-image text-6xl mb-2"></i>
                    <p class="text-sm">Tidak ada foto</p>
                </div>
                @endif

                <!-- Stock Badge -->
                <div class="absolute top-4 right-4">
                    @if($product->available_stock > 0)
                    <div class="bg-green-600 text-white px-3 py-1 rounded-full text-sm font-bold shadow">
                        Tersedia
                    </div>
                    @else
                    <div class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-bold shadow">
                        Habis
                    </div>
                    @endif
                </div>

            </div>

            <!-- Product Info -->
            <div class="p-8 text-center bg-gradient-to-b from-white to-maroon-50">
                <h3 class="text-xl font-black text-maroon-900 mb-4 line-clamp-2 group-hover:text-red-700 transition-colors">
                    {{ $product->name }}
                </h3>

                @php
                    $displayPrice = (float) ($product->price ?? 0);
                    $oldPrice = $displayPrice > 0 ? round($displayPrice * 1.1) : 0;
                @endphp

                <div class="space-y-2 mb-6">
                    <p class="text-sm text-gray-500 line-through font-medium">Rp {{ number_format($oldPrice, 0, ',', '.') }}</p>
                    <p class="text-3xl font-black text-red-700">Rp {{ number_format($displayPrice, 0, ',', '.') }}</p>
                </div>

                <a href="{{ route('catalog.show', $product) }}" class="china-btn fx-3d-btn fx-3d-press w-full bg-gradient-to-r from-red-700 to-red-800 hover:from-red-800 hover:to-red-900 text-white py-5 px-6 text-center font-black text-xl rounded-2xl transition-all duration-300 shadow-xl hover:shadow-2xl transform hover:-translate-y-2 block">
                    <i class="fas fa-eye mr-3"></i>
                    Lihat Detail
                </a>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16">
            <div class="max-w-md mx-auto bg-white/90 p-8 rounded-2xl shadow-lg border border-amber-100">
                <h3 class="text-2xl font-bold text-brown-800 mb-3 font-serif">Produk tidak ditemukan</h3>
                <p class="text-brown-700 mb-6">Coba kata kunci lain atau lihat koleksi Produk kami yang lain</p>
                <a href="{{ route('catalog.index') }}" class="inline-flex items-center bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-md hover:shadow-lg">
                    <i class="fas fa-redo mr-2"></i>Reset Pencarian
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
    <div class="mt-8">
        {{ $products->links('pagination::tailwind') }}
    </div>
    @endif
</main>

<!-- Profil Desa + Kontak (untuk navigasi di header) -->
<section id="profil-desa" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="catalog-surface rounded-2xl border border-amber-100 p-6 sm:p-10 shadow-md">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center">
                    <i class="fas fa-landmark"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Tentang Toko</h3>
                    <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                        {{ $companyContact->deskripsi_perusahaan ?? 'Katalog produk ' . ($companyContact->nama_perusahaan ?? $company->name ?? config('app.name')) . '. Temukan berbagai produk pilihan kami dan lakukan pemesanan dengan mudah.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="kontak" class="pb-14">
@if(($companyContact->phone_perusahaan ?? null) || ($companyContact->email_perusahaan ?? null) || ($companyContact->alamat_perusahaan ?? null))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="catalog-surface rounded-2xl border border-amber-100 p-6 sm:p-10 shadow-md">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Kontak</h3>
                    <div class="mt-3 space-y-2">
                        @if($companyContact->phone_perusahaan ?? null)
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-phone text-green-600 text-xs"></i>
                            </div>
                            <span>{{ $companyContact->phone_perusahaan }}</span>
                        </div>
                        @endif
                        @if($companyContact->email_perusahaan ?? null)
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-envelope text-blue-600 text-xs"></i>
                            </div>
                            <span>{{ $companyContact->email_perusahaan }}</span>
                        </div>
                        @endif
                        @if($companyContact->alamat_perusahaan ?? null)
                        <div class="flex items-center gap-3 text-sm text-gray-700">
                            <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-map-marker-alt text-red-600 text-xs"></i>
                            </div>
                            <span>{{ $companyContact->alamat_perusahaan }}</span>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
</section>

@if(session('success'))
@endif

@endsection

<script>
let currentSlide = 1;
const totalSlides = {{ $carouselProducts->count() ?: 1 }};

function showSlide(slideNumber) {
    // Hide all slides
    for (let i = 1; i <= totalSlides; i++) {
        const slide = document.getElementById('slide' + i);
        const dot = document.getElementById('dot' + i);
        
        if (i === slideNumber) {
            slide.classList.remove('opacity-0');
            slide.classList.add('opacity-100');
            dot.classList.remove('bg-amber-700/50');
            dot.classList.add('bg-amber-700/80');
        } else {
            slide.classList.remove('opacity-100');
            slide.classList.add('opacity-0');
            dot.classList.remove('bg-amber-700/80');
            dot.classList.add('bg-amber-700/50');
        }
    }
    
    currentSlide = slideNumber;
}

function nextSlide() {
    const next = currentSlide === totalSlides ? 1 : currentSlide + 1;
    showSlide(next);
}

function previousSlide() {
    const prev = currentSlide === 1 ? totalSlides : currentSlide - 1;
    showSlide(prev);
}

// Auto-play carousel
setInterval(nextSlide, 5000);
</script>