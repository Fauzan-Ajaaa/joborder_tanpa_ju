@extends('layouts.app')
@section('title', 'Detail Aset Tetap')
@section('content')
<div class="max-w-5xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Detail Aset Tetap</h1>
                    <p class="text-sm text-gray-500">Informasi lengkap aset dan jadwal penyusutan</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('aset.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Kembali</a>
                    <a href="{{ route('aset.edit', $asset) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">Edit</a>
                    <a href="{{ route('aset.depreciation', $asset) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">Jadwal Lengkap</a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h2 class="text-base font-medium text-gray-900 mb-3">Informasi Aset</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Nama Aset</dt><dd class="font-medium text-gray-900">{{ $asset->nama_asset }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Tipe Aset</dt><dd class="font-medium text-gray-900 capitalize">{{ $asset->tipe_asset ?? '-' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Tanggal Perolehan</dt><dd class="font-medium text-gray-900">{{ optional($asset->tanggal_perolehan)->translatedFormat('d F Y') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Umur Ekonomis</dt><dd class="font-medium text-gray-900">{{ $asset->masa_manfaat }} tahun</dd></div>
                    </dl>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h2 class="text-base font-medium text-gray-900 mb-3">Nilai Aset</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Harga Perolehan</dt><dd class="font-medium text-gray-900">Rp {{ number_format($asset->harga_perolehan, 0, ',', '.') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Nilai Residu</dt><dd class="font-medium text-gray-900">Rp {{ number_format($asset->nilai_sisa, 0, ',', '.') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Nilai Dapat Disusutkan</dt><dd class="font-medium text-blue-700">Rp {{ number_format($asset->harga_perolehan - $asset->nilai_sisa, 0, ',', '.') }}</dd></div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Metode Penyusutan</dt>
                            <dd>
                                @switch($asset->depreciation_method)
                                    @case('straight_line')<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Garis Lurus</span>@break
                                    @case('double_declining')<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Saldo Menurun Ganda</span>@break
                                    @case('sum_of_years_digits')<span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Jumlah Angka Tahun</span>@break
                                @endswitch
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            @php
                $totalDep = collect($schedule)->sum('beban_penyusutan');
                $lastRow  = collect($schedule)->last();
                $nilaiBukuAkhir = $lastRow ? $lastRow['nilai_buku'] : $asset->harga_perolehan;
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-xs font-medium text-blue-700">Total Beban Penyusutan</p>
                    <p class="mt-1 text-xl font-bold text-blue-900">Rp {{ number_format($totalDep, 0, ',', '.') }}</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-xs font-medium text-green-700">Nilai Buku Akhir</p>
                    <p class="mt-1 text-xl font-bold text-green-900">Rp {{ number_format($nilaiBukuAkhir, 0, ',', '.') }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-xs font-medium text-gray-600">Total Periode</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ count($schedule) }} bulan</p>
                </div>
            </div>

            <!-- Tabel Jadwal (12 baris pertama) -->
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-base font-medium text-gray-900">Jadwal Penyusutan (12 Bulan Pertama)</h2>
                    <a href="{{ route('aset.depreciation', $asset) }}" class="text-sm text-blue-600 hover:underline">Lihat semua →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Beban Penyusutan</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Akumulasi</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nilai Buku</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach(array_slice($schedule, 0, 12) as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-600">{{ $row['bulan_tahun'] }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($row['beban_penyusutan'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">Rp {{ number_format($row['akumulasi_penyusutan'], 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">Rp {{ number_format($row['nilai_buku'], 2, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
