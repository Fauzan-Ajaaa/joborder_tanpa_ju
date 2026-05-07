@extends('layouts.app')
@section('title', 'Jadwal Penyusutan - ' . $asset->nama_asset)
@section('content')
<div class="max-w-6xl mx-auto py-6 sm:px-6 lg:px-8 space-y-6">

    {{-- Header --}}
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">Jadwal Penyusutan</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $asset->nama_asset }} &mdash; <span class="capitalize">{{ $asset->tipe_asset }}</span></p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('aset.show', $asset) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Kembali</a>
            </div>
        </div>

        {{-- Mode toggle --}}
        <div class="mt-4 flex space-x-2">
            <a href="{{ route('aset.depreciation', ['asset' => $asset->id_assets, 'mode' => 'bulanan']) }}"
               class="px-3 py-1.5 text-sm rounded-md {{ $mode === 'bulanan' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Bulanan
            </a>
            <a href="{{ route('aset.depreciation', ['asset' => $asset->id_assets, 'mode' => 'tahunan']) }}"
               class="px-3 py-1.5 text-sm rounded-md {{ $mode === 'tahunan' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Tahunan
            </a>
        </div>
    </div>



    {{-- Tabel Jadwal Utama --}}
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-medium text-gray-900">
                Jadwal {{ $mode === 'tahunan' ? 'Tahunan' : 'Bulanan' }} &mdash;
                @switch($asset->depreciation_method)
                    @case('straight_line') Garis Lurus @break
                    @case('sum_of_years_digits') Jumlah Angka Tahun @break
                    @case('double_declining') Saldo Menurun Ganda @break
                @endswitch
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-8">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Beban Penyusutan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Akumulasi Penyusutan</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nilai Buku</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    {{-- Baris awal --}}
                    <tr class="bg-gray-50">
                        <td class="px-4 py-3 text-gray-400">0</td>
                        <td class="px-4 py-3 text-gray-600">Awal</td>
                        <td class="px-4 py-3 text-right text-gray-400">&mdash;</td>
                        <td class="px-4 py-3 text-right text-gray-400">Rp 0</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">Rp {{ number_format($asset->harga_perolehan, 2, ',', '.') }}</td>
                    </tr>
                    @foreach($schedule as $i => $row)
                    <tr class="hover:bg-gray-50 {{ $row['nilai_buku'] <= $asset->nilai_sisa ? 'bg-green-50' : '' }}">
                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $row['bulan_tahun'] ?? $row['tahun'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($row['beban_penyusutan'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($row['akumulasi_penyusutan'], 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $row['nilai_buku'] <= $asset->nilai_sisa ? 'text-green-700' : 'text-gray-900' }}">
                            Rp {{ number_format($row['nilai_buku'], 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-700">Total</td>
                        <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">Rp {{ number_format(collect($schedule)->sum('beban_penyusutan'), 2, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>



</div>
@endsection
