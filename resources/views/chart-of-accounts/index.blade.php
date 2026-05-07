@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Chart of Accounts</h1>
        <div class="flex space-x-3">
            <a href="{{ route('chart-of-accounts.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Tambah Akun
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KODE AKUN</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NAMA AKUN</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NAMA KELOMPOK AKUN</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">POSISI</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SALDO AWAL</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">BULAN</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTION</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if(isset($accounts) && $accounts->count() > 0)
                    @foreach($accounts as $account)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $account->code }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $account->account_name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @switch($account->account_group_name)
                                    @case('asset')
                                        Aset
                                        @break
                                    @case('liability')
                                        Liabilitas
                                        @break
                                    @case('equity')
                                        Ekuitas
                                        @break
                                    @case('revenue')
                                        Pendapatan
                                        @break
                                    @case('expense')
                                        Beban
                                        @break
                                    @default
                                        {{ $account->account_group_name }}
                                @endswitch
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @switch($account->account_group_name)
                                    @case('asset')
                                        Debet
                                        @break
                                    @case('expense')
                                        Debet
                                        @break
                                    @case('liability')
                                        Kredit
                                        @break
                                    @case('equity')
                                        Kredit
                                        @break
                                    @case('revenue')
                                        Kredit
                                        @break
                                    @default
                                        {{ $account->normal_balance_position ?? 'Debet' }}
                                @endswitch
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp {{ number_format($account->opening_balance ?? 0, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ date('F Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('chart-of-accounts.edit', $account) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                <a href="{{ route('chart-of-accounts.manage-balance', $account) }}" class="text-green-600 hover:text-green-900">Kelola Saldo</a>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada data akun</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection