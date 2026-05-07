@extends('layouts.app')

@section('title', 'Job Order')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Daftar Pemesanan</h1>
        <a href="{{ route('job-orders.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Buat Pemesanan
        </a>
    </div>

    <div class="bg-white shadow overflow-x-auto sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Job</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl/Jam Pesanan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pembeli</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total HPP</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penjualan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($jobs as $job)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $job->kode_job }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($job->jobOrderDetails->count() > 0)
                                @foreach($job->jobOrderDetails as $index => $detail)
                                    {{ $detail->product->name }}{{ $detail->quantity ? ' (' . (int) $detail->quantity . ')' : '' }}{{ !$loop->last ? ', ' : '' }}
                                @endforeach
                            @elseif($job->product)
                                {{ $job->product->name }}{{ $job->quantity ? ' (' . (int) $job->quantity . ')' : '' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($job->jobOrderDetails->count() > 0)
                                {{ (int) $job->jobOrderDetails->sum('quantity') }}
                            @elseif($job->quantity)
                                {{ (int) $job->quantity }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $job->created_at ? $job->created_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $job->customer_name ?? $job->customer->name ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($job->status === 'completed') bg-green-100 text-green-800
                                @elseif($job->status === 'in_progress') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst(str_replace('_', ' ', $job->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @php
                                $job->loadMissing('materials', 'jobOrderDetails.product');
                                // BBB live
                                $idxBbb = $job->materials->sum(fn($m) => (float)$m->qty_total * (float)$m->harga_per_unit);
                                // Durasi: SELALU dari BOM process (sumber kebenaran) × total quantity
                                $firstProductId = $job->jobOrderDetails->first()?->product_id ?? $job->product_id;
                                $idxTotalQty = $job->jobOrderDetails->sum('quantity') ?: ($job->quantity ?? 1);
                                $idxBomProc = \App\Models\BillOfMaterialProcess::whereHas('billOfMaterial', fn($q) => $q->where('product_id', $firstProductId))->first();
                                $idxDurasiPerUnit = $idxBomProc ? ($idxBomProc->duration_minutes / 60) : (float)($job->durasi_jam ?? 0);
                                $idxDurasiJam = $idxDurasiPerUnit * $idxTotalQty;
                                // BTKL rate: dari BOM (btkl_rate_per_hour) atau snapshot
                                $idxBtklRate = (float)($job->snapshot_btkl_rate ?? 0);
                                if ($idxBtklRate <= 0) {
                                    $firstBom = \App\Models\BillOfMaterial::where('product_id', $firstProductId)->first();
                                    $idxBtklRate = $firstBom ? (float)$firstBom->btkl_rate_per_hour : 0;
                                }
                                $idxBtkl = $idxDurasiJam * $idxBtklRate;
                                // BOP: por × durasi
                                $idxPorRate = $porBulanIni ? (float)$porBulanIni->por_per_jam : 0;
                                $idxBopPor = $idxPorRate * $idxDurasiJam;
                                $idxAux = 0;
                                foreach ($job->jobOrderDetails as $d) {
                                    $bomAux = \App\Models\BillOfMaterial::with('auxiliaries')
                                        ->where('product_id', $d->product_id)->first();
                                    if (!$bomAux) continue;
                                    foreach ($bomAux->auxiliaries as $aux) {
                                        $idxAux += (float)($aux->quantity ?? 0) * (float)($aux->unit_cost ?? 0) * (float)($d->quantity ?? 0);
                                    }
                                }
                                $idxHpp = round($idxBbb + $idxBtkl + $idxBopPor + $idxAux, 2);
                            @endphp
                            @if($idxHpp > 0)
                                @if($job->status !== 'completed')
                                    <span class="text-gray-400 text-xs">~</span>
                                @endif
                                Rp {{ number_format($idxHpp, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div id="sales-status-{{ $job->id }}">
                                @php
                                    $hasSales = $job->salesTransactions()->count() > 0;
                                    $salesCount = $job->salesTransactions()->count();
                                @endphp
                                @if($hasSales)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Sudah
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Belum
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-3" id="action-buttons-{{ $job->id }}">
                                <a href="{{ route('job-orders.show', $job) }}" class="text-blue-600 hover:text-blue-900">Detail</a>
                                @if($job->status === 'completed' && !$hasSales)
                                    <button onclick="createSalesFromJobOrder({{ $job->id }})" class="text-green-600 hover:text-green-900 whitespace-nowrap">Buat Penjualan</button>
                                @elseif($hasSales)
                                    <a href="{{ route('sales.index', ['job_order_id' => $job->id]) }}" class="text-purple-600 hover:text-purple-900 whitespace-nowrap">Lihat Penjualan</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">Belum ada job order</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $jobs->links() }}
    </div>
</div>

<script>
function createSalesFromJobOrder(jobOrderId) {
    // Tampilkan loading
    const statusElement = document.getElementById(`sales-status-${jobOrderId}`);
    const actionElement = document.getElementById(`action-buttons-${jobOrderId}`);
    
    statusElement.innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><svg class="w-3 h-3 mr-1 animate-spin" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path></svg>Membuat...</span>';
    
    // Redirect ke halaman pembuatan penjualan dengan route yang benar
    window.location.href = '{{ route("sales.create.from-job-order", ":jobOrderId") }}'.replace(':jobOrderId', jobOrderId);
}

// Jika user kembali dari halaman pembuatan penjualan dengan success, update status
document.addEventListener('DOMContentLoaded', function() {
    // Check URL parameter untuk success
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('sales_created') === 'true' && urlParams.get('job_order_id')) {
        const jobOrderId = urlParams.get('job_order_id');
        updateSalesStatus(jobOrderId);
    }
});

function updateSalesStatus(jobOrderId) {
    const statusElement = document.getElementById(`sales-status-${jobOrderId}`);
    const actionElement = document.getElementById(`action-buttons-${jobOrderId}`);
    
    if (statusElement) {
        statusElement.innerHTML = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"><svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Sudah</span>';
    }
    
    if (actionElement) {
        actionElement.innerHTML = `
            <a href="/job-orders/${jobOrderId}" class="text-blue-600 hover:text-blue-900">Detail</a>
            <a href="/sales?job_order_id=${jobOrderId}" class="text-purple-600 hover:text-purple-900 whitespace-nowrap">Lihat Penjualan</a>
        `;
    }
}
</script>
@endsection
