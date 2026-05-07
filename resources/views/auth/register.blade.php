<x-guest-layout>
    @slot('title', 'Create your account')
    @slot('subtitle', 'Join ' . config('app.name', 'Manufaktur') . ' today and start managing your manufacturing business.')

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Name -->
            <div class="mb-4">
                <label for="name" class="block text-xs font-bold text-[#EDE9E6]/60 uppercase tracking-widest mb-3 ml-1">Nama Lengkap</label>
                <input id="name" name="name" type="text" :value="old('name')" required autofocus autocomplete="name"
                    class="block w-full px-5 py-4 rounded-2xl border border-[#EDE9E6]/20 bg-[#EDE9E6]/5 text-white transition-all focus:ring-2 focus:ring-[#C9996B] focus:border-[#C9996B] focus:bg-transparent placeholder-gray-500 shadow-sm"
                    placeholder="Nama Lengkap">
                <x-input-error :messages="$errors->get('name')" class="mt-2 ml-1" />
            </div>

            <!-- Email Address -->
            <div class="mb-4">
                <label for="email" class="block text-xs font-bold text-[#EDE9E6]/60 uppercase tracking-widest mb-3 ml-1">Alamat Email</label>
                <input id="email" name="email" type="email" :value="old('email')" required autocomplete="username"
                    class="block w-full px-5 py-4 rounded-2xl border border-[#EDE9E6]/20 bg-[#EDE9E6]/5 text-white transition-all focus:ring-2 focus:ring-[#C9996B] focus:border-[#C9996B] focus:bg-transparent placeholder-gray-500 shadow-sm"
                    placeholder="name@example.com">
                <x-input-error :messages="$errors->get('email')" class="mt-2 ml-1" />
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="block text-xs font-bold text-[#EDE9E6]/60 uppercase tracking-widest mb-3 ml-1">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password"
                    class="block w-full px-5 py-4 rounded-2xl border border-[#EDE9E6]/20 bg-[#EDE9E6]/5 text-white transition-all focus:ring-2 focus:ring-[#C9996B] focus:border-[#C9996B] focus:bg-transparent placeholder-gray-500 shadow-sm"
                    placeholder="Password">
                <x-input-error :messages="$errors->get('password')" class="mt-2 ml-1" />
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label for="password_confirmation" class="block text-xs font-bold text-[#EDE9E6]/60 uppercase tracking-widest mb-3 ml-1">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    autocomplete="new-password"
                    class="block w-full px-5 py-4 rounded-2xl border border-[#EDE9E6]/20 bg-[#EDE9E6]/5 text-white transition-all focus:ring-2 focus:ring-[#C9996B] focus:border-[#C9996B] focus:bg-transparent placeholder-gray-500 shadow-sm"
                    placeholder="Konfirmasi password">
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 ml-1" />
            </div>
        </div>

        <!-- Nama Perusahaan -->
        <div class="mb-4">
            <label for="nama_perusahaan" class="block text-xs font-bold text-[#EDE9E6]/60 uppercase tracking-widest mb-3 ml-1">
                Nama Perusahaan <span class="text-[10px] text-[#EDE9E6]/40 font-normal ml-1 lowercase tracking-normal">(Opsional)</span>
            </label>
            <input id="nama_perusahaan" name="nama_perusahaan" type="text" :value="old('nama_perusahaan')"
                class="block w-full px-5 py-4 rounded-2xl border border-[#EDE9E6]/20 bg-[#EDE9E6]/5 text-white transition-all focus:ring-2 focus:ring-[#C9996B] focus:border-[#C9996B] focus:bg-transparent placeholder-gray-500 shadow-sm"
                placeholder="Misal: Perusahaan Harmoni">
            <x-input-error :messages="$errors->get('nama_perusahaan')" class="mt-2 ml-1" />
        </div>

        <!-- Alamat Perusahaan -->
        <div class="mb-8">
            <label for="alamat_perusahaan" class="block text-xs font-bold text-[#EDE9E6]/60 uppercase tracking-widest mb-3 ml-1">
                Alamat Perusahaan <span class="text-[10px] text-[#EDE9E6]/40 font-normal ml-1 lowercase tracking-normal">(Opsional)</span>
            </label>
            <input id="alamat_perusahaan" name="alamat_perusahaan" type="text" :value="old('alamat_perusahaan')"
                class="block w-full px-5 py-4 rounded-2xl border border-[#EDE9E6]/20 bg-[#EDE9E6]/5 text-white transition-all focus:ring-2 focus:ring-[#C9996B] focus:border-[#C9996B] focus:bg-transparent placeholder-gray-500 shadow-sm"
                placeholder="Misal: Jakarta">
            <x-input-error :messages="$errors->get('alamat_perusahaan')" class="mt-2 ml-1" />
        </div>

        <!-- Submit Button -->
        <div class="mb-8">
            <button type="submit"
                class="w-full relative group overflow-hidden px-6 py-5 rounded-2xl bg-[#C9996B] text-[#5C4F4A] font-extrabold text-lg shadow-xl hover:shadow-[#C9996B]/20 hover:-translate-y-1 transition-all duration-300 active:scale-[0.98]">
                <span class="relative z-10 flex items-center justify-center gap-2">
                    <span>Create Account</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </span>
                <div
                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:animate-shimmer">
                </div>
            </button>
        </div>

        <!-- Terms and Login Link -->
        <div class="text-center space-y-6">
            <div class="text-center">
                <span class="text-sm text-[#EDE9E6]/40">
                    Sudah Memiliki Akun ?
                </span>
                <a href="{{ route('login') }}"
                    class="ml-2 text-sm font-bold text-[#C9996B] hover:text-white transition-colors">
                    Sign in
                </a>
            </div>
        </div>
    </form>
</x-guest-layout>