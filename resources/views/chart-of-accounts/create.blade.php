<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Tambah Akun Baru
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('chart-of-accounts.store') }}" method="POST">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Kode Akun (Auto Generate) -->
                            <div>
                                <x-input-label for="account_code" :value="__('Kode Akun')" class="text-[#6D94C5] font-semibold" />
                                <x-text-input id="account_code" class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg bg-gray-100" 
                                             type="text" name="account_code" value="AUTO" readonly />
                                <x-input-error :messages="$errors->get('account_code')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Kode akun akan di-generate otomatis berdasarkan tipe akun</p>
                            </div>

                            <!-- Nama Akun -->
                            <div>
                                <x-input-label for="account_name" :value="__('Nama Akun')" class="text-[#6D94C5] font-semibold" />
                                <x-text-input id="account_name" class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg" 
                                             type="text" name="account_name" :value="old('account_name')" required autofocus />
                                <x-input-error :messages="$errors->get('account_name')" class="mt-2" />
                            </div>

                            <!-- Tipe Akun -->
                            <div>
                                <x-input-label for="account_type" :value="__('Tipe Akun')" class="text-[#6D94C5] font-semibold" />
                                <select id="account_type" name="account_type" 
                                        class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg"
                                        required>
                                    <option value="">Pilih Tipe Akun</option>
                                    <option value="asset" {{ old('account_type') == 'asset' ? 'selected' : '' }}>Aset</option>
                                    <option value="liability" {{ old('account_type') == 'liability' ? 'selected' : '' }}>Liabilitas</option>
                                    <option value="equity" {{ old('account_type') == 'equity' ? 'selected' : '' }}>Ekuitas</option>
                                    <option value="revenue" {{ old('account_type') == 'revenue' ? 'selected' : '' }}>Pendapatan</option>
                                    <option value="expense" {{ old('account_type') == 'expense' ? 'selected' : '' }}>Beban</option>
                                </select>
                                <x-input-error :messages="$errors->get('account_type')" class="mt-2" />
                            </div>

                            <!-- Saldo Awal -->
                            <div>
                                <x-input-label for="balance" :value="__('Saldo Awal (Opsional)')" class="text-[#6D94C5] font-semibold" />
                                <x-text-input id="balance" class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg" 
                                             type="number" step="0.01" name="balance" :value="old('balance', 0)" />
                                <x-input-error :messages="$errors->get('balance')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Kosongkan jika saldo awal dimulai dari 0</p>
                            </div>

                            <!-- Deskripsi -->
                            <div class="md:col-span-2">
                                <x-input-label for="description" :value="__('Deskripsi')" class="text-[#6D94C5] font-semibold" />
                                <textarea id="description" name="description" rows="3"
                                          class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                        <!-- Status Aktif -->
                            <div class="md:col-span-2">
                                <label for="is_active" class="inline-flex items-center">
                                    <input id="is_active" type="checkbox" class="rounded border-[#E8DFCA] text-[#6D94C5] shadow-sm focus:ring-[#6D94C5] focus:ring-offset-[#F5EFE6]" 
                                           name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}>
                                    <span class="ms-2 text-sm text-[#6D94C5]">{{ __('Akun Aktif') }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <a href="{{ route('chart-of-accounts.index') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#6D94C5]">
                                Batal
                            </a>
                            <x-primary-button>
                                Simpan Akun
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
