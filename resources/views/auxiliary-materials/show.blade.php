@extends('layouts.app')

@section('title', 'Detail Bahan Penolong')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Detail Bahan Penolong</h1>
        <div class="space-x-2">
            <a href="{{ route('auxiliary-materials.edit', $auxiliaryMaterial) }}" class="px-3 py-2 bg-amber-500 text-white rounded-md hover:bg-amber-600">Edit</a>
            <a href="{{ route('auxiliary-materials.index') }}" class="px-3 py-2 border rounded-md hover:bg-gray-50">Kembali</a>
        </div>
    </div>

    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Informasi Umum</h3>
                    <dl class="mt-2 space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Kode</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $auxiliaryMaterial->code }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Nama</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $auxiliaryMaterial->name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Deskripsi</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $auxiliaryMaterial->description ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $auxiliaryMaterial->supplier?->name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">COA</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                @if($auxiliaryMaterial->chartOfAccount)
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800">
                                        {{ $auxiliaryMaterial->chartOfAccount->code }}
                                    </span>
                                    <span class="ml-1 text-xs text-gray-600">{{ $auxiliaryMaterial->chartOfAccount->account_name }}</span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900">Informasi Stok</h3>
                    <dl class="mt-2 space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Stok Saat Ini</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($auxiliaryMaterial->stock, 2, ',', '.') }} {{ $auxiliaryMaterial->unit }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Harga per Satuan</dt>
                            <dd class="mt-1 text-sm text-gray-900">Rp {{ number_format($auxiliaryMaterial->price_per_unit, 0, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Satuan Dasar</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $auxiliaryMaterial->unit }}</dd>
                        </div>
                        @if($auxiliaryMaterial->recipe_unit)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Satuan Resep</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $auxiliaryMaterial->recipe_unit }}</dd>
                        </div>
                        @endif
                        @if($auxiliaryMaterial->recipe_conversion_factor)
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Faktor Konversi</dt>
                            <dd class="mt-1 text-sm text-gray-900">1 {{ $auxiliaryMaterial->unit }} = {{ rtrim(rtrim(number_format($auxiliaryMaterial->recipe_conversion_factor, 6, ',', '.'), '0'), ',') }} {{ $auxiliaryMaterial->recipe_unit }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            @if($auxiliaryMaterial->unitConversions->count() > 0)
            <div class="mt-6 pt-6 border-t">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Konversi Multi-Satuan</h3>
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dari Satuan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ke Satuan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Faktor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($auxiliaryMaterial->unitConversions as $conversion)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $conversion->from_unit }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $conversion->to_unit }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ rtrim(rtrim(number_format($conversion->factor, 6, ',', '.'), '0'), ',') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 {{ $conversion->from_unit }} = {{ rtrim(rtrim(number_format($conversion->factor, 6, ',', '.'), '0'), ',') }} {{ $conversion->to_unit }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
