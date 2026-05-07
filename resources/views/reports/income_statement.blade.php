<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Laba Rugi') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Filter --}}
            <div class="bg-white rounded-lg shadow-sm p-4">
                <form method="GET" action="{{ route('reports.laba-rugi') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bulan</label>
                        <select name="month" class="w-full text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" @selected((int)$month === $m)>
                                    {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tahun</label>
                        <input type="number" name="year" min="2000" max="2099"
                               value="{{ $year }}"
                               class="w-full text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end gap-2">
                        <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-3 rounded-md shadow-sm">
                            Tampilkan
                        </button>
                        <a href="{{ route('reports.laba-rugi.pdf', ['year' => $year, 'month' => $month]) }}"
                           target="_blank"
                           class="flex-1 text-center bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-1.5 px-3 rounded-md shadow-sm">
                            Cetak PDF
                        </a>
                    </div>
                </form>
            </div>

            {{-- Header --}}
            <div class="bg-white rounded-lg shadow-sm p-6 text-center space-y-1">
                <div class="text-lg font-semibold text-gray-800">{{ auth()->user()->nama_perusahaan ?? 'Perusahaan Manufaktur' }}</div>
                <div class="text-sm text-gray-500">Laporan Keuangan {{ \Carbon\Carbon::create($year, $month)->translatedFormat('F Y') }}</div>
                <div class="text-base font-medium text-gray-700">Laporan Laba Rugi</div>
            </div>

            {{-- Tabel --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                @php
                    $sections = [
                        ['label' => 'Pendapatan', 'color' => 'bg-blue-50',  'rows' => $revenues, 'total' => $totalRevenue],
                        ['label' => 'Biaya',      'color' => 'bg-red-50',   'rows' => $expenses, 'total' => $totalExpense],
                    ];
                @endphp

                <table class="w-full text-sm border border-gray-100">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-4 py-2 text-left">Kode Akun</th>
                            <th class="px-4 py-2 text-left">Nama Akun</th>
                            <th class="px-4 py-2 text-right">Saldo (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sections as $section)
                            <tr class="{{ $section['color'] }} font-semibold text-gray-700">
                                <td colspan="3" class="px-4 py-3">{{ $section['label'] }}</td>
                            </tr>

                            @forelse($section['rows'] as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2">{{ $row['code'] }}</td>
                                    <td class="px-4 py-2">{{ $row['name'] }}</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ number_format($row['amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-center text-gray-400">Belum ada data</td>
                                </tr>
                            @endforelse

                            <tr class="font-semibold text-gray-800 border-t">
                                <td colspan="2" class="px-4 py-2 text-right">Total {{ $section['label'] }}</td>
                                <td class="px-4 py-2 text-right">
                                    {{ number_format($section['total'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach

                        <tr class="bg-emerald-50 font-semibold text-gray-800">
                            <td colspan="2" class="px-4 py-3 text-right">Laba/Rugi Bersih</td>
                            <td class="px-4 py-3 text-right">
                                {{ number_format($netIncome, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
