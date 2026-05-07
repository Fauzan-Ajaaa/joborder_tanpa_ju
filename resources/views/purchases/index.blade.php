@extends('layouts.app')

@section('title', $type === 'auxiliary_material' ? 'Pembelian Bahan Penolong' : 'Pembelian Bahan Baku')

@section('content')
<div class="space-y-6">
    <!-- Tabs: Pembelian Bahan Baku | Pembelian Bahan Penolong -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="{{ route('purchases.index', ['type' => 'raw_material']) }}"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                    @if($type === 'raw_material')
                        border-blue-500 text-blue-600
                    @else
                        border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700
                    @endif">
                Pembelian Bahan Baku
            </a>
            <a href="{{ route('purchases.index', ['type' => 'auxiliary_material']) }}"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
                    @if($type === 'auxiliary_material')
                        border-blue-500 text-blue-600
                    @else
                        border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700
                    @endif">
                Pembelian Bahan Penolong
            </a>
        </nav>
    </div>

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $type === 'auxiliary_material' ? 'Pembelian Bahan Penolong' : 'Pembelian Bahan Baku' }}</h1>
            <p class="mt-1 text-sm text-gray-600">Kelola purchase order dengan FOB dan PPN</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('purchase-returns.index') }}"
                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent
                      rounded-md font-semibold text-xs text-white uppercase tracking-widest
                      hover:bg-red-700 focus:bg-red-700 active:bg-red-800
                      focus:outline-none focus:ring-2 focus:ring-red-300 transition ease-in-out duration-150">
                Daftar Retur
            </a>
            <a href="{{ route('purchases.create', ['type' => $type]) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Buat Purchase Order
            </a>
        </div>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Total PO</div>
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-blue-50 rounded-lg shadow p-4">
            <div class="text-sm text-blue-600">Draft</div>
            <div class="text-2xl font-bold text-blue-900">{{ $stats['draft'] }}</div>
        </div>
        <div class="bg-green-50 rounded-lg shadow p-4">
            <div class="text-sm text-green-600">Approved</div>
            <div class="text-2xl font-bold text-green-900">{{ $stats['approved'] }}</div>
        </div>
        <div class="bg-purple-50 rounded-lg shadow p-4">
            <div class="text-sm text-purple-600">Received</div>
            <div class="text-2xl font-bold text-purple-900">{{ $stats['received'] }}</div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white shadow overflow-x-auto sm:rounded-lg">
        <table class="min-w-max w-full divide-y divide-gray-200 pr-6">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FOB</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PPN</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metode</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bayar</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-56">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($purchases as $purchase)
                @php
                // Calculate taxable and non-taxable subtotals from items
                $taxableSubtotal = 0; // before_tax items
                $nonTaxableSubtotal = 0; // after_tax items (sudah termasuk PPN)
                
                foreach ($purchase->items as $item) {
                    if ($item->tax_type === 'after_tax') {
                        $nonTaxableSubtotal += $item->subtotal;
                    } else {
                        $taxableSubtotal += $item->subtotal;
                    }
                }
                
                // Get rates from purchase object
                $discountRateCalc = $purchase->discount_rate ?? 0;
                $ppnRateCalc = $purchase->ppn_rate ?? 11;
                $fobCostCalc = $purchase->fob_cost ?? 0;
                
                // Metode A: Diskon dari DPP Asli
                // 1. Hitung Total Harga Termasuk PPN (dari after_tax items) + Harga Sebelum PPN (dari before_tax items)
                $totalHargaTermasukPpnCalc = $taxableSubtotal + $nonTaxableSubtotal;
                
                // 2. Hitung DPP Asli
                // Untuk before_tax items: sudah DPP, tidak perlu dibagi 1.11
                // Untuk after_tax items: perlu dibagi 1.11 untuk dapat DPP
                $dppAsliCalc = $taxableSubtotal + ($nonTaxableSubtotal / (1 + ($ppnRateCalc / 100)));
                
                // 3. Hitung Diskon dari DPP Asli
                $discAmountCalc = $dppAsliCalc * ($discountRateCalc / 100);
                
                // 4. Hitung DPP Baru setelah diskon
                $dppBaruCalc = $dppAsliCalc - $discAmountCalc;
                
                // 5. Hitung PPN: PPN = 11% x DPP Baru
                $ppnAmountCalc = $dppBaruCalc * ($ppnRateCalc / 100);
                
                // 6. Total Barang = DPP Baru + PPN
                $totalBarangCalc = $dppBaruCalc + $ppnAmountCalc;
                
                // 7. Total Keseluruhan = Total Barang + FOB
                $totalAmountCalc = $totalBarangCalc + $fobCostCalc;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $purchase->purchase_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $purchase->purchase_date->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $purchase->supplier?->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $purchase->items->count() }} item(s)
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-left">
                        Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-left">
                        Rp {{ number_format($fobCostCalc, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-left">
                        Rp {{ number_format($ppnAmountCalc, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-left">
                        Rp {{ number_format($totalAmountCalc, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($purchase->payment_method == 'cash')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                            </svg>
                            Tunai
                        </span>
                        @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd" />
                            </svg>
                            Hutang
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($purchase->payment_method == 'cash')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Lunas
                        </span>
                        @else
                        @if($purchase->payment_status == 'paid')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Lunas
                        </span>
                        @elseif($purchase->payment_status == 'partial')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            Sebagian
                        </span>
                        @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            Belum
                        </span>
                        @endif
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            @if($purchase->status == 'draft') bg-gray-100 text-gray-800
                            @elseif($purchase->status == 'approved') bg-blue-100 text-blue-800
                            @elseif($purchase->status == 'received') bg-green-100 text-green-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($purchase->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right text-xs font-medium align-top whitespace-nowrap">
                        <a href="{{ route('purchases.show', $purchase) }}" class="text-blue-600 hover:text-blue-900">Lihat</a>
                        @if($purchase->status == 'received')
                        <span class="mx-1">|</span>
                        <a href="{{ route('purchase-returns.create', ['purchase_id' => $purchase->id]) }}" class="text-red-600 hover:text-red-900">Retur</a>
                        @endif
                        @if($purchase->status == 'draft')
                        <span class="mx-1">|</span>
                        <a href="{{ route('purchases.edit', $purchase) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                        <span class="mx-1">|</span>
                        <form action="{{ route('purchases.approve', $purchase) }}" method="POST" class="inline"
                            onsubmit="return confirm('Approve purchase order ini?')">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                        </form>
                        <span class="mx-1">|</span>
                        <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="inline"
                            onsubmit="return confirm('Yakin ingin menghapus Purchase Order ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Hapus</button>
                        </form>
                        @elseif($purchase->status == 'approved')
                        <span class="mx-1">|</span>
                        <form action="{{ route('purchases.receive', $purchase) }}" method="POST" class="inline"
                            onsubmit="return confirm('Terima purchase order ini? Stok {{ ($purchase->purchase_type ?? 'raw_material') === 'auxiliary_material' ? 'bahan penolong' : 'bahan baku' }} akan diupdate.')">
                            @csrf
                            <button type="submit" class="text-purple-600 hover:text-purple-900">Terima & Update Stok</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-gray-500 text-lg font-medium">Belum ada Purchase Order</p>
                            <p class="text-gray-400 text-sm mt-1">Klik tombol "Buat Purchase Order" untuk membuat PO baru</p>
                            <a href="{{ route('purchases.create', ['type' => $type]) }}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                Buat Purchase Order
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($purchases->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $purchases->links() }}
        </div>
        @endif
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Tentang Modul Pembelian</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li><strong>FOB (Freight on Board):</strong> Biaya pengiriman barang</li>
                        <li><strong>PPN:</strong> Pajak Pertambahan Nilai (default 11%)</li>
                        <li><strong>Total:</strong> Subtotal + FOB + PPN</li>
                        <li><strong>Status:</strong> Draft → Approved → Received (stok otomatis bertambah)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection