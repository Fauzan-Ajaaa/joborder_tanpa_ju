<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Jurnal Umum') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
           {{-- Filter Card --}}
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="{{ route('reports.jurnal-umum') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label for="month" class="block text-xs font-medium text-gray-700 mb-1">Bulan</label>
            <select id="month" name="month" class="w-full text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($month == $m)>
                        {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
    <label for="year" class="block text-xs font-medium text-gray-700 mb-1">Tahun</label>
    <input type="number" 
           id="year" 
           name="year" 
           value="{{ $year }}" 
           min="2000" 
           max="2099" 
           step="1" 
           class="w-full text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
</div>
        <div>
            <label for="account_id" class="block text-xs font-medium text-gray-700 mb-1">Akun</label>
            <select id="account_id" name="account_id" class="w-full text-sm border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                <option value="">Semua Akun</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" @selected($accountId == $account->id)>
                        {{ $account->code }} - {{ $account->account_name }}
                    </option>
                @endforeach
            </select>
        </div>
       <div class="flex items-end gap-2 md:mt-6">
    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-3 rounded-md shadow-sm">
        Tampilkan
    </button>

    <button type="submit"
            formaction="{{ route('reports.jurnal-umum.pdf') }}"
            formmethod="GET"
            formtarget="_blank"
            class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-1.5 px-3 rounded-md shadow-sm">
        Cetak PDF
    </button>
</div>
    </form>
</div>
            {{-- Card Judul --}}
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6 text-center">
                <div class="text-lg font-semibold text-gray-800 mb-1">{{ (auth()->user()->nama_perusahaan ?? 'Perusahaan Manufaktur') }}</div>
                <div class="text-sm text-gray-500 mb-1">Laporan Keuangan {{ $periode }}</div>
                <div class="text-base font-medium text-gray-700">Jurnal Umum</div>
            </div>

            {{-- Table Card --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse border border-gray-300 text-sm">
                        <thead class="bg-gray-100">
                            <tr class="border-b border-gray-300">
                                <th class="border border-gray-300 px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-wider w-32">Tanggal</th>
                                <th class="border border-gray-300 px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-wider w-64">Nama Akun</th>
                                <th class="border border-gray-300 px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-wider w-24">Nomor Akun</th>
                                <th class="border border-gray-300 px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-wider w-56">Keterangan</th>
                                <th class="border border-gray-300 px-4 py-3 text-right font-bold text-gray-700 uppercase tracking-wider w-40">Debit</th>
                                <th class="border border-gray-300 px-4 py-3 text-right font-bold text-gray-700 uppercase tracking-wider w-40">Kredit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php $groupedItems = $items->groupBy('journal_entry_id'); @endphp
                            @forelse($groupedItems as $entryItems)
                                @foreach($entryItems as $index => $item)
                                    <tr class="hover:bg-gray-50 border-b border-gray-200">
                                        <td class="border border-gray-200 px-4 py-2 align-top">
                                            @if($index === 0 && $item->journalEntry->source_type !== 'hpp')
                                                <div class="font-semibold">{{ \Carbon\Carbon::parse($item->journalEntry->transaction_date)->translatedFormat('d M Y') }}</div>
                                                <div class="text-xs text-gray-400">{{ $item->journalEntry->reference ?? '-' }}</div>
                                            @endif
                                        </td>
                                        <td class="border border-gray-200 px-4 py-2 font-medium" style="@if($item->debit > 0) text-align: left; @else text-align: center; @endif">{{ $item->chartOfAccount->account_name ?? '-' }}</td>
                                        <td class="border border-gray-200 px-4 py-2 text-center">{{ $item->chartOfAccount->code ?? '-' }}</td>
                                        <td class="border border-gray-200 px-4 py-2">
                                            @if($index === 0)
                                                <div class="font-medium">{{ $item->journalEntry->description }}</div>
                                            @endif
                                            <div class="text-xs text-gray-500">{{ $item->description ?? '' }}</div>
                                        </td>
                                        <td class="border border-gray-200 px-4 py-2 text-right text-emerald-600 font-semibold">
                                            @if($item->debit > 0)
                                                Rp {{ number_format($item->debit, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="border border-gray-200 px-4 py-2 text-right text-rose-600 font-semibold">
                                            @if($item->credit > 0)
                                                Rp {{ number_format($item->credit, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-400">
                                        Belum ada transaksi untuk periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($items->count() > 0)
                        <tfoot class="bg-gray-100 font-bold">
                            <tr class="border-t-2 border-gray-400">
                                <td colspan="4" class="border border-gray-300 px-4 py-3 text-right text-gray-700 bg-gray-200">TOTAL:</td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-emerald-600 bg-gray-200">
                                    Rp {{ number_format($totalDebit, 0, ',', '.') }}
                                </td>
                                <td class="border border-gray-300 px-4 py-3 text-right text-rose-600 bg-gray-200">
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
