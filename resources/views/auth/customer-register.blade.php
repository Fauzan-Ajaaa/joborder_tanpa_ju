@extends('layouts.customer')

@section('title', 'Register - CocoPop!')

@push('styles')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('auth-title').textContent = 'Buat Akun Baru';
        document.getElementById('auth-subtitle').textContent = 'Bergabung dengan CocoPop! dan dapatkan penawaran spesial';
    });
</script>
@endpush

@section('content')
<div class="space-y-6">
    <form method="POST" action="{{ route('customer.register.submit') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-amber-700 mb-2">
                <i class="fas fa-user mr-2 text-amber-500"></i>Nama Lengkap
            </label>
            <div class="relative">
                <input id="name" 
                       type="text" 
                       name="name" 
                       value="{{ old('name') }}" 
                       required 
                       autofocus 
                       autocomplete="name"
                       class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 bg-amber-50/50 placeholder-amber-400"
                       placeholder="John Doe">
                @error('name')
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                @enderror
            </div>
            @error('name')
                <p class="mt-2 text-sm text-red-600 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-amber-700 mb-2">
                <i class="fas fa-envelope mr-2 text-amber-500"></i>Email Address
            </label>
            <div class="relative">
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autocomplete="username"
                       class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 bg-amber-50/50 placeholder-amber-400"
                       placeholder="nama@email.com">
                @error('email')
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                @enderror
            </div>
            @error('email')
                <p class="mt-2 text-sm text-red-600 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block text-sm font-medium text-amber-700 mb-2">
                <i class="fas fa-phone mr-2 text-amber-500"></i>Nomor Telepon
            </label>
            <div class="relative">
                <input id="phone" 
                       type="tel" 
                       name="phone" 
                       value="{{ old('phone') }}" 
                       required 
                       autocomplete="tel"
                       class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 bg-amber-50/50 placeholder-amber-400"
                       placeholder="08123456789">
                @error('phone')
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                @enderror
            </div>
            @error('phone')
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
                       autocomplete="new-password"
                       class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 bg-amber-50/50 placeholder-amber-400"
                       placeholder="Minimal 8 karakter">
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
            <!-- Password Strength Indicator -->
            <div class="mt-2">
                <div class="flex items-center justify-between text-xs text-amber-600 mb-1">
                    <span>Kekuatan Password</span>
                    <span id="password-strength-text">-</span>
                </div>
                <div class="w-full bg-amber-200 rounded-full h-2">
                    <div id="password-strength" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-amber-700 mb-2">
                <i class="fas fa-check-circle mr-2 text-amber-500"></i>Konfirmasi Password
            </label>
            <div class="relative">
                <input id="password_confirmation" 
                       type="password" 
                       name="password_confirmation" 
                       required 
                       autocomplete="new-password"
                       class="w-full px-4 py-3 border-2 border-amber-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all duration-200 bg-amber-50/50 placeholder-amber-400"
                       placeholder="Ulangi password">
                <button type="button" 
                        onclick="togglePassword('password_confirmation')"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-amber-500 hover:text-amber-700">
                    <i class="fas fa-eye" id="password_confirmation-toggle"></i>
                </button>
            </div>
            @error('password_confirmation')
                <p class="mt-2 text-sm text-red-600 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Terms and Privacy -->
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start">
                <input id="terms" 
                       type="checkbox" 
                       required
                       class="w-4 h-4 text-amber-600 border-amber-300 rounded focus:ring-amber-500 focus:ring-amber-500/50 mt-1">
                <label for="terms" class="ml-3 text-sm text-amber-700">
                    Saya setuju dengan 
                    <a href="#" class="text-amber-600 hover:text-amber-800 font-medium hover:underline">Syarat & Ketentuan</a> 
                    dan 
                    <a href="#" class="text-amber-600 hover:text-amber-800 font-medium hover:underline">Kebijakan Privasi</a>
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4">
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-amber-600 to-amber-700 text-white py-3 px-4 rounded-xl font-semibold hover:from-amber-700 hover:to-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-amber-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-[1.02]">
                <i class="fas fa-user-plus mr-2"></i>
                Buat Akun Sekarang
            </button>
        </div>

        <!-- Login Link -->
        <div class="text-center pt-4 border-t border-amber-200">
            <p class="text-amber-700 text-sm">
                Sudah punya akun? 
                <a href="{{ route('customer.login') }}" 
                   class="font-semibold text-amber-600 hover:text-amber-800 hover:underline transition-colors duration-200">
                    Masuk di sini
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

// Password Strength Checker
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    const strengthBar = document.getElementById('password-strength');
    const strengthText = document.getElementById('password-strength-text');
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    const strengthLevels = [
        { width: '0%', color: 'bg-red-500', text: '-' },
        { width: '25%', color: 'bg-red-500', text: 'Lemah' },
        { width: '50%', color: 'bg-yellow-500', text: 'Sedang' },
        { width: '75%', color: 'bg-blue-500', text: 'Kuat' },
        { width: '100%', color: 'bg-green-500', text: 'Sangat Kuat' }
    ];
    
    const level = strengthLevels[strength];
    strengthBar.style.width = level.width;
    strengthBar.className = `h-2 rounded-full transition-all duration-300 ${level.color}`;
    strengthText.textContent = level.text;
});
</script>
@endsection
