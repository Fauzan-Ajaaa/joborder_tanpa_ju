@extends('layouts.app')

@section('title', 'Edit Saldo - ' . $chartOfAccount->account_name)

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Saldo</h1>
            <p class="mt-2 text-sm text-gray-600">{{ $chartOfAccount->code }} - {{ $chartOfAccount->account_name }}</p>
            <p class="mt-1 text-sm text-gray-600">Periode: {{ \Carbon\Carbon::parse($balance->period)->format('F Y') }}</p>
        </div>
        <a href="{{ route('chart-of-accounts.manage-balance', $chartOfAccount) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali
        </a>
    </div>

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
            <form method="POST" action="{{ route('chart-of-accounts.update-balance', [$chartOfAccount, $balance->id]) }}" class="space-y-4">
                @csrf
                @method('PUT')
                
                <div>
                    <label for="balance" class="block text-sm font-medium text-gray-700">
                        Saldo {{ ucfirst($chartOfAccount->normal_balance_position) }}
                    </label>
                    <input type="number" 
                           step="0.01" 
                           id="balance" 
                           name="balance" 
                           value="{{ old('balance', $balance->ending_balance) }}" 
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-sm text-gray-500">
                        Akun ini berposisi normal di {{ $chartOfAccount->normal_balance_position }}. 
                        Masukkan nilai saldo {{ $chartOfAccount->normal_balance_position }}.
                    </p>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="{{ route('chart-of-accounts.manage-balance', $chartOfAccount) }}" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                        Update Saldo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
