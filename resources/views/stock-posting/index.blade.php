@extends('layouts.app')

@section('title', 'Posting Stok Bulanan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Posting Stok Bulanan</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
        <div class="mb-6">
            <form action="{{ route('stock-posting.index') }}" method="GET" class="flex items-center gap-4">
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700">Tahun</label>
                    <select id="year" name="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" onchange="this.form.submit()">
                        @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($months as $month)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $month['name'] }} {{ $year }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($month['is_posted'])
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Sudah Diposting
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Belum Diposting
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @if($month['can_post'])
                                    <form action="{{ route('stock-posting.store') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin melakukan posting stok untuk bulan ini? Data yang sudah ada akan diperbarui.')">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $month['number'] }}">
                                        <input type="hidden" name="year" value="{{ $year }}">
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded-md transition-colors">
                                            {{ $month['is_posted'] ? 'Posting Ulang' : 'Posting Sekarang' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 italic font-normal">Belum tersedia</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8 bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>Informasi Posting:</strong>
                        <ul class="list-disc ml-5 mt-1 space-y-1">
                            <li>Posting stok memidahkan saldo akhir bulan ini menjadi saldo awal bulan depan.</li>
                            <li>Hal ini berguna agar laporan kartu stok bulan berikutnya lebih cepat diakses.</li>
                            <li>Anda dapat melakukan posting ulang jika ada revisi transaksi di bulan tersebut.</li>
                        </ul>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
