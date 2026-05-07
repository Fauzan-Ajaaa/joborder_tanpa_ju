@extends('layouts.app')

@section('title', 'Daftar Aset Tetap')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">Daftar Aset Tetap</h1>
                    <p class="text-sm text-gray-500">Manajemen aset tetap perusahaan</p>
                </div>
                <a href="{{ route('aset.create') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                    Tambah Aset
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Aset</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Perolehan</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Masa Manfaat</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Sisa Masa Manfaat</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($assets as $asset)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $asset->nama_asset }}</div>
                                <div class="text-sm text-gray-500">
                                    <span class="capitalize">{{ $asset->tipe_asset ?? '-' }}</span>
                                    &mdash; {{ optional($asset->tanggal_perolehan)->translatedFormat('d M Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                Rp {{ number_format($asset->harga_perolehan, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                {{ $asset->masa_manfaat }} tahun
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                @php
                                    $tahunSekarang = now()->year;
                                    $tahunPerolehan = \Carbon\Carbon::parse($asset->tanggal_perolehan)->year;
                                    $tahunBerjalan = $tahunSekarang - $tahunPerolehan;
                                    $sisaMasaManfaat = max(0, $asset->masa_manfaat - $tahunBerjalan);
                                    
                                    // Tampilkan dengan warna yang sesuai
                                    $warna = 'text-green-600';
                                    if ($sisaMasaManfaat <= 1) {
                                        $warna = 'text-red-600 font-bold';
                                    } elseif ($sisaMasaManfaat <= 3) {
                                        $warna = 'text-yellow-600';
                                    }
                                @endphp
                                <span class="{{ $warna }}">
                                    {{ $sisaMasaManfaat }} tahun
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                @switch($asset->depreciation_method)
                                    @case('straight_line')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Garis Lurus</span>
                                        @break
                                    @case('double_declining')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Saldo Menurun (Ganda)</span>
                                        @break
                                    @case('sum_of_years_digits')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Jumlah Angka Tahun</span>
                                        @break
                                    @case('units_of_production')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Satuan Hasil Produksi</span>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="{{ route('aset.show', $asset->id_assets) }}" 
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Lihat
                                </a>
                                <a href="{{ route('aset.edit', $asset->id_assets) }}" 
                                   class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Edit
                                </a>
                                <form action="{{ route('aset.destroy', $asset->id_assets) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aset ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                Belum ada data aset
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($assets->hasPages())
            <div class="mt-4">
                {{ $assets->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
