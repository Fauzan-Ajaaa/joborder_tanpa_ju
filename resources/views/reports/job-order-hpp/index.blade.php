@extends('layouts.app')

@section('title', 'Laporan HPP Job Order')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Laporan HPP Job Order</h1>
        <a href="{{ route('reports.job-order-hpp.pdf', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Export PDF
        </a>
    </div>

    <form method="GET" class="bg-white shadow sm:rounded-lg p-4 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label for="from" class="block text-sm font-medium text-gray-700">Tanggal Selesai Dari</label>
            <input type="date" name="from" id="from" value="{{ $from }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label for="to" class="block text-sm font-medium text-gray-700">Tanggal Selesai Sampai</label>
            <input type="date" name="to" id="to" value="{{ $to }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="md:col-span-2 flex space-x-3 justify-end">
            <a href="{{ route('reports.job-order-hpp') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Reset</a>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Filter
            </button>
        </div>
    </form>

    <div class="bg-white shadow sm:rounded-lg p-6 space-y-6">
        @forelse($jobs as $job)
            <div class="border rounded-lg mb-6 overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 flex justify-between items-center">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">{{ $job->kode_job }}</div>
                        <div class="text-xs text-gray-600">Tanggal Selesai: {{ optional($job->selesai_job_at)->format('d/m/Y') ?? '-' }}</div>
                        <div class="text-xs text-gray-600">Customer: {{ $job->customer_name ?? '-' }}</div>
                    </div>
                    <div class="text-right text-xs text-gray-600">
                        <div>BBB Total Job: <span class="font-semibold">Rp {{ number_format($job->total_bbb ?? 0, 0, ',', '.') }}</span></div>
                        <div>BTKL Total Job: <span class="font-semibold">Rp {{ number_format($job->total_btkl ?? 0, 0, ',', '.') }}</span></div>
                        <div>BOP Total Job: <span class="font-semibold">Rp {{ number_format($job->total_bop ?? 0, 0, ',', '.') }}</span></div>
                        <div>Total HPP Job: <span class="font-semibold">Rp {{ number_format($job->total_hpp ?? 0, 0, ',', '.') }}</span></div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">BBB</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">BTKL</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">BOP</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total HPP</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">HPP / Unit</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($job->items as $item)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                        {{ $item->product->code ?? '-' }} - {{ $item->product->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($item->quantity ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp {{ number_format($item->bbb_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp {{ number_format($item->btkl_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp {{ number_format($item->bop_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp {{ number_format($item->hpp_total ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 text-right">
                                        Rp {{ number_format($item->hpp_per_unit ?? 0, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-3 text-center text-xs text-gray-500">Tidak ada produk untuk job order ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">Belum ada job order selesai pada periode ini.</p>
        @endforelse
    </div>
</div>
@endsection
