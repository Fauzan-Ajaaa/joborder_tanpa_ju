@extends('layouts.app')

@section('title', 'Detail Faktur Penjualan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Detail Faktur Penjualan</h1>
        <div class="space-x-2">
            <a href="{{ route('invoices.print', $sales->id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700" target="_blank">
                Cetak Faktur
            </a>
            <a href="{{ route('invoices.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Kembali
            </a>
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <!-- Invoice Header -->
            <div class="border-b pb-6 mb-6">
                <div class="grid grid-cols-2 gap-8">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">FAKTUR PENJUALAN</h2>
                        <p class="text-lg font-semibold text-gray-700">
                            No. INV-{{ date('Ymd', strtotime($sales->transaction_date)) }}-{{ str_pad($sales->id, 4, '0', STR_PAD_LEFT) }}
                        </p>
                        <p class="text-sm text-gray-600">
                            Tanggal: {{ date('d F Y', strtotime($sales->transaction_date)) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600">
                            <p class="font-semibold">{{ $company['name'] }}</p>
                            <p>{{ $company['address'] }}</p>
                            <p>Telp: {{ $company['phone'] }}</p>
                            <p>Email: {{ $company['email'] }}</p>
                            <p>NPWP: {{ $company['tax_id'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="grid grid-cols-2 gap-8 mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Informasi Pelanggan</h3>
                    <div class="text-sm text-gray-700">
                        <p><strong>Nama:</strong> {{ $sales->jobOrder->customer->name ?? $sales->customer_name ?? '-' }}</p>
                        <p><strong>Alamat:</strong> {{ $sales->jobOrder->customer->address ?? $sales->customer_address ?? '-' }}</p>
                        <p><strong>No. Telepon:</strong> {{ $sales->jobOrder->customer->phone ?? '-' }}</p>
                        @if($sales->jobOrder && $sales->jobOrder->customer)
                            <p><strong>Kode Pelanggan:</strong> {{ $sales->jobOrder->customer->code }}</p>
                        @endif
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Informasi Penjualan</h3>
                    <div class="text-sm text-gray-700">
                        <p><strong>No. Transaksi:</strong> {{ $sales->transaction_number }}</p>
                        <p><strong>Sales:</strong> {{ $sales->jobOrder->labors->first()->employee->name ?? $sales->employee ?? '-' }}</p>
                        <p><strong>Metode Pembayaran:</strong> 
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $sales->payment_status == 'cash' ? 'bg-green-100 text-green-800' : 
                                   ($sales->payment_status == 'transfer' ? 'bg-blue-100 text-blue-800' : 
                                   'bg-purple-100 text-purple-800') }}">
                                {{ $sales->payment_status == 'cash' ? 'Tunai' : 
                                   ($sales->payment_status == 'transfer' ? 'Transfer' : 'E-Wallet') }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Detail Barang</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Produk</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Satuan</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($sales->salesItems as $index => $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->product->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-6 py-3 text-right text-sm font-medium text-gray-900">Subtotal:</td>
                                <td class="px-6 py-3 text-right text-sm font-medium text-gray-900">Rp {{ number_format($sales->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @if($sales->fob_cost > 0)
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right text-sm text-gray-600">FOB Cost ({{ $sales->fob_type }}):</td>
                                    <td class="px-6 py-3 text-right text-sm text-gray-600">Rp {{ number_format($sales->fob_cost, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            @if($sales->ppn_amount > 0)
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right text-sm text-gray-600">PPN ({{ $sales->ppn_rate }}%):</td>
                                    <td class="px-6 py-3 text-right text-sm text-gray-600">Rp {{ number_format($sales->ppn_amount, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="4" class="px-6 py-3 text-right text-lg font-bold text-gray-900">TOTAL:</td>
                                <td class="px-6 py-3 text-right text-lg font-bold text-gray-900">Rp {{ number_format($sales->grand_total, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            @if($sales->notes)
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Catatan</h3>
                    <p class="text-sm text-gray-700">{{ $sales->notes }}</p>
                </div>
            @endif

            <!-- Footer -->
            <div class="border-t pt-6">
                <div class="grid grid-cols-3 gap-8">
                    <div>
                        <p class="text-sm text-gray-600 mb-8">Penerima Barang,</p>
                        <p class="text-sm text-gray-600 mt-8">(_________________________)</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-8">Mengetahui,</p>
                        <p class="text-sm text-gray-600 mt-8">(_________________________)</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600 mb-8">{{ $company['name'] }},</p>
                        <p class="text-sm text-gray-600 mt-8">(_________________________)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
