<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('NERACA SALDO') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Filter Section -->
            <div class="bg-white rounded-lg shadow-sm p-4">
                <form method="GET" action="{{ route('reports.neraca-saldo') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
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
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-3 rounded-md shadow-sm">
                            Tampilkan
                        </button>
                    </div>
                    <div class="flex items-end">
                        <a href="{{ route('reports.neraca-saldo.print', ['year' => $year, 'month' => $month]) }}" 
                           target="_blank"
                           class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-1.5 px-3 rounded-md shadow-sm text-center">
                            Cetak PDF
                        </a>
                    </div>
                </form>
            </div>

            <!-- Period Info -->
            <!-- Header Laporan -->
            <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                <div class="text-lg font-semibold text-gray-800">{{ auth()->user()->nama_perusahaan ?? 'Perusahaan Manufaktur' }}</div>
                <div class="text-sm text-gray-500 mt-1">Laporan Keuangan {{ isset($period) ? $period['formatted'] : '' }}</div>
                <div class="text-base font-medium text-gray-700 mt-1">Neraca Saldo</div>
            </div>

            <!-- Trial Balance Table -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left border border-gray-300">AKUN</th>
                            <th class="px-4 py-3 text-right border border-gray-300">DEBIT (Rp)</th>
                            <th class="px-4 py-3 text-right border border-gray-300">KREDIT (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trialBalance['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 border border-gray-300">
                                    {{ $account['code'] }} - {{ $account['name'] }}
                                </td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    @if($account['debit'] > 0)
                                        {{ number_format($account['debit'], 0, ',', '.') }}
                                    @else
                                        0
                                    @endif
                                </td>
                                <td class="px-4 py-2 border border-gray-300 text-right">
                                    @if($account['credit'] > 0)
                                        {{ number_format($account['credit'], 0, ',', '.') }}
                                    @else
                                        0
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        <!-- Totals -->
                        <tr class="font-bold bg-gray-100">
                            <td class="px-4 py-2 border border-gray-300">TOTAL</td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($trialBalance['total_debit'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 border border-gray-300 text-right">
                                {{ number_format($trialBalance['total_credit'], 0, ',', '.') }}
                            </td>
                        </tr>

                        @if(!$isBalanced)
                        <tr class="font-bold bg-red-100 text-red-800">
                            <td class="px-4 py-2 border border-gray-300">SELISIH</td>
                            <td class="px-4 py-2 border border-gray-300 text-right" colspan="2">
                                {{ number_format($difference, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Balance Status -->
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
                            {{ number_format($trialBalance['total_debit'], 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Total Debit</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">
                            {{ number_format($trialBalance['total_credit'], 0, ',', '.') }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">Total Kredit</div>
                    </div>
                </div>
                
                @if(!$isBalanced)
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                    <div class="text-sm text-red-800">
                        <strong>Selisih:</strong> {{ number_format(abs($difference), 2, ',', '.') }}
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
