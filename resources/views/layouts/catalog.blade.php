<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Katalog Produk - Bakery')</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('logcookies.png') }}?v={{ time() }}">
    <link rel="icon" href="{{ asset('logcookies.png') }}?v={{ time() }}">

    <!-- Tailwind & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Modern clean typography (catalog only) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --catalog-font: "Poppins", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        html {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            font-family: var(--catalog-font);
            letter-spacing: 0.01em;
        }

        .catalog-batik-bg {
            position: fixed;
            inset: 0;
            background-color: #ffffff;
            pointer-events: none;
            z-index: 0;
        }

        .catalog-batik-overlay {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 30% 0%, rgba(255,255,255,0.10), rgba(255,255,255,0.45) 55%, rgba(255,255,255,0.70) 100%),
                linear-gradient(180deg, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0.32) 30%, rgba(255,255,255,0.50) 100%);
            pointer-events: none;
            z-index: 1;
        }

        .catalog-page {
            min-height: 100vh;
            position: relative;
            z-index: 2;
        }

        .catalog-surface {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(6px);
        }

        .catalog-elevated {
            box-shadow:
                0 1px 2px rgba(16, 24, 40, 0.06),
                0 8px 24px rgba(16, 24, 40, 0.08);
        }

        .catalog-link {
            position: relative;
        }

        .catalog-link::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -6px;
            height: 2px;
            width: 0;
            background: rgba(120, 53, 15, 0.45);
            transition: width 160ms ease;
        }

        .catalog-link:hover::after {
            width: 100%;
        }

        .china-trim {
            position: relative;
        }

        .china-trim::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, rgba(127,29,29,1) 0%, rgba(251,191,36,1) 50%, rgba(127,29,29,1) 100%);
        }

        .china-trim::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 15% 20%, rgba(251,191,36,0.09), rgba(251,191,36,0) 45%),
                        radial-gradient(circle at 85% 30%, rgba(127,29,29,0.10), rgba(127,29,29,0) 45%);
            pointer-events: none;
        }

        .china-btn {
            box-shadow: 0 0 0 2px rgba(251,191,36,0.30);
        }

        .china-btn:hover {
            box-shadow: 0 0 0 2px rgba(251,191,36,0.45);
        }

        .china-outline {
            position: relative;
            border-radius: 1.5rem;
            box-shadow:
                0 0 0 1px rgba(251,191,36,0.75),
                0 0 0 3px rgba(127,29,29,0.35);
        }

        .china-outline::after {
            content: "";
            position: absolute;
            inset: 6px;
            border-radius: calc(1.5rem - 6px);
            pointer-events: none;
            box-shadow:
                0 0 0 1px rgba(127,29,29,0.40),
                0 0 0 2px rgba(251,191,36,0.30);
        }

        .china-outline:hover {
            box-shadow:
                0 0 0 1px rgba(251,191,36,0.95),
                0 0 0 3px rgba(127,29,29,0.45);
        }

        .china-outline-tight {
            position: relative;
            border-radius: 1rem;
            box-shadow:
                0 0 0 1px rgba(251,191,36,0.75),
                0 0 0 3px rgba(127,29,29,0.30);
        }

        .china-outline-tight::after {
            content: "";
            position: absolute;
            inset: 5px;
            border-radius: calc(1rem - 5px);
            pointer-events: none;
            box-shadow:
                0 0 0 1px rgba(127,29,29,0.35),
                0 0 0 2px rgba(251,191,36,0.25);
        }

        .fx-3d {
            transform-style: preserve-3d;
            will-change: transform;
        }

        .fx-3d-card {
            transform-style: preserve-3d;
            transition: transform 260ms ease, box-shadow 260ms ease;
            box-shadow:
                0 10px 28px rgba(15, 23, 42, 0.10),
                0 2px 8px rgba(15, 23, 42, 0.08);
        }

        .fx-3d-card:hover {
            transform: translateY(-4px) rotateX(1.25deg) rotateY(-1.25deg);
            box-shadow:
                0 18px 40px rgba(15, 23, 42, 0.16),
                0 6px 18px rgba(15, 23, 42, 0.10);
        }

        .fx-3d-press {
            transform-style: preserve-3d;
            transition: transform 140ms ease, box-shadow 140ms ease;
        }

        .fx-3d-press:active {
            transform: translateY(1px) scale(0.99);
        }

        /* Ambient Animations */
        .floating-icon {
            position: fixed;
            pointer-events: none;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(5deg); }
            50% { transform: translateY(-10px) rotate(-3deg); }
            75% { transform: translateY(-15px) rotate(2deg); }
        }

        .shimmer {
            position: relative;
            overflow: hidden;
        }

        .shimmer::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.4),
                transparent
            );
            animation: shimmer 3s infinite;
            z-index: 1;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .sparkle {
            position: fixed;
            pointer-events: none;
            opacity: 0;
            animation: sparkle 4s linear infinite;
            z-index: 1;
        }

        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
            50% { opacity: 0.6; transform: scale(1) rotate(180deg); }
        }

        .pulse-glow {
            animation: pulseGlow 2s ease-in-out infinite;
        }

        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(251, 191, 36, 0.3); }
            50% { box-shadow: 0 0 40px rgba(251, 191, 36, 0.6); }
        }

        .fx-3d-btn {
            position: relative;
            transform-style: preserve-3d;
            transition: transform 180ms ease, box-shadow 180ms ease;
            box-shadow:
                0 10px 18px rgba(127, 29, 29, 0.22),
                0 2px 6px rgba(15, 23, 42, 0.08);
        }

        .fx-3d-btn::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(180deg, rgba(255,255,255,0.18), rgba(255,255,255,0));
            pointer-events: none;
        }

        .fx-3d-btn:hover {
            transform: translateY(-2px);
            box-shadow:
                0 14px 26px rgba(127, 29, 29, 0.26),
                0 5px 12px rgba(15, 23, 42, 0.10);
        }

        .fx-3d-btn:active {
            transform: translateY(0px) scale(0.99);
        }

        /* Font modern untuk katalog */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        :root {
            --catalog-font: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --heading-font: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            font-family: var(--catalog-font);
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--heading-font);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .brand-text {
            font-family: var(--heading-font);
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        button, input, select, textarea {
            font-family: var(--catalog-font);
        }

        /* Prevent header wrap on mobile */
        .catalog-header-brand {
            flex-shrink: 0;
            min-width: 0;
        }

        .catalog-header-brand span {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 640px) {
            .catalog-header-brand .brand-text {
                font-size: 0.8rem;
                line-height: 1.1;
            }
        }

        .notification {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%) translateY(120%);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            z-index: 1000;
            animation: slideIn 0.3s ease-out forwards;
            max-width: 90%;
            width: max-content;
            text-align: center;
        }

        .notification.success {
            background-color: #10b981; /* emerald-500 */
        }

        .notification.error {
            background-color: #ef4444; /* red-500 */
        }

        .notification.warning {
            background-color: #f59e0b; /* amber-500 */
        }

        @keyframes slideIn {
            to {
                transform: translateX(-50%) translateY(0);
            }
        }

        .fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateX(-50%) translateY(120%);
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-transparent">
    <div class="catalog-batik-bg"></div>
    <div class="catalog-batik-overlay"></div>
    <div class="catalog-page">
    @php
        $catalogCart = session('catalog_cart', []);
        $catalogCartCount = is_array($catalogCart)
            ? collect($catalogCart)->sum(function ($row) { return (int)($row['quantity'] ?? 0); })
            : 0;

        $catalogCartItems = [];
        $catalogCartSubtotal = 0;
        if (is_array($catalogCart) && ! empty($catalogCart)) {
            $productIds = array_keys($catalogCart);
            $products = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');
            foreach ($catalogCart as $productId => $row) {
                $p = $products->get($productId);
                if (! $p) {
                    continue;
                }
                $qty = (int) ($row['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $unitPrice = (float) ($row['unit_price'] ?? $p->price ?? 0);
                $lineTotal = $qty * $unitPrice;
                $catalogCartSubtotal += $lineTotal;
                $catalogCartItems[] = [
                    'product' => $p,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }
        }
    @endphp

    <!-- Header -->
    <header class="china-trim bg-gradient-to-r from-red-50 via-amber-50 to-red-100 shadow-xl sticky top-0 z-50 border-b-2 border-red-300">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
            <div class="flex justify-between items-center h-24 sm:h-28">
                <!-- Logo & Brand -->
                <a href="{{ route('catalog.index') }}" class="text-red-900 flex items-center catalog-header-brand group">
                    <span class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-xl border-2 border-red-300 shadow-xl flex-shrink-0 hover:shadow-2xl transition-all duration-300 hover:scale-105 overflow-hidden bg-red-800">
                        <img src="{{ asset('images/logo-catalog.png') }}" alt="{{ $companyContact->nama_perusahaan ?? $company->name ?? config('app.name') }}"
                             class="w-full h-full object-cover"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <span class="text-white font-black text-2xl sm:text-3xl hidden w-full h-full items-center justify-center">{{ strtoupper(substr($companyContact->nama_perusahaan ?? $company->name ?? config('app.name'), 0, 2)) }}</span>
                    </span>
                    <div class="ml-6 leading-tight">
                        <span class="block text-3xl sm:text-4xl font-bold brand-text text-red-900 group-hover:text-red-700 transition-colors">{{ $companyContact->nama_perusahaan ?? $company->name ?? config('app.name') }}</span>
                        <div class="mt-3 h-1.5 bg-gradient-to-r from-red-600 to-amber-600 w-24 rounded-full"></div>
                    </div>
                </a>

                <!-- Navigation (desktop) -->
                <nav class="hidden md:flex space-x-8 items-center ml-8">
                    <a href="{{ route('catalog.index') }}" class="text-red-900 hover:text-red-700 font-bold text-lg transition-colors hover:scale-105 transform">Produk</a>
                    <a href="#profil-desa" class="text-red-900 hover:text-red-700 font-bold text-lg transition-colors hover:scale-105 transform">Tentang Toko</a>
                    <a href="#kontak" class="text-red-900 hover:text-red-700 font-bold text-lg transition-colors hover:scale-105 transform">Kontak</a>
                    @guest
                        <a href="{{ route('customer.login') }}" class="text-red-800 hover:text-red-900 font-bold text-lg transition-colors hover:scale-105 transform">Login</a>
                        <a href="{{ route('customer.register') }}" class="bg-white text-red-700 px-5 py-3 rounded-xl font-bold text-lg hover:bg-red-50 transition border-2 border-red-300 shadow-lg hover:shadow-xl hover:scale-105 transform">
                            Daftar
                        </a>
                    @endguest
                </nav>

                <!-- Mobile actions -->
                <div class="flex items-center space-x-3 md:hidden">
                    <button class="text-maroon-800">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-red-50 via-amber-50 to-red-100 text-gray-900 mt-16 border-t border-red-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">
                        <i class="fas fa-store mr-2 text-red-700"></i>{{ $companyContact->nama_perusahaan ?? $company->name ?? config('app.name') }}
                    </h3>
                    <p class="text-gray-700">{{ $companyContact->alamat_perusahaan ?? $company->address ?? '' }}</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('catalog.index') }}" class="text-gray-800 hover:text-gray-900 transition-colors">Produk</a></li>
                        <li><a href="#profil-desa" class="text-gray-800 hover:text-gray-900 transition-colors">Tentang Toko</a></li>
                        <li><a href="#kontak" class="text-gray-800 hover:text-gray-900 transition-colors">Kontak</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Kontak Info</h4>
                    <ul class="space-y-2 text-gray-700">
                        @if($companyContact->phone_perusahaan ?? null)
                        <li><i class="fas fa-phone mr-2"></i>{{ $companyContact->phone_perusahaan }}</li>
                        @endif
                        @if($companyContact->email_perusahaan ?? null)
                        <li><i class="fas fa-envelope mr-2"></i>{{ $companyContact->email_perusahaan }}</li>
                        @endif
                        <li><i class="fas fa-map-marker-alt mr-2"></i>{{ $companyContact->alamat_perusahaan ?? ($company->address ?? '-') }}</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-red-200 mt-8 pt-8 text-center text-gray-600">
                <p>&copy; {{ date('Y') }} {{ $companyContact->nama_perusahaan ?? $company->name ?? config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')

    <script>
        function openMiniCart() {
            const overlay = document.getElementById('miniCartOverlay');
            const drawer = document.getElementById('miniCartDrawer');
            if (!overlay || !drawer) return;
            overlay.classList.remove('opacity-0', 'pointer-events-none');
            overlay.classList.add('opacity-100');
            drawer.classList.remove('translate-x-full');
        }

        function closeMiniCart() {
            const overlay = document.getElementById('miniCartOverlay');
            const drawer = document.getElementById('miniCartDrawer');
            if (!overlay || !drawer) return;
            overlay.classList.add('opacity-0', 'pointer-events-none');
            overlay.classList.remove('opacity-100');
            drawer.classList.add('translate-x-full');
        }
    </script>
    
    @if(session('success') || session('error') || session('warning'))
        <div id="notification" class="notification {{ session('success') ? 'success' : (session('error') ? 'error' : 'warning') }}">
            {{ session('success') ?? session('error') ?? session('warning') }}
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const notification = document.getElementById('notification');
                if (notification) {
                    // Hide notification after 5 seconds
                    setTimeout(() => {
                        notification.classList.add('fade-out');
                        // Remove element after animation completes
                        setTimeout(() => {
                            notification.remove();
                        }, 500);
                    }, 5000);

                    // Allow manual close on click
                    notification.addEventListener('click', function() {
                        this.classList.add('fade-out');
                        setTimeout(() => {
                            this.remove();
                        }, 500);
                    });
                }
            });
        </script>
    @endif
    </div>
</body>
</html>
