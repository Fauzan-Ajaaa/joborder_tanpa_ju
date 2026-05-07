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

<body class="font-sans antialiased batik-pattern"
    style="background-image: url('{{ asset('images/backgrounds/auth-bg.png') }}');">
    <!-- Batik Overlay -->
    <div class="fixed inset-0 pointer-events-none bg-white bg-opacity-70 -z-10"></div>
    <!-- Batik Sidebars -->
    <div class="batik-sidebar-right"></div>

    <div class="min-h-screen flex flex-col">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white border-b border-gray-300">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @yield('content')
                @isset($slot)
                    {{ $slot }}
                @endisset
            </div>
        </main>
        <!-- Dashboard Footer (Scroll only) -->
        @if(request()->routeIs('dashboard'))
            <div
                class="w-full flex justify-between items-center px-10 py-5 gap-6 border-t border-amber-800/10 backdrop-blur-md mt-auto bg-dashboard-theme">
                <div class="text-xs text-gray-700 leading-tight pl-2">
                    <p class="font-bold text-base mb-1 text-amber-900">Sistem Informasi Akuntansi</p>
                    <p class="text-gray-600 mb-2">Fakultas Ilmu Terapan · Telkom University</p>
                    <p class="font-semibold mb-1">Developed by:</p>
                    <p class="leading-relaxed">Dr. Nelsi Wisna, S.E., M.Si. &nbsp;·&nbsp; Cindy Kartika Putri &nbsp;·&nbsp;
                        Fauzan Abiyyu Aziz &nbsp;·&nbsp; Hanina Syahida Riyanto &nbsp;·&nbsp; Wa Ode Aura Syania Azzahra</p>
                </div>
                <div class="flex items-center gap-6 flex-shrink-0">
                    <img src="{{ asset('images/logo-telkom.png') }}" alt="Telkom" class="h-20 w-auto">
                    <img src="{{ asset('images/logo-eadt.png') }}" alt="EADT" class="h-20 w-auto">
                </div>
            </div>
        @endif
    </div>
    @stack('scripts')
</body>

</html>