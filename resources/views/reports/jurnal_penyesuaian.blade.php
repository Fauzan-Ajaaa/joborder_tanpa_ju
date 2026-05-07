<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jurnal Penyesuaian</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Filter --}}
            <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <form action="{{ route('reports.jurnal-penyesuaian') }}" method="GET"
                      class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Bulan</label>
                        <select name="month" class="w-full text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" @selected($month == $m)>
                                    {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tahun</label>
                        <input type="number" name="year" value="{{ $year }}" min="2000" max="2099"
                               class="w-full text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-4 rounded-md">
                            Tampilkan
                        </button>
                        <a href="{{ route('reports.jurnal-penyesuaian.pdf', ['month' => $month, 'year' => $year]) }}" 
                           target="_blank"
                           class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-4 rounded-md text-center">
                            Cetak PDF
                        </a>
                    </div>
                </form>
            </div>

            {{-- Header Laporan --}}
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6 text-center">
                <div class="text-lg font-semibold text-gray-800">{{ auth()->user()->nama_perusahaan ?? 'Perusahaan Manufaktur' }}</div>
                <div class="text-sm text-gray-500 mt-1">Periode: {{ $periode }}</div>
                <div class="text-base font-medium text-gray-700 mt-1">Jurnal Penyesuaian</div>
            </div>

            {{-- Status posting --}}
            @if(count($entries) > 0)
            <div class="mb-4 flex items-center gap-4">
                @if($isPosted)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        ✓ Sudah diposting ke Neraca Saldo
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        Belum diposting
                    </span>
                @endif

                <form action="{{ route('reports.jurnal-penyesuaian.post') }}" method="POST">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <button type="submit"
                        onclick="return confirm('Posting jurnal penyesuaian ke neraca saldo?')"
                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                        {{ $isPosted ? 'Posting Ulang' : 'Posting ke Neraca Saldo' }}
                    </button>
                </form>
            </div>
            @endif

            @if(session('success'))
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded-md text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded-md text-sm">{{ session('error') }}</div>
            @endif

            {{-- Tabel --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse border border-gray-300 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border border-gray-300 px-4 py-3 text-left font-bold text-gray-700 uppercase w-36">Tanggal</th>
                                <th class="border border-gray-300 px-4 py-3 text-left font-bold text-gray-700 uppercase">Keterangan</th>
                                <th class="border border-gray-300 px-4 py-3 text-center font-bold text-gray-700 uppercase w-28">Ref</th>
                                <th class="border border-gray-300 px-4 py-3 text-right font-bold text-gray-700 uppercase w-44">Debet</th>
                                <th class="border border-gray-300 px-4 py-3 text-right font-bold text-gray-700 uppercase w-44">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                                {{-- Baris Debet: BOP Penyusutan --}}
                                <tr class="hover:bg-gray-50">
                                    <td class="border border-gray-200 px-4 py-2 align-middle font-semibold" rowspan="2">
                                        {{ $entry['tanggal']->translatedFormat('F Y') }}
                                    </td>
                                    <td class="border border-gray-200 px-4 py-2">
                                        {{ $entry['bop_coa']->account_name ?? ('BOP-Penyusutan ' . ucfirst($entry['asset']->tipe_asset) . ' - ' . $entry['asset']->nama_asset) }}
                                    </td>
                                    <td class="border border-gray-200 px-4 py-2 text-center text-gray-500">
                                        {{ $entry['bop_coa']->code ?? '-' }}
                                    </td>
                                    <td class="border border-gray-200 px-4 py-2 text-right text-emerald-600 font-semibold">
                                        Rp {{ number_format($entry['beban'], 0, ',', '.') }}
                                    </td>
                                    <td class="border border-gray-200 px-4 py-2 text-right text-gray-300">-</td>
                                </tr>
                                {{-- Baris Kredit: Akumulasi Penyusutan --}}
                                <tr class="hover:bg-gray-50 border-b-2 border-gray-300">
                                    <td class="border border-gray-200 px-4 py-2 pl-10 text-gray-600 italic">
                                        {{ $entry['akum_coa']->account_name ?? ('Akumulasi Penyusutan ' . ucfirst($entry['asset']->tipe_asset) . ' - ' . $entry['asset']->nama_asset) }}
                                    </td>
                                    <td class="border border-gray-200 px-4 py-2 text-center text-gray-500">
                                        {{ $entry['akum_coa']->code ?? '-' }}
                                    </td>
                                    <td class="border border-gray-200 px-4 py-2 text-right text-gray-300">-</td>
                                    <td class="border border-gray-200 px-4 py-2 text-right text-rose-600 font-semibold">
                                        Rp {{ number_format($entry['beban'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-400">
                                        Tidak ada penyusutan untuk periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($entries) > 0)
                        <tfoot class="bg-gray-100 font-bold">
                            <tr class="border-t-2 border-gray-400">
                                <td colspan="3" class="border border-gray-300 px-4 py-3 text-right text-gray-700">TOTAL</td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-emerald-600">
                                    Rp {{ number_format($totalDebit, 0, ',', '.') }}
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-rose-600">
                                    Rp {{ number_format($totalCredit, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
