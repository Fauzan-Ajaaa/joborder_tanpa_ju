@extends('layouts.app')

@section('title', 'Edit Beban Overhead')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Edit Beban Overhead</h1>
        <a href="{{ route('biaya-overhead.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Kembali</a>
    </div>

    <div class="bg-white shadow sm:rounded-lg p-6">
        <form action="{{ route('biaya-overhead.update', $overhead) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Periode</label>
                <input type="month" name="periode" value="{{ old('periode', optional($overhead->periode)->format('Y-m')) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('periode')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kategori</label>
                    <select name="kategori" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        @foreach($kategoriOptions as $opt)
                            <option value="{{ $opt }}" {{ old('kategori', $overhead->kategori ?? 'BOP') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('kategori')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Jenis Biaya</label>
                    <input type="text" name="jenis_biaya" value="{{ old('jenis_biaya', $overhead->jenis_biaya) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    @error('jenis_biaya')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Akun Beban (COA Kepala 5)</label>
                <select name="chart_of_account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Pilih Akun Beban --</option>
                    @foreach($expenseAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ (int) old('chart_of_account_id', $overhead->chart_of_account_id) === $acc->id ? 'selected' : '' }}>
                            {{ $acc->account_code }} - {{ $acc->account_name }}
                        </option>
                    @endforeach
                </select>
                @error('chart_of_account_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Total Biaya</label>
                <input type="number" step="0.01" name="total" value="{{ old('total', $overhead->total) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('total')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
                <textarea name="catatan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('catatan', $overhead->catatan) }}</textarea>
                @error('catatan')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
