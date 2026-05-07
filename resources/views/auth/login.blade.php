<x-guest-layout>
    @slot('title', 'Masuk Ke Akun Anda')
    @slot('subtitle', 'Selamat datang kembali, Silahkan masuk untuk melanjutkan.')

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Alamat Email -->
        <div class="mb-6">
            <label for="email"
                class="block text-xs font-bold text-[#EDE9E6]/60 uppercase tracking-widest mb-3 ml-1">Email</label>
            <div class="relative group">
                <input id="email" name="email" type="email" :value="old('email')" required autofocus
                    autocomplete="username"
                    class="block w-full px-5 py-4 rounded-2xl border border-[#EDE9E6]/20 bg-[#EDE9E6]/5 text-white transition-all focus:ring-2 focus:ring-[#C9996B] focus:border-[#C9996B] focus:bg-transparent placeholder-gray-500 shadow-sm"
                    placeholder="name@example.com">
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-[#EDE9E6]/20 group-focus-within:text-[#C9996B] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                    </svg>
                </div>
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 ml-1" />
        </div>

        <!-- Password -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3 ml-1">
                <label for="password"
                    class="block text-xs font-bold text-[#EDE9E6]/60 uppercase tracking-widest">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                        class="text-xs font-bold text-[#C9996B] hover:text-[#EDE9E6] transition-colors">
                        Lupa Password?
                    </a>
                @endif
            </div>
            <div class="relative group">
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    class="block w-full px-5 py-4 rounded-2xl border border-[#EDE9E6]/20 bg-[#EDE9E6]/5 text-white transition-all focus:ring-2 focus:ring-[#C9996B] focus:border-[#C9996B] focus:bg-transparent placeholder-gray-500 shadow-sm"
                    placeholder="••••••••">
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-[#EDE9E6]/20 group-focus-within:text-[#C9996B] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 ml-1" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center mb-8">
            <input id="remember_me" type="checkbox" name="remember"
                class="h-5 w-5 text-[#C9996B] focus:ring-[#C9996B] border-[#EDE9E6]/20 rounded-md transition-all cursor-pointer bg-transparent">
            <label for="remember_me"
                class="ml-3 block text-sm text-[#EDE9E6]/60 font-medium select-none cursor-pointer">
                Ingat Saya
            </label>
        </div>

        <!-- Submit Button -->
        <div class="mb-8">
            <button type="submit"
                class="w-full relative group overflow-hidden px-6 py-5 rounded-2xl bg-[#C9996B] text-[#5C4F4A] font-extrabold text-lg shadow-xl hover:shadow-[#C9996B]/20 hover:-translate-y-1 transition-all duration-300 active:scale-[0.98]">
                <span class="relative z-10 flex items-center justify-center gap-2">
                    <span>Sign in to Account</span>
                    <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </span>
                <div
                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer">
                </div>
            </button>
        </div>

        <!-- Register Link -->
        @if (Route::has('register'))
            <div class="text-center">
                <span class="text-sm text-[#EDE9E6]/40">
                    Belum Memiliki Akun?
                </span>
                <a href="{{ route('register') }}"
                    class="ml-2 text-sm font-bold text-[#C9996B] hover:text-white transition-colors">
                    Daftar Sekarang
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>