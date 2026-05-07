@extends('layouts.app')

@section('title', 'Bill of Materials')

@section('content')
<div class="space-y-6">

    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Bill of Materials</h1>
        <a href="{{ route('bill-of-materials.create') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Tambah BOM
        </a>
    </div>

    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded">{{ session('info') }}</div>
    @endif
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">

            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama BOM</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Bahan Baku</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total BTKL</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total BOP</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Biaya</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($boms as $bom)
                <tr>

                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $bom->code }}
                    </td>

                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div class="font-semibold">{{ $bom->name }}</div>
                        @if($bom->description)
                            <div class="text-gray-500 text-xs">
                                {{ Str::limit($bom->description, 50) }}
                            </div>
                        @endif
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($bom->product)
                            {{ $bom->product->name }}
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>

                    @php
                        $durasiJam   = ($bom->processes->first()?->duration_minutes ?? 0) / 60;
                        $totalBtkl   = (float)$bom->btkl_rate_per_hour * $durasiJam;
                        $totalBopPor = (float)$bom->bop_rate_per_hour  * $durasiJam;
                        $totalAux    = 0;
                        foreach ($bom->auxiliaries as $aux) {
                            $totalAux += (float)($aux->quantity ?? 0) * (float)($aux->unit_cost ?? 0);
                        }
                        $totalBop   = $totalBopPor + $totalAux;
                        $totalBb    = $bom->items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost);
                        $totalBiaya = $totalBb + $totalBtkl + $totalBop;
                    @endphp

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                        Rp {{ number_format(round($totalBb), 0, ',', '.') }}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                        Rp {{ number_format(round($totalBtkl), 0, ',', '.') }}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                        Rp {{ number_format(round($totalBop), 0, ',', '.') }}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">
                        Rp {{ number_format(round($totalBiaya), 0, ',', '.') }}
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($bom->is_active ?? true)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Aktif
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Nonaktif
                            </span>
                        @endif
                    </td>

                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <a href="{{ route('bill-of-materials.show', $bom) }}"
                           class="text-blue-600 hover:text-blue-900">
                           Lihat
                        </a>

                        <a href="{{ route('bill-of-materials.edit', $bom) }}"
                           class="text-yellow-600 hover:text-yellow-900">
                           Edit
                        </a>

                        <form action="{{ route('bill-of-materials.destroy', $bom) }}"
                              method="POST"
                              class="inline"
                              onsubmit="return confirm('Yakin ingin hapus BOM ini? Data tidak bisa dikembalikan.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-red-600 hover:text-red-900">
                                Hapus
                            </button>
                        </form>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                        Belum ada data Bill of Materials
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>
    </div>

    <div class="mt-4">
        {{ $boms->links() }}
    </div>

</div>
@endsection