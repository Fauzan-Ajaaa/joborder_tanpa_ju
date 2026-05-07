<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Posisi Keuangan') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Filter --}}
            <div class="bg-white rounded-lg shadow-sm p-4">
                <form method="GET" action="{{ route('reports.neraca') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
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
                        <a href="{{ route('reports.neraca.pdf', ['year' => $year, 'month' => $month]) }}"
                           target="_blank"
                           class="flex-1 text-center bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-1.5 px-3 rounded-md shadow-sm">
                            Cetak PDF
                        </a>
                    </div>
                </form>
            </div>

            {{-- Header Laporan --}}
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6 text-center">
                <div class="text-lg font-semibold text-gray-800">{{ auth()->user()->nama_perusahaan ?? 'Perusahaan Manufaktur' }}</div>
                <div class="text-sm text-gray-500 mt-1">Laporan Keuangan {{ \Carbon\Carbon::create($year, $month)->translatedFormat('F Y') }}</div>
                <div class="text-base font-medium text-gray-700 mt-1">Laporan Posisi Keuangan</div>
            </div>

            {{-- Balance Sheet Table --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left border border-gray-300">AKUN</th>
                            <th class="px-4 py-3 text-right border border-gray-300">JUMLAH (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- ASET --}}
                        <tr class="bg-blue-50 font-semibold text-gray-700">
                            <td colspan="2" class="px-4 py-3 border border-gray-300">ASET</td>
                        </tr>

                        {{-- Aset Lancar --}}
                        <tr class="bg-blue-100 font-medium text-gray-600">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Aset Lancar</td>
                            <td class="px-4 py-2 border border-gray-300 text-right"></td>
                        </tr>
                        @if(isset($balanceSheet['assets']['current_assets']['accounts']))
                        @foreach($balanceSheet['assets']['current_assets']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    {{ number_format($account['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        @endif
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Aset Lancar</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($balanceSheet['assets']['current_assets']['total'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Aset Tetap --}}
                        <tr class="bg-blue-100 font-medium text-gray-600">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Aset Tetap</td>
                            <td class="px-4 py-2 border border-gray-300 text-right"></td>
                        </tr>
                        @if(isset($balanceSheet['assets']['fixed_assets']['accounts']))
                        @foreach($balanceSheet['assets']['fixed_assets']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    {{ number_format($account['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        @endif
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Aset Tetap</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($balanceSheet['assets']['fixed_assets']['total'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Total Aset --}}
                        <tr class="font-bold bg-gray-100">
                            <td class="px-6 py-2 border border-gray-300 pl-8">TOTAL ASET</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($totals['assets'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- KEWAJIBAN --}}
                        <tr class="bg-green-50 font-semibold text-gray-700">
                            <td colspan="2" class="px-4 py-3 border border-gray-300">KEWAJIBAN</td>
                        </tr>

                        <tr class="bg-green-100 font-medium text-gray-600">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Kewajiban Lancar</td>
                            <td class="px-4 py-2 border border-gray-300 text-right"></td>
                        </tr>
                        @if(isset($balanceSheet['liabilities']['current_liabilities']['accounts']))
                        @foreach($balanceSheet['liabilities']['current_liabilities']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    {{ number_format($account['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        @endif
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Kewajiban Lancar</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($balanceSheet['liabilities']['current_liabilities']['total'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Total Kewajiban --}}
                        <tr class="font-bold bg-gray-100">
                            <td class="px-6 py-2 border border-gray-300 pl-8">TOTAL KEWAJIBAN</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($totals['liabilities'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- EKUITAS --}}
                        <tr class="bg-purple-50 font-semibold text-gray-700">
                            <td colspan="2" class="px-4 py-3 border border-gray-300">EKUITAS</td>
                        </tr>

                        {{-- Modal --}}
                        <tr class="bg-purple-100 font-medium text-gray-600">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Modal</td>
                            <td class="px-4 py-2 border border-gray-300 text-right"></td>
                        </tr>
                        @if(isset($balanceSheet['equity']['capital']['accounts']))
                        @foreach($balanceSheet['equity']['capital']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    {{ number_format($account['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        @endif
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Modal</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($balanceSheet['equity']['capital']['total'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Laba Ditahan --}}
                        @if(isset($balanceSheet['equity']['retained_earnings']['accounts']) && count($balanceSheet['equity']['retained_earnings']['accounts']) > 0)
                        <tr class="bg-purple-100 font-medium text-gray-600">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Laba Ditahan</td>
                            <td class="px-4 py-2 border border-gray-300 text-right"></td>
                        </tr>
                        @foreach($balanceSheet['equity']['retained_earnings']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    {{ number_format($account['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        @endif

                        {{-- Total Ekuitas --}}
                        <tr class="font-bold bg-gray-100">
                            <td class="px-6 py-2 border border-gray-300 pl-8">TOTAL EKUITAS</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($totals['equity'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- FINAL TOTAL --}}
                        <tr class="font-bold bg-yellow-100 text-gray-800">
                            <td class="px-6 py-3 border border-gray-300 pl-4">TOTAL KEWAJIBAN + EKUITAS</td>
                            <td class="px-4 py-3 border border-gray-300 text-right">
                                {{ number_format($totals['liabilities'] + $totals['equity'], 0, ',', '.') }}
                            </td>
                        </tr>

                        @if(!$isBalanced)
                        <tr class="font-bold bg-red-100 text-red-800">
                            <td class="px-6 py-3 border border-gray-300 pl-4">SELISIH</td>
                            <td class="px-4 py-3 border border-gray-300 text-right">
                                {{ number_format($totals['difference'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Balance Status --}}
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold {{ $isBalanced ? 'text-green-600' : 'text-red-600' }}">
                            {{ $isBalanced ? '✅ BALANCE' : '❌ TIDAK BALANCE' }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Status Neraca</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">
                            {{ number_format($totals['assets'], 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Total Aset</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">
                            {{ number_format($totals['liabilities'] + $totals['equity'], 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Total Kewajiban + Ekuitas</div>
                    </div>
                </div>
                
                @if(!$isBalanced)
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <div class="text-sm text-red-800">
                        <strong>Selisih:</strong> {{ number_format(abs($totals['difference']), 0, ',', '.') }}
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>