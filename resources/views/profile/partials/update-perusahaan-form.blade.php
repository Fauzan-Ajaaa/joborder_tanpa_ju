<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            Informasi Perusahaan
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Perbarui nama dan alamat perusahaan Anda.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update-perusahaan') }}" class="mt-6 space-y-6">
        @csrf
        @method('PATCH')

        <div>
            <x-input-label for="nama_perusahaan" :value="__('Nama Perusahaan')" />
            <x-text-input id="nama_perusahaan" name="nama_perusahaan" type="text" class="mt-1 block w-full" 
                          value="{{ old('nama_perusahaan', auth()->user()->nama_perusahaan) }}" 
                          placeholder="Toko Harmoni" autofocus />
            <x-input-error :messages="$errors->get('nama_perusahaan')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="deskripsi_perusahaan" :value="__('Deskripsi Toko')" />
            <textarea id="deskripsi_perusahaan" name="deskripsi_perusahaan" rows="3"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Ceritakan tentang toko Anda...">{{ old('deskripsi_perusahaan', auth()->user()->deskripsi_perusahaan) }}</textarea>
            <x-input-error :messages="$errors->get('deskripsi_perusahaan')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="alamat_perusahaan" :value="__('Alamat Perusahaan')" />
            <textarea id="alamat_perusahaan" name="alamat_perusahaan" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
                      rows="3"
                      placeholder="Garut">{{ old('alamat_perusahaan', auth()->user()->alamat_perusahaan) }}</textarea>
            <x-input-error :messages="$errors->get('alamat_perusahaan')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone_perusahaan" :value="__('No. Telepon')" />
            <x-text-input id="phone_perusahaan" name="phone_perusahaan" type="text" class="mt-1 block w-full"
                          value="{{ old('phone_perusahaan', auth()->user()->phone_perusahaan) }}"
                          placeholder="+62 812-3456-7890" />
            <x-input-error :messages="$errors->get('phone_perusahaan')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email_perusahaan" :value="__('Email Perusahaan')" />
            <x-text-input id="email_perusahaan" name="email_perusahaan" type="email" class="mt-1 block w-full"
                          value="{{ old('email_perusahaan', auth()->user()->email_perusahaan) }}"
                          placeholder="info@perusahaan.com" />
            <x-input-error :messages="$errors->get('email_perusahaan')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Simpan') }}</x-primary-button>

            @if (session('status') == 'perusahaan-updated')
                <div class="text-sm text-green-600 font-medium">
                    {{ __('Tersimpan.') }}
                </div>
            @endif
        </div>
    </form>
</section>
