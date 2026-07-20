<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buku Besar') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Filter --}}
            <div class="bg-white rounded-lg shadow-sm p-4">
                <form method="GET" action="{{ route('reports.buku-besar') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bulan</label>
                        <select name="month" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ (int)$month === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input type="number"
                               name="year"
                               value="{{ $year }}"
                               min="2000"
                               max="2099"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Akun</label>
                        <select name="account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Pilih Akun</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}"
                                        {{ $accountId == $account->id ? 'selected' : '' }}>
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
                                formaction="{{ route('reports.buku-besar.pdf') }}"
                                formmethod="GET"
                                formtarget="_blank"
                                class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-1.5 px-3 rounded-md shadow-sm">
                            Cetak PDF
                        </button>
                    </div>
                </form>
            </div>




            {{-- Navigasi Posting (Hanya Tombol) --}}
            @if($selectedAccount)
                @php
                    $calculatedFinalBalance = $journalItems->last()?->running_balance ?? $saldoAwal;
                    $periodDateStr = \Carbon\Carbon::create($year, $month, 1)->format('Y-m-d');
                    $isAccountPosted = \App\Models\AccountPeriodBalance::where('chart_of_account_id', $accountId)
                        ->where('period', $periodDateStr)
                        ->where('is_posted', true)
                        ->exists();
                @endphp

                <div class="flex flex-wrap gap-2 justify-end">
                    <form action="{{ route('reports.buku-besar.post-manual') }}" method="POST">
                        @csrf
                        <input type="hidden" name="account_id" value="{{ $accountId }}">
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <input type="hidden" name="balance" value="{{ $calculatedFinalBalance }}">
                        
                        <button type="submit" 
                                title="Posting/Update HANYA akun ini"
                                class="{{ $isAccountPosted ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white text-[10px] font-bold py-2 px-4 rounded shadow-sm transition duration-150">
                            {{ $isAccountPosted ? 'POSTING AKUN INI' : 'POSTING AKUN INI' }}
                        </button>
                    </form>

                    <button type="button" 
                            onclick="submitPosting()"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-bold py-2 px-4 rounded shadow-sm transition duration-150"
                            title="Posting/Update SEMUA akun aktif untuk periode {{ $month }}/{{ $year }}">
                        POSTING SEMUA AKUN
                    </button>

                    <a href="{{ route('chart-of-accounts.manage-balance', $selectedAccount) }}" 
                       class="bg-white hover:bg-gray-50 text-gray-500 text-[10px] font-bold py-2 px-4 rounded border border-gray-300 transition duration-150">
                        KELOLA COA
                    </a>
                </div>
            @endif

            {{-- Header Laporan --}}
            <div class="bg-white rounded-lg shadow-sm p-6 relative">
                <div class="text-center">
                    <div class="text-lg font-semibold text-gray-800">{{ auth()->user()->nama_perusahaan ?? 'PT. Manufaktur' }}</div>
                    <div class="text-sm text-gray-500 mt-1">Laporan Keuangan {{ isset($month) && isset($year) ? \Carbon\Carbon::create($year, $month)->translatedFormat('F Y') : '' }}</div>
                    <div class="text-base font-medium text-gray-700 mt-1">Buku Besar</div>
                    @if($selectedAccount)
                        <div class="text-sm text-gray-600 mt-1">
                            {{ $selectedAccount->code }} - {{ $selectedAccount->account_name }}
                        </div>
                    @endif
                </div>
                
                {{-- Hidden form for "Posting Semua Akun" functionality --}}
                <form action="{{ route('reports.buku-besar.post') }}" method="POST" id="posting-form" class="hidden">
                    @csrf
                    <input type="hidden" name="month" id="post-month" value="{{ $month }}">
                    <input type="hidden" name="year" id="post-year" value="{{ $year }}">
                </form>
                
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Table --}}
            @if(isset($selectedAccount) && $selectedAccount)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kredit</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase"></th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 uppercase">Kredit</th>
                                    <th class="px-6 py-2 text-right text-xs font-medium text-gray-500 uppercase"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                {{-- SELALU TAMPILKAN SALDO AWAL --}}
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \Carbon\Carbon::create($year, $month, 1)->subDay()->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Saldo Awal</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                                        {{-- Tampilkan saldo awal di kolom sesuai normal_balance_position --}}
                                        @if($selectedAccount->normal_balance_position == 'debit' && $saldoAwal != 0)
                                            {{ number_format(abs($saldoAwal), 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                                        @if($selectedAccount->normal_balance_position == 'credit' && $saldoAwal != 0)
                                            {{ number_format(abs($saldoAwal), 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                        {{-- Tampilkan saldo awal sesuai normal balance, selalu positif untuk akun kredit --}}
                                        @if($saldoAwal != 0)
                                            {{ number_format(abs($saldoAwal), 0, ',', '.') }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>

                                @php $running = $saldoAwal ?? 0; @endphp
                                @foreach($journalItems as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $item->journalEntry->transaction_date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $item->journalEntry->description ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $item->journalEntry->journal_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                                            {{ $item->debit > 0 ? number_format($item->debit, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                                            {{ $item->credit > 0 ? number_format($item->credit, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                            {{-- Tampilkan saldo selalu positif untuk semua jenis akun --}}
                                            {{ number_format(abs($item->running_balance), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- Jika tidak ada transaksi, tampilkan pesan di dalam tabel --}}
                                @if($journalItems->isEmpty())
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-400 italic">
                                        Tidak ada transaksi pada periode ini
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="p-8 text-center text-gray-500">
                    <p>Silakan pilih akun untuk menampilkan buku besar</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

<script>
function submitPosting() {
    const monthEl = document.querySelector('select[name="month"]');
    const yearEl = document.querySelector('input[name="year"]');
    const month = monthEl ? monthEl.value : '{{ $month }}';
    const year = yearEl ? yearEl.value : '{{ $year }}';
    
    if (!confirm(`Posting buku besar SEMUA AKUN periode ${month}/${year}?\nSaldo akhir semua akun akan menjadi saldo awal bulan berikutnya.`)) {
        return;
    }
    
    document.getElementById('post-month').value = month;
    document.getElementById('post-year').value = year;
    document.getElementById('posting-form').submit();
}

// Update badge dan tombol posting saat filter berubah
document.addEventListener('DOMContentLoaded', function() {
    const monthEl = document.querySelector('select[name="month"]');
    const yearEl = document.querySelector('input[name="year"]');
    
    function updatePostingButton() {
        const month = monthEl ? monthEl.value : '{{ $month }}';
        const year = yearEl ? yearEl.value : '{{ $year }}';
        
        // Update hidden inputs
        const postMonth = document.getElementById('post-month');
        const postYear = document.getElementById('post-year');
        if (postMonth) postMonth.value = month;
        if (postYear) postYear.value = year;
    }
    
    if (monthEl) monthEl.addEventListener('change', updatePostingButton);
    if (yearEl) yearEl.addEventListener('input', updatePostingButton);
});
</script>