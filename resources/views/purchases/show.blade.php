@extends('layouts.app')

@section('title', 'Detail Purchase Order')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Purchase Order #{{ $purchase->purchase_number }}</h1>
            <p class="mt-1 text-sm text-gray-600">Detail pembelian {{ $purchase->purchase_type === 'auxiliary_material' ? 'bahan penolong' : 'bahan baku' }}</p>
        </div>
        <div class="flex gap-2">
            @if($purchase->status == 'draft')
            <a href="{{ route('purchases.edit', $purchase) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-indigo-700 shadow-md">
                Edit
            </a>
            <form action="{{ route('purchases.approve', $purchase) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-green-700 shadow-md"
                    onclick="return confirm('Approve purchase order ini?')">
                    Approve
                </button>
            </form>
            @endif

            @if($purchase->status == 'approved')
            <a href="#receive-form" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-purple-700 shadow-md">
                Terima & Update Stok
            </a>
            @endif

            @if($purchase->status == 'received')
            <a href="{{ route('purchase-returns.create', ['purchase_id' => $purchase->id]) }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-red-700 shadow-md">
                Buat Retur
            </a>
            @endif

            <a href="{{ route('purchases.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-gray-700 shadow-md">
                Kembali
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Purchase Info -->
    @php
    // FIX: Baca tax_type langsung tanpa isset() agar benar pada Eloquent model
    $hasBeforeTax = false;
    $hasAfterTax  = false;
    foreach ($purchase->items as $item) {
        $taxType = $item->tax_type ?? null;
        if ($taxType === 'before_tax') $hasBeforeTax = true;
        if ($taxType === 'after_tax')  $hasAfterTax  = true;
    }
    @endphp

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">Informasi Purchase Order</h3>
            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                @if($purchase->status == 'draft') bg-gray-100 text-gray-800
                @elseif($purchase->status == 'approved') bg-blue-100 text-blue-800
                @elseif($purchase->status == 'received') bg-green-100 text-green-800
                @else bg-red-100 text-red-800
                @endif">
                {{ ucfirst($purchase->status) }}
            </span>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">PO Number</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ $purchase->purchase_number }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tanggal Pembelian</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchase->purchase_date->format('d F Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchase->supplier?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">FOB Type</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($purchase->fob_type == 'shipping_point')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Shipping Point (Pembeli bayar ongkir)
                        </span>
                        @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Destination (Penjual bayar ongkir)
                        </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Keterangan Pajak</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($hasBeforeTax && $hasAfterTax)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Campuran (Sebelum & Termasuk Pajak)
                        </span>
                        @elseif($hasBeforeTax)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Sebelum Pajak (PPN {{ number_format($purchase->ppn_rate, 0) }}%)
                        </span>
                        @elseif($hasAfterTax)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Termasuk Pajak (Tidak ada PPN tambahan)
                        </span>
                        @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Tidak ada item
                        </span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($purchase->status) }}</dd>
                </div>
                @if($purchase->receivedBy)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Diterima Oleh</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchase->receivedBy->name }}</dd>
                </div>
                @endif
                @if($purchase->invoice_number)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nomor Faktur</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ $purchase->invoice_number }}</dd>
                </div>
                @endif
                @if($purchase->receipt_document_path)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Bukti Penerimaan Barang</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <a href="{{ asset('storage/' . $purchase->receipt_document_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">
                            Lihat Bukti
                        </a>
                    </dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">Metode Pembayaran</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @if($purchase->payment_method == 'cash')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Tunai
                        </span>
                        @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            Hutang
                        </span>
                        @endif
                    </dd>
                </div>
                @if($purchase->payment_method == 'credit' && $purchase->due_date)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Jatuh Tempo</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchase->due_date->format('d F Y') }}</dd>
                </div>
                @endif
                @if($purchase->payment_method == 'credit')
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status Pembayaran</dt>
                    <dd class="mt-1 text-sm text-gray-900">
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
                            Belum Lunas
                        </span>
                        @endif
                    </dd>
                </div>
                @endif
                @if($purchase->notes)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Catatan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $purchase->notes }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Items -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Item Pembelian</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $purchase->purchase_type === 'auxiliary_material' ? 'Bahan Penolong' : 'Bahan Baku' }}</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pajak</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($purchase->items as $item)
                    @php $itemTaxType = $item->tax_type ?? null; @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $item->item_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                        @php
                            $storedQty    = (float) $item->quantity;   // always stored in base unit
                            $selectedUnit = $item->unit;               // unit chosen at purchase time
                            $convFactor   = (float) ($item->conversion_factor ?? 0);
                            $material     = $item->rawMaterial ?? $item->auxiliaryMaterial;
                            $baseUnit     = $material ? $material->unit : $selectedUnit;

                            // Helper: no decimals if whole, max 2 decimal places otherwise
                            $fmt = fn($n) => ($n == floor($n))
                                ? number_format((int) $n, 0, ',', '.')
                                : number_format($n, 2, ',', '.');

                            $output = '';

                            if ($convFactor > 0 && $selectedUnit && strtolower($selectedUnit) !== strtolower($baseUnit)) {
                                // Case A: user bought in an alt unit (e.g. PTG while base is EKR)
                                // storedQty = base qty; purchaseQty = storedQty * convFactor
                                // Example: stored 4 EKR, factor 6  →  show "24 PTG : 4 EKR"
                                $purchaseQty = $storedQty * $convFactor;
                                $output = $fmt($purchaseQty) . ' ' . strtoupper($selectedUnit)
                                        . ' : ' . $fmt($storedQty) . ' ' . strtoupper($baseUnit);
                            } else {
                                // Case B: user bought in base unit (e.g. 500 EKR, base = EKR)
                                // convFactor = 1 baseUnit gives N altUnits (e.g. 1 EKR = 6 PTG → factor 6)
                                // Use unitConversions only to discover the alt unit NAME.
                                // Always use item's convFactor for the math (unitConversions.factor may be stale).
                                $altLabel = null;

                                if ($convFactor > 0 && $material) {
                                    $conv = $material->unitConversions
                                        ->first(fn($c) => strtolower($c->from_unit) === strtolower($baseUnit));

                                    if ($conv && $conv->to_unit) {
                                        $altQty   = $storedQty * $convFactor; // e.g. 500 × 6 = 3.000
                                        $altLabel = $fmt($storedQty) . ' ' . strtoupper($baseUnit)
                                                  . ' : ' . $fmt($altQty) . ' ' . strtoupper($conv->to_unit);
                                    }
                                }

                                $output = $altLabel ?? $fmt($storedQty) . ' ' . strtoupper($selectedUnit ?? $baseUnit);
                            }

                            echo $output;
                        @endphp
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                            @php
                                // Use the stored display unit price directly
                                $displayUnitPrice = $item->display_unit_price;
                                echo 'Rp ' . number_format($displayUnitPrice, 0, ',', '.');
                                
                                if ($item->tax_type === 'after_tax') {
                                    $beforeTax = $displayUnitPrice / (1 + ($purchase->ppn_rate / 100));
                                    echo '<div class="text-xs text-gray-500">Sebelum PPN: Rp ' . number_format($beforeTax, 0, ',', '.') . '</div>';
                                }
                            @endphp
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                            @if($itemTaxType === 'before_tax')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Sebelum Pajak
                            </span>
                            @elseif($itemTaxType === 'after_tax')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Termasuk Pajak
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                -
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                            @php
                                // Use the stored display subtotal directly
                                $displaySubtotal = $item->display_subtotal;
                                echo 'Rp ' . number_format($displaySubtotal, 0, ',', '.');
                                
                                if ($itemTaxType === 'after_tax') {
                                    $beforeTax = $displaySubtotal / (1 + ($purchase->ppn_rate / 100));
                                    echo '<div class="text-xs text-gray-500">Sebelum PPN: Rp ' . number_format($beforeTax, 0, ',', '.') . '</div>';
                                }
                            @endphp
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cost Summary + Returns -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Ringkasan Biaya</h3>
        </div>
        <div class="px-6 py-4">
            @php
            $completedReturns  = $purchase->returns?->where('status', 'completed') ?? collect();
            $totalReturnAmount = $completedReturns->sum('total_return_amount');

            // FIX: Baca tax_type langsung tanpa isset() agar benar pada Eloquent model
            $taxableSubtotal    = 0; // item before_tax → dikenai PPN
            $nonTaxableSubtotal = 0; // item after_tax  → harga sudah include PPN
            foreach ($purchase->items as $item) {
                $taxType = $item->tax_type ?? null;
                
                // Use the stored display subtotal directly
                $displaySubtotal = $item->display_subtotal;
                
                if ($taxType === 'after_tax') {
                    $nonTaxableSubtotal += $displaySubtotal;
                } else {
                    $taxableSubtotal += $displaySubtotal;
                }
            }

            $subtotal       = $taxableSubtotal + $nonTaxableSubtotal;
            $fobCost        = $purchase->fob_cost ?? 0;
            $ppnRate        = $purchase->ppn_rate ?? 0;
            $discountRate   = $purchase->discount_rate ?? 0;
            $discountAmount = $purchase->discount_amount ?? 0;

            // Metode A: Diskon dari DPP Asli
            // 1. Hitung Total Harga Termasuk PPN (dari after_tax items) + Harga Sebelum PPN (dari before_tax items)
            $totalHargaTermasukPpn = $taxableSubtotal + $nonTaxableSubtotal;
            
            // 2. Hitung DPP Asli
            // Untuk before_tax items: sudah DPP, tidak perlu dibagi 1.11
            // Untuk after_tax items: perlu dibagi 1.11 untuk dapat DPP
            $dppAsli = $taxableSubtotal + ($nonTaxableSubtotal / (1 + ($ppnRate / 100)));
            
            // 3. Hitung Diskon dari DPP Asli
            $discAmount = $dppAsli * ($discountRate / 100);
            
            // 4. Hitung DPP Baru setelah diskon
            $dppBaru = $dppAsli - $discAmount;
            
            // 5. Hitung PPN: PPN = 11% x DPP Baru
            $ppnTotal = $dppBaru * ($ppnRate / 100);
            
            // 6. Total Barang = DPP Baru + PPN
            $totalBarang = $dppBaru + $ppnTotal;
            
            // 7. Total Keseluruhan = Total Barang + FOB
            $total = $totalBarang + $fobCost;

            // Subtotal + PPN (sebelum FOB)
            $subtotalPlusPpn = $totalBarang;

            $totalAmount = $total;
            $netTotal = $totalAmount - $totalReturnAmount;
            @endphp

            <div class="space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Total Harga Termasuk PPN:</span>
                    <span class="font-medium text-blue-600">Rp {{ number_format($totalHargaTermasukPpn, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">DPP Asli:</span>
                    <span class="font-medium text-purple-600">Rp {{ number_format($dppAsli, 0, ',', '.') }}</span>
                </div>
                @if($discountRate > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Diskon ({{ number_format($discountRate, 0) }}% dari DPP Asli):</span>
                    <span class="font-medium text-red-600">− Rp {{ number_format($discAmount, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">DPP Baru:</span>
                    <span class="font-medium text-green-600">Rp {{ number_format($dppBaru, 0, ',', '.') }}</span>
                </div>
                @if($ppnTotal > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">PPN {{ number_format($ppnRate, 0) }}%:</span>
                    <span class="font-medium">Rp {{ number_format($ppnTotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm text-xs text-gray-500">
                    <span class="text-gray-500">Detail PPN:</span>
                    <span class="font-medium text-gray-500">DPP Baru: Rp {{ number_format($dppBaru, 0, ',', '.') }} × {{ number_format($ppnRate, 0) }}% = Rp {{ number_format($ppnTotal, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="border-t border-gray-300 my-1"></div>
                <div class="flex justify-between text-sm font-medium">
                    <span class="text-gray-700">Subtotal + PPN</span>
                    <span class="font-medium">Rp {{ number_format($totalBarang, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">FOB</span>
                    <span class="font-medium">Rp {{ number_format($fobCost, 0, ',', '.') }}</span>
                </div>
                <div class="border-t-2 border-gray-400 my-1"></div>
                <div class="flex justify-between text-lg font-bold">
                    <span class="text-gray-900">TOTAL</span>
                    <span class="text-blue-600">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>

                @if($purchase->payment_method == 'credit')
                <div class="border-t pt-2 mt-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Total Hutang:</span>
                        <span class="font-medium">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Sudah Dibayar:</span>
                        <span class="font-medium text-green-600">Rp {{ number_format($purchase->paid_amount ?? 0, 0, ',', '.') }}</span>
                    </div>
                    @php
                    $remaining = round($totalAmount - ($purchase->paid_amount ?? 0));
                    @endphp
                    <div class="flex justify-between text-sm font-bold">
                        <span class="text-gray-900">Sisa Hutang:</span>
                        @if($remaining < 0)
                        <span class="font-medium text-green-600">
                            Rp {{ number_format(abs($remaining), 0, ',', '.') }} (Lebih Bayar)
                        </span>
                        @else
                        <span class="font-medium text-red-600">
                            Rp {{ number_format($remaining, 0, ',', '.') }}
                        </span>
                        @endif
                    </div>

                    @if($purchase->payment_status !== 'paid')
                    <div class="mt-3 pt-3 border-t">
                        <a href="{{ route('purchases.pay-debt', $purchase) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Bayar Hutang
                        </a>
                    </div>
                    @endif
                </div>
                @endif

                @if($completedReturns->isNotEmpty())
                <div class="flex justify-between text-sm border-t pt-2">
                    <span class="text-gray-600">Total Retur (tanpa PPN & FOB):</span>
                    <span class="font-semibold text-red-600">- Rp {{ number_format($totalReturnAmount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold">
                    <span class="text-gray-900">TOTAL BERSIH SESUDAH RETUR:</span>
                    <span class="text-green-700">Rp {{ number_format($netTotal, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($purchase->status == 'approved')
    <!-- Receive Form -->
    <div id="receive-form" class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Konfirmasi Penerimaan Barang</h3>
            <p class="mt-1 text-sm text-gray-500">Lengkapi data berikut sebelum menerima dan mengupdate stok.</p>
        </div>
        <div class="px-6 py-4">
            <form action="{{ route('purchases.receive', $purchase) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="receipt_document" class="block text-sm font-medium text-gray-700">
                        Bukti Penerimaan Barang <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="receipt_document" id="receipt_document" accept=".jpg,.jpeg,.png,.pdf"
                        class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('receipt_document')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Format yang diizinkan: JPG, JPEG, PNG, atau PDF. Maksimal 4 MB.</p>
                </div>

                <div>
                    <label for="invoice_number" class="block text-sm font-medium text-gray-700">
                        Nomor Faktur <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="invoice_number" id="invoice_number" value="{{ old('invoice_number') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Masukkan nomor faktur dari supplier" required>
                    @error('invoice_number')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="received_by_employee_id" class="block text-sm font-medium text-gray-700">
                        Karyawan Penerima <span class="text-red-500">*</span>
                    </label>
                    <select name="received_by_employee_id" id="received_by_employee_id"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-sm border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                        required>
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach(($employees ?? []) as $employee)
                        <option value="{{ $employee->id }}" @selected(old('received_by_employee_id')==$employee->id)>
                            {{ $employee->employee_number }} - {{ $employee->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('received_by_employee_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2 flex justify-end">
                    <button type="submit"
                        onclick="return confirm('Terima purchase order ini? Stok {{ $purchase->purchase_type === 'auxiliary_material' ? 'bahan penolong' : 'bahan baku' }} akan diupdate.')"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 shadow-md">
                        Terima &amp; Update Stok
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if(($purchase->returns?->count() ?? 0) > 0)
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Riwayat Retur Pembelian</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Retur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Retur</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($purchase->returns as $return)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $return->return_number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $return->return_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($return->status == 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($return->status == 'approved' || $return->status == 'completed') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                {{ ucfirst($return->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">Rp {{ number_format($return->total_return_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('purchase-returns.show', $return) }}" class="text-blue-600 hover:text-blue-900">Lihat</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Delete Button (only for draft) -->
    @if($purchase->status == 'draft')
    <div class="flex justify-end">
        <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus purchase order ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-red-700 shadow-md">
                Hapus Purchase Order
            </button>
        </form>
    </div>
    @endif
</div>
@endsection