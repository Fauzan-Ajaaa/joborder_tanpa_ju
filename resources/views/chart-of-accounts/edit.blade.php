<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Edit Akun
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('chart-of-accounts.update', $chartOfAccount) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Kode Akun (Readonly) -->
                            <div>
                                <x-input-label for="account_code" :value="__('Kode Akun')" class="text-[#6D94C5] font-semibold" />
                                <x-text-input id="account_code" class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg bg-gray-100" 
                                             type="text" name="account_code" :value="old('account_code', $chartOfAccount->account_code)" readonly />
                                <x-input-error :messages="$errors->get('account_code')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Kode akun tidak dapat diubah</p>
                            </div>

                            <!-- Nama Akun (Readonly) -->
                            <div>
                                <x-input-label for="account_name" :value="__('Nama Akun')" class="text-[#6D94C5] font-semibold" />
                                <x-text-input id="account_name" class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg bg-gray-100" 
                                             type="text" name="account_name" :value="old('account_name', $chartOfAccount->account_name)" readonly />
                                <x-input-error :messages="$errors->get('account_name')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Nama akun tidak dapat diubah</p>
                            </div>

                            <!-- Nama Kelompok Akun (Readonly) -->
                            <div>
                                <x-input-label for="account_group_name" :value="__('Nama Kelompok Akun')" class="text-[#6D94C5] font-semibold" />
                                <x-text-input id="account_group_name" class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg bg-gray-100" 
                                             type="text" name="account_group_name" :value="old('account_group_name', $chartOfAccount->account_group_name)" readonly />
                                <x-input-error :messages="$errors->get('account_group_name')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Kelompok akun tidak dapat diubah</p>
                            </div>

                            <!-- Posisi Saldo Normal -->
                            <div>
                                <x-input-label for="normal_balance_position" :value="__('Posisi Saldo Normal')" class="text-[#6D94C5] font-semibold" />
                                <select id="normal_balance_position" name="normal_balance_position" 
                                        class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg"
                                        required>
                                    <option value="">Pilih Posisi</option>
                                    <option value="debit" {{ old('normal_balance_position', $chartOfAccount->normal_balance_position) == 'debit' ? 'selected' : '' }}>Debit</option>
                                    <option value="credit" {{ old('normal_balance_position', $chartOfAccount->normal_balance_position) == 'credit' ? 'selected' : '' }}>Kredit</option>
                                </select>
                                <x-input-error :messages="$errors->get('normal_balance_position')" class="mt-2" />
                            </div>

                            <!-- Saldo Awal -->
                            <div>
                                <x-input-label for="opening_balance" :value="__('Saldo Awal')" class="text-[#6D94C5] font-semibold" />
                                <x-text-input id="opening_balance" class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg" 
                                             type="number" step="0.01" name="opening_balance" :value="old('opening_balance', $chartOfAccount->opening_balance)" />
                                <x-input-error :messages="$errors->get('opening_balance')" class="mt-2" />
                                <p class="text-xs text-gray-500 mt-1">Saldo awal periode</p>
                            </div>

                            <!-- Deskripsi -->
                            <div class="md:col-span-2">
                                <x-input-label for="description" :value="__('Deskripsi')" class="text-[#6D94C5] font-semibold" />
                                <textarea id="description" name="description" rows="3"
                                          class="block mt-1 w-full border-2 border-[#E8DFCA] focus:border-[#6D94C5] focus:ring-[#E8DFCA] rounded-lg">{{ old('description', $chartOfAccount->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <a href="{{ route('chart-of-accounts.index') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#6D94C5]">
                                Batal
                            </a>
                            <x-primary-button>
                                Update Akun
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
