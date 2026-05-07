@extends('layouts.customer')

@section('title', 'Login - CocoPop!')

@push('styles')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('auth-title').textContent = 'Selamat Datang Kembali';
        document.getElementById('auth-subtitle').textContent = 'Masuk ke akun Anda untuk melanjutkan';
    });
</script>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg p-3">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('customer.login.submit') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="login" class="block text-sm font-medium text-amber-700 mb-2">
                <i class="fas fa-envelope mr-2 text-amber-500"></i>Email atau Telepon
            </label>
            <div class="relative">
                <input id="login" 
                       type="text" 
                       name="login" 
                       value="{{ old('login') }}" 
                       required 
                       autofocus 
                       autocomplete="username"
                       class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 bg-amber-50/50 placeholder-amber-400"
                       placeholder="email@contoh.com atau 08123456789">
                @error('login')
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                @enderror
            </div>
            @error('login')
                <p class="mt-2 text-sm text-red-600 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-amber-700 mb-2">
                <i class="fas fa-lock mr-2 text-amber-500"></i>Password
            </label>
            <div class="relative">
                <input id="password" 
                       type="password" 
                       name="password" 
                       required 
                       autocomplete="current-password"
                       class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 bg-amber-50/50 placeholder-amber-400"
                       placeholder="••••••••">
                <button type="button" 
                        onclick="togglePassword('password')"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-amber-500 hover:text-amber-700">
                    <i class="fas fa-eye" id="password-toggle"></i>
                </button>
            </div>
            @error('password')
                <p class="mt-2 text-sm text-red-600 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input id="remember_me" 
                       type="checkbox" 
                       name="remember"
                       class="w-4 h-4 text-amber-600 border-amber-300 rounded focus:ring-amber-500 focus:ring-amber-500/50">
                <label for="remember_me" class="ml-2 block text-sm text-amber-700">
                    Ingat saya
                </label>
            </div>

            @if (Route::has('customer.password.request'))
                <a href="{{ route('customer.password.request') }}" 
                   class="text-sm text-amber-600 hover:text-amber-800 font-medium hover:underline transition-colors duration-200">
                    Lupa password?
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="pt-4">
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-amber-600 to-amber-700 text-white py-3 px-4 rounded-xl font-semibold hover:from-amber-700 hover:to-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-amber-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-[1.02]">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Masuk Sekarang
            </button>
        </div>

        <!-- Register Link -->
        <div class="text-center pt-4 border-t border-amber-200">
            <p class="text-amber-700 text-sm">
                Belum punya akun? 
                <a href="{{ route('customer.register') }}" 
                   class="font-semibold text-amber-600 hover:text-amber-800 hover:underline transition-colors duration-200">
                    Daftar di sini
                </a>
            </p>
        </div>
    </form>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggle = document.getElementById(inputId + '-toggle');
    
    if (input.type === 'password') {
        input.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}
</script>
@endsection
