<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Laporan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium mb-4">Daftar Laporan</h3>
                    <ul class="space-y-2">
                        <li><a href="{{ route('reports.income_statement') }}" class="text-blue-600 hover:underline">Laporan Laba Rugi</a></li>
                        <li><a href="{{ route('reports.balance_sheet') }}" class="text-blue-600 hover:underline">Neraca</a></li>
                        <li><a href="{{ route('reports.jurnal-umum.pdf') }}" class="text-blue-600 hover:underline">Cetak Jurnal Umum (PDF)</a></li>
                        <li><a href="{{ route('reports.ledger.pdf') }}" class="text-blue-600 hover:underline">Cetak Buku Besar (PDF)</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>