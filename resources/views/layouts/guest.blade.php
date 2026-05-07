<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Manufaktur Job Order Cost') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ date('YmdHis') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ date('YmdHis') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        </head>
    <body class="font-sans text-gray-900 antialiased bg-cover bg-center bg-no-repeat bg-fixed" style="background-image: url('{{ asset('images/backgrounds/auth-bg.png') }}');">
                
        <!-- Simple Overlay -->
        <div class="fixed inset-0 bg-white/5"></div>
        
        <!-- Main Content -->
        <div class="relative min-h-screen flex flex-col">
            <!-- Header Logo -->
            <div class="absolute top-4 left-4 sm:top-6 sm:left-6 z-10">
                <a href="/" class="flex items-center space-x-2 sm:space-x-3 px-4 py-2 sm:px-6 sm:py-3 rounded-xl sm:rounded-2xl bg-white/90 backdrop-blur-md shadow-lg border border-[#C9996B]/30 transition-all hover:shadow-xl hover:bg-white">
                    <x-application-logo class="h-8 sm:h-10 w-auto" />
                    <span class="text-lg sm:text-xl font-bold text-[#5C4F4A]">
                        {{ config('app.name', 'SIMAJOC') }}
                    </span>
                </a>
            </div>

            <!-- Main content -->
            <div class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
                <div class="max-w-4xl w-full bg-[#5C4F4A] rounded-[2rem] shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[600px] border border-[#C9996B]/20">
                    
                    <!-- Left Side: Branding & Batik -->
                    <div class="hidden md:flex md:w-5/12 relative overflow-hidden bg-white/5 backdrop-blur-3xl">
                        <!-- Heavily Blurred Batik Pattern -->
                        <div class="absolute inset-0 opacity-60 bg-cover bg-center blur-2xl scale-125" style="background-image: url('{{ asset('images/backgrounds/auth-bg.png') }}');"></div>
                        <div class="absolute inset-0 bg-gradient-to-br from-[#5C4F4A]/90 via-[#5C4F4A]/40 to-transparent"></div>
                        
                        <div class="relative z-10 p-10 flex flex-col justify-between h-full">
                            <div>
                                <a href="/" class="flex items-center space-x-3 mb-12">
                                    <x-application-logo class="h-12 w-auto brightness-0 invert" />
                                    <span class="text-2xl font-bold text-white tracking-wider">SIMAJOC</span>
                                </a>
                                <h2 class="text-4xl font-extrabold text-white leading-tight">
                                    Manufaktur <br>
                                    <span class="text-[#C9996B]">Job Order Costing</span>
                                </h2>
                                <p class="mt-6 text-[#EDE9E6]/80 text-lg leading-relaxed">
                                    Solusi Untuk Perusahaan Manufaktur Job Order Costing Anda.
                                </p>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-1 bg-[#C9996B]"></div>
                                <span class="text-white text-sm font-medium tracking-widest uppercase">Telkom University</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Auth Form -->
                    <div class="w-full md:w-7/12 p-8 sm:p-12 flex flex-col justify-center">
                        <div class="md:hidden flex justify-center mb-8">
                            <x-application-logo class="h-16 w-auto brightness-0 invert" />
                        </div>
                        
                        <div class="mb-10 text-center md:text-left">
                            <h1 class="text-3xl font-bold text-white">
                                {{ $title ?? 'Welcome' }}
                            </h1>
                            @isset($subtitle)
                                <p class="mt-3 text-[#EDE9E6]/60 text-sm">{{ $subtitle }}</p>
                            @endisset
                        </div>
                        
                        <div class="auth-form-container">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logo footer -->
            <div class="w-full flex justify-between items-center px-8 py-6 gap-6 border-t border-amber-300/50 backdrop-blur-md" style="background: linear-gradient(to right, #fff7ed, #fef3c7, #fff7ed);">
                <div class="text-xs text-gray-700 leading-relaxed">
                    <p class="font-bold text-sm mb-1">Sistem Informasi Akuntansi</p>
                    <p class="text-gray-600 mb-2">Fakultas Ilmu Terapan · Telkom University</p>
                    <p class="font-semibold mb-1">Developed by:</p>
                    <p>Dr. Nelsi Wisna, S.E., M.Si. &nbsp;·&nbsp; Cindy Kartika Putri &nbsp;·&nbsp; Fauzan Abiyyu Aziz &nbsp;·&nbsp; Hanina Syahida Riyanto &nbsp;·&nbsp; Wa Ode Aura Syania Azzahra</p>
                </div>
                <div class="flex items-center gap-8 flex-shrink-0">
                    <img src="{{ asset('images/logo-telkom.png') }}" alt="Telkom" class="h-24 w-auto">
                    <img src="{{ asset('images/logo-eadt.png') }}" alt="EADT" class="h-24 w-auto">
                </div>
            </div>
        </div>
    </body>
</html>
