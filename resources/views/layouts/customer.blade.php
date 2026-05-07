<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- Custom Tailwind Config -->
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            brown: {
                                50: '#f8f5f0',
                                100: '#f0e8db',
                                200: '#e0d1b8',
                                300: '#c9b28c',
                                400: '#b08c63',
                                500: '#9c7552',
                                600: '#8a6247',
                                700: '#6d4d3b',
                                800: '#5a4033',
                                900: '#4c372e',
                            },
                            tanah: {
                                50: '#f9f6f0',
                                100: '#f1e9d9',
                                200: '#e3d1b3',
                                300: '#d2b284',
                                400: '#c19764',
                                500: '#b38351',
                                600: '#9f6d46',
                                700: '#85563b',
                                800: '#6e4835',
                                900: '#5b3c2f',
                            }
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gradient-to-br from-tanah-50 via-brown-50 to-tanah-100 min-h-screen">
        <!-- Background Pattern - Pola Tanah -->
        <div class="fixed inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiM2ZDRkM2IiIGZpbGwtb3BhY2l0eT0iMC40Ij48cGF0aCBkPSJNMzYgMzR2LTRoLTJ2NGgtNHYyaDR2NGgydi00aDR2LTJoLTR6bTAtMzBWMGgtMnY0aC00djJoNHY0aDJWNjBoNHYySDZ6TTYgMzR2LTRINFY0SDB2Mmg0djRoMnYtNGg0djJoLTR6TTYgNFYwSDR2NEgwdjJoNHY0aDJWNjhoNFY0SDZ6Ii8+PC9nPjwvZz48L3N2Zz4=');"></div>
        </div>

        <div class="relative z-10 min-h-screen flex flex-col sm:justify-center items-center py-12 px-4">
            <!-- Brand Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-brown-700 to-brown-900 rounded-full shadow-2xl mb-6 transform hover:scale-105 transition-transform duration-300 border-4 border-white/20">
                    <i class="fas fa-mountain text-4xl text-amber-100 animate-pulse"></i>
                </div>
                <h1 class="text-3xl font-bold text-brown-800 mb-2">Alam Nusantara</h1>
                <p class="text-brown-700 text-lg flex items-center justify-center gap-2">
                    <i class="fas fa-mountain text-brown-600"></i>
                    Keindahan Tanah Air
                    <i class="fas fa-tree text-brown-500"></i>
                </p>
            </div>

            <!-- Main Card -->
            <div class="w-full sm:max-w-md">
                <div class="bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl border-2 border-brown-100 overflow-hidden transform hover:scale-[1.02] transition-transform duration-300">
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-brown-700 via-brown-600 to-brown-800 px-8 py-6 text-center relative overflow-hidden">
                        <!-- Decorative Pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,.1) 10px, rgba(255,255,255,.1) 20px);"></div>
                        </div>
                        
                        <div class="text-white relative z-10">
                            <i class="fas fa-mountain-sun text-2xl mb-2 opacity-80"></i>
                            <h2 id="auth-title" class="text-xl font-bold">Selamat Datang</h2>
                            <p id="auth-subtitle" class="text-amber-100 text-sm mt-1">Keindahan Alam Nusantara</p>
                        </div>
                    </div>
                    
                    <!-- Card Content -->
                    <div class="px-8 py-8">
                        @yield('content')
                    </div>
                    
                    <!-- Card Footer -->
                    <div class="bg-gradient-to-r from-tanah-50 via-brown-50 to-tanah-50 px-8 py-4 border-t border-brown-100">
                        <div class="text-center">
                            <p class="text-brown-700 text-sm mb-3 flex items-center justify-center gap-2">
                                <span class="w-8 h-px bg-amber-300"></span>
                                Atau masuk dengan
                                <span class="w-8 h-px bg-amber-300"></span>
                            </p>
                            <div class="flex justify-center space-x-3">
                                <button class="w-10 h-10 bg-white border-2 border-amber-200 rounded-full flex items-center justify-center hover:bg-amber-50 hover:border-amber-300 transition-all duration-200 hover:scale-110">
                                    <i class="fab fa-google text-amber-600"></i>
                                </button>
                                <button class="w-10 h-10 bg-white border-2 border-amber-200 rounded-full flex items-center justify-center hover:bg-amber-50 hover:border-amber-300 transition-all duration-200 hover:scale-110">
                                    <i class="fab fa-facebook text-blue-600"></i>
                                </button>
                                <button class="w-10 h-10 bg-white border-2 border-amber-200 rounded-full flex items-center justify-center hover:bg-amber-50 hover:border-amber-300 transition-all duration-200 hover:scale-110">
                                    <i class="fab fa-whatsapp text-green-600"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Info Card -->
                <div class="mt-6 bg-gradient-to-r from-amber-50 to-amber-100 rounded-2xl p-4 border-2 border-amber-200/50 shadow-lg">
                    <div class="flex items-center gap-3 text-brown-800">
                        <i class="fas fa-mountain text-2xl text-amber-600"></i>
                        <div>
                            <p class="text-sm font-semibold">Keindahan Alam Indonesia</p>
                            <p class="text-xs text-brown-700">Gunung • Sawah • Pantai • Hutan</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-brown-600 text-sm mb-2 flex items-center justify-center gap-2">
                    <i class="fas fa-map-marker-alt text-brown-500"></i>
                    © 2025 Alam Nusantara - Hak Cipta Dilindungi
                </p>
                <div class="flex items-center justify-center space-x-4 text-brown-500 text-xs">
                    <a href="#" class="hover:text-brown-700 transition-colors flex items-center gap-1">
                        <i class="fas fa-shield-alt"></i> Kabijakan Privasi
                    </a>
                    <span class="text-brown-300">•</span>
                    <a href="#" class="hover:text-brown-700 transition-colors flex items-center gap-1">
                        <i class="fas fa-file-contract"></i> Syarat Layanan
                    </a>
                    <span class="text-brown-300">•</span>
                    <a href="#" class="hover:text-brown-700 transition-colors flex items-center gap-1">
                        <i class="fas fa-phone"></i> Kontak
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>