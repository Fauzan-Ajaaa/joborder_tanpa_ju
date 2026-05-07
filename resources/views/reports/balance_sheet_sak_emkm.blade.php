<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan Posisi Keuangan - SAK EMKM') }}
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
                                <option value="{{ $m }}" @selected((int)$period['month'] === $m)>
                                    {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tahun</label>
                        <input type="number" name="year" min="2000" max="2099"
                               value="{{ $period['year'] }}"
                               class="w-full text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-3 rounded-md shadow-sm">
                            Tampilkan
                        </button>
                    </div>
                </form>
            </div>

            {{-- System Status and Warnings --}}
            @if(!$systemStatus['has_initial_capital'] || !$isBalanced || !empty($systemStatus['warnings']))
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Status Sistem</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            @foreach($systemStatus['warnings'] as $warning)
                                <p>• {{ $warning }}</p>
                            @endforeach
                            
                            @if(!$isBalanced)
                                <p class="font-semibold">• Neraca tidak seimbang! Selisih: Rp {{ number_format($totals['difference'], 0, ',', '.') }}</p>
                            @endif
                            
                            @if(!$systemStatus['has_initial_capital'])
                                <p class="font-semibold">• Rekomendasi: Catat modal awal untuk menyeimbangkan neraca</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Header --}}
            <div class="bg-white rounded-lg shadow-sm p-6 text-center space-y-1">
                <div class="text-lg font-semibold text-gray-800">{{ (auth()->user()->nama_perusahaan ?? 'Perusahaan Manufaktur') }}</div>
                <div class="text-sm text-gray-500">Laporan Posisi Keuangan</div>
                <div class="text-sm text-gray-400">
                    Periode: {{ $period['formatted'] }}
                </div>
                <div class="text-xs text-gray-400">
                    Berdasarkan Standar Akuntansi Keuangan Entitas Mikro dan Kecil (SAK EMKM)
                </div>
            </div>

            {{-- Tabel Neraca --}}
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
                        @foreach($balanceSheet['assets']['current_assets']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    {{ number_format($account['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Aset Lancar</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($balanceSheet['assets']['current_assets']['total'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Aset Tetap --}}
                        <tr class="bg-blue-100 font-medium text-gray-600">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Aset Tetap</td>
                            <td class="px-4 py-2 border border-gray-300 text-right"></td>
                        </tr>
                        @foreach($balanceSheet['assets']['fixed_assets']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    {{ number_format($account['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Aset Tetap</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($balanceSheet['assets']['fixed_assets']['total'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Contra Assets --}}
                        @if(!empty($balanceSheet['assets']['contra_assets']['accounts']))
                        <tr class="bg-blue-100 font-medium text-gray-600">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Kontra Aset</td>
                            <td class="px-4 py-2 border border-gray-300 text-right"></td>
                        </tr>
                        @foreach($balanceSheet['assets']['contra_assets']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    ({{ number_format($account['balance'], 0, ',', '.') }})
                                </td>
                            </tr>
                        @endforeach
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Kontra Aset</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                ({{ number_format($balanceSheet['assets']['contra_assets']['total'], 0, ',', '.') }})
                            </td>
                        </tr>
                        @endif

                        {{-- Total Aset --}}
                        <tr class="bg-blue-200 font-bold text-gray-800">
                            <td class="px-4 py-3 border border-gray-300">TOTAL ASET</td>
                            <td class="px-4 py-3 border border-gray-300 text-right">
                                {{ number_format($totals['assets'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- LIABILITAS --}}
                        <tr class="bg-red-50 font-semibold text-gray-700">
                            <td colspan="2" class="px-4 py-3 border border-gray-300">LIABILITAS</td>
                        </tr>

                        {{-- Liabilitas Jangka Pendek --}}
                        <tr class="bg-red-100 font-medium text-gray-600">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Liabilitas Jangka Pendek</td>
                            <td class="px-4 py-2 border border-gray-300 text-right"></td>
                        </tr>
                        @foreach($balanceSheet['liabilities']['current_liabilities']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    {{ number_format($account['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Liabilitas Jangka Pendek</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($balanceSheet['liabilities']['current_liabilities']['total'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Total Liabilitas --}}
                        <tr class="bg-red-200 font-bold text-gray-800">
                            <td class="px-4 py-3 border border-gray-300">TOTAL LIABILITAS</td>
                            <td class="px-4 py-3 border border-gray-300 text-right">
                                {{ number_format($totals['liabilities'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- EKUITAS --}}
                        <tr class="bg-emerald-50 font-semibold text-gray-700">
                            <td colspan="2" class="px-4 py-3 border border-gray-300">EKUITAS</td>
                        </tr>

                        {{-- Modal --}}
                        <tr class="bg-emerald-100 font-medium text-gray-600">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Modal</td>
                            <td class="px-4 py-2 border border-gray-300 text-right"></td>
                        </tr>
                        @foreach($balanceSheet['equity']['capital']['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-2 border border-gray-300 pl-12">{{ $account['code'] }} - {{ $account['name'] }}</td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    {{ number_format($account['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Modal</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($balanceSheet['equity']['capital']['total'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Laba Ditahan --}}
                        <tr class="bg-emerald-100 font-medium text-gray-600">
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
                        <tr class="font-medium">
                            <td class="px-6 py-2 border border-gray-300 pl-8">Jumlah Laba Ditahan</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($balanceSheet['equity']['retained_earnings']['total'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Total Ekuitas --}}
                        <tr class="bg-emerald-200 font-bold text-gray-800">
                            <td class="px-4 py-3 border border-gray-300">TOTAL EKUITAS</td>
                            <td class="px-4 py-3 border border-gray-300 text-right">
                                {{ number_format($totals['equity'], 0, ',', '.') }}
                            </td>
                        </tr>

                        {{-- Total Liabilitas + Ekuitas --}}
                        <tr class="bg-gray-200 font-bold text-gray-800">
                            <td class="px-4 py-3 border border-gray-300">TOTAL LIABILITAS + EKUITAS</td>
                            <td class="px-4 py-3 border border-gray-300 text-right">
                                {{ number_format($totals['liabilities'] + $totals['equity'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-6 space-y-2">
                    <p class="text-sm text-gray-600 text-center font-medium">
                        <strong>Persamaan Dasar Akuntansi:</strong> Aset = Liabilitas + Ekuitas
                    </p>
                    <p class="text-sm text-gray-600 text-center">
                        {{ number_format($totals['assets'], 0, ',', '.') }} = 
                        {{ number_format($totals['liabilities'], 0, ',', '.') }} + 
                        {{ number_format($totals['equity'], 0, ',', '.') }}
                    </p>
                    <p class="text-sm {{ $isBalanced ? 'text-green-600' : 'text-red-600' }} text-center font-bold">
                        {{ $isBalanced ? '✓ NERACA SEIMBANG' : '✗ NERACA TIDAK SEIMBANG' }}
                    </p>
                </div>
            </div>

            {{-- Catatan --}}
            @if(!$systemStatus['has_initial_capital'])
            <div class="bg-blue-50 rounded-lg p-4">
                <h4 class="font-semibold text-blue-800 mb-2">Catatan Sistem:</h4>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Sistem mendeteksi belum adanya transaksi modal awal</li>
                    <li>• Saldo negatif pada akun tertentu mungkin disebabkan oleh ketiadaan modal awal</li>
                    <li>• Untuk menyeimbangkan neraca, disarankan mencatat jurnal modal awal</li>
                    <li>• Sistem tidak melakukan penyesuaian saldo secara otomatis untuk menjaga integritas data</li>
                </ul>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

