@extends('layouts.app')

@section('title', 'Input Saldo Awal Semua Akun')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Input Saldo Awal Semua Akun</h1>
            <p class="mt-2 text-sm text-gray-600">Input saldo awal untuk semua akun sekaligus</p>
        </div>
        <a href="{{ route('chart-of-accounts.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <!-- Form untuk pilih periode -->
            <form method="GET" action="{{ route('chart-of-accounts.bulk-balance') }}" class="mb-6">
                <div class="flex items-end space-x-4">
                    <div class="flex-1">
                        <label for="period_month" class="block text-sm font-medium text-gray-700">Pilih Periode untuk Dikelola</label>
                        <input type="month" 
                               id="period_month" 
                               name="period_month" 
                               max="{{ now()->subMonth()->format('Y-m') }}"
                               value="{{ $selectedPeriod ?? old('period_month') }}"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        Tampilkan
                    </button>
                </div>
                <p class="mt-1 text-sm text-gray-500">Pilih periode untuk melihat dan mengedit saldo yang sudah ada, atau input saldo baru</p>
            </form>

            @if($selectedPeriod)
            <!-- Form untuk simpan saldo -->
            <form method="POST" action="{{ route('chart-of-accounts.store-bulk-balance') }}" class="space-y-6">
                @csrf
                
                <input type="hidden" name="period_month" value="{{ $selectedPeriod }}">

                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Periode: <strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $selectedPeriod)->format('F Y') }}</strong>. 
                                Kosongkan field saldo untuk akun yang tidak ingin diubah/diinput.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Akun</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posisi Normal</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Awal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($accounts as $account)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $account->code }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $account->account_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $account->normal_balance_position === 'debit' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ ucfirst($account->normal_balance_position) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <input type="number" 
                                               step="0.01" 
                                               name="balances[{{ $account->id }}]" 
                                               value="{{ old('balances.' . $account->id, $existingBalances[$account->id] ?? '') }}"
                                               placeholder="0"
                                               class="block w-full text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm {{ isset($existingBalances[$account->id]) ? 'bg-yellow-50' : '' }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('chart-of-accounts.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                        Simpan Semua Saldo
                    </button>
                </div>
            </form>
            @else
            <div class="text-center py-12 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-2">Pilih periode di atas untuk mulai mengelola saldo</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
