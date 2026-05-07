@extends('layouts.app')

@section('title', 'Detail Penjualan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Detail Penjualan</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Kembali</a>
            @if($sale->salesReturns->isEmpty())
            <a href="{{ route('sales.returns.create', $sale) }}" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700">Retur Penjualan</a>
            @endif
        </div>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Informasi Penjualan</h3>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nomor Penjualan</dt>
                    <dd class="mt-1 text-sm font-bold text-blue-600">{{ $sale->transaction_number }}</dd>
                </div>
                @if($sale->job_order_id)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Job Order Terkait</dt>
                    <dd class="mt-1">
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-purple-900">{{ $sale->jobOrder->kode_job ?? 'Job Order #' . $sale->job_order_id }}</p>
                                </div>
                            </div>
                        </div>
                    </dd>
                </div>
                @endif
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tanggal Penjualan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ optional($sale->transaction_date)->format('d F Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Nama Pelanggan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $sale->customer_name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Karyawan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $sale->employee ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Opsi Pengiriman</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @php($ft = $sale->fob_type ?? 'shipping_point')
                        @if($ft === 'destination')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Diantar (Biaya Pengiriman dibayar pembeli)</span>
                        @elseif($ft === 'dine_in')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Dine In</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Take Away</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Metode Pembayaran</dt>
                    <dd class="mt-1">
                        @if($sale->payment_status === 'cash')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Tunai</span>
                        @elseif($sale->payment_status === 'transfer')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Transfer</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">E-Wallet</span>
                        @endif
                    </dd>
                </div>
                
                <!-- Status Approval -->
                @if($sale->requiresPaymentProof())
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status Approval</dt>
                    <dd class="mt-1">
                        @if($sale->isApproved())
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Disetujui
                            </span>
                            @if($sale->approved_at)
                                <p class="text-xs text-gray-500 mt-1">{{ $sale->approved_at->format('d F Y H:i') }}</p>
                            @endif
                        @elseif($sale->isRejected())
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                                Ditolak
                            </span>
                            @if($sale->approval_notes)
                                <p class="text-xs text-red-600 mt-1">{{ $sale->approval_notes }}</p>
                            @endif
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Menunggu Persetujuan
                            </span>
                        @endif
                    </dd>
                </div>
                @endif
                @if($sale->salesReturns && $sale->salesReturns->count() > 0)
                <div class="md:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Riwayat Retur</dt>
                    <dd class="mt-1">
                        <div class="space-y-2">
                            @foreach($sale->salesReturns as $return)
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium text-amber-900">RET-{{ str_pad($return->id, 4, '0', STR_PAD_LEFT) }}</p>
                                        <p class="text-sm text-gray-600">{{ optional($return->return_date)->format('d F Y') }} - {{ $return->reason ?? '-' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-red-600">Rp {{ number_format($return->grand_total, 0, ',', '.') }}</p>
                                        <a href="{{ route('sales.returns.show', $return) }}" class="text-xs text-blue-600 hover:text-blue-800">Detail</a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Section Upload Bukti Pembayaran (Opsional) -->
    @if($sale->requiresPaymentProof())
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Bukti Pembayaran</h3>
        </div>
        <div class="px-6 py-4">
            @if($sale->payment_proof)
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium text-green-800">Bukti pembayaran sudah diupload</span>
                        </div>
                        <a href="{{ route('sales.payment-proof', $sale) }}" target="_blank" 
                           class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Lihat Bukti
                        </a>
                    </div>
                </div>
            @else
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada bukti pembayaran</h3>
                        <p class="mt-1 text-sm text-gray-500">Upload bukti pembayaran jika diperlukan (opsional)</p>
                        
                        <div class="mt-4">
                            <form action="{{ route('sales.upload-payment-proof', $sale) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <div>
                                    <input type="file" name="payment_proof" accept="image/*,application/pdf"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md">
                                    <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, PDF. Maksimal 2MB.</p>
                                </div>
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    Upload Bukti
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Section Approval Pembayaran (Wajib) -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Status Persetujuan Pembayaran</h3>
        </div>
        <div class="px-6 py-4">
            @if($sale->isApproved())
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <h4 class="text-lg font-medium text-green-800">Pembayaran Disetujui</h4>
                            @if($sale->approved_at)
                                <p class="text-sm text-green-600">{{ $sale->approved_at->format('d F Y H:i') }}</p>
                            @endif
                            @if($sale->approval_notes)
                                <p class="text-sm text-green-700 mt-1">{{ $sale->approval_notes }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif($sale->isRejected())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <h4 class="text-lg font-medium text-red-800">Pembayaran Ditolak</h4>
                            @if($sale->approved_at)
                                <p class="text-sm text-red-600">{{ $sale->approved_at->format('d F Y H:i') }}</p>
                            @endif
                            @if($sale->approval_notes)
                                <p class="text-sm text-red-700 mt-1">{{ $sale->approval_notes }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-yellow-600 mr-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <div>
                                <h4 class="text-lg font-medium text-yellow-800">Menunggu Persetujuan</h4>
                                <p class="text-sm text-yellow-600">Pembayaran perlu disetujui atau ditolak oleh admin</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel Approval Admin -->
                    <div class="mt-4 pt-4 border-t border-yellow-200">
                        <h5 class="text-sm font-medium text-yellow-800 mb-3">Panel Admin</h5>
                        <div class="flex space-x-3">
                            <!-- Tombol Setujui -->
                            <form action="{{ route('sales.approve-payment', $sale) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                                        onclick="return confirm('Yakin ingin menyetujui pembayaran ini?')">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Setujui
                                </button>
                            </form>
                            
                            <!-- Tombol Tolak -->
                            <button type="button" 
                                    onclick="showRejectModal()"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Tolak
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Modal untuk Reject dengan Alasan -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Tolak Pembayaran</h3>
                <form action="{{ route('sales.reject-payment', $sale) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Penolakan</label>
                        <textarea name="rejection_reason" rows="3" required
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm"
                                  placeholder="Masukkan alasan penolakan pembayaran..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideRejectModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                            Tolak Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
        }
        
        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
    </script>

    @if($sale->salesItems && $sale->salesItems->count() > 0)
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Item Penjualan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($sale->salesItems as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($item->product_id && $item->product)
                                {{ $item->product->name }}
                            @elseif($item->product_id)
                                <span class="text-red-600">Produk #{{ $item->product_id }} (tidak ditemukan)</span>
                            @else
                                <span class="text-red-600">Produk tidak ditentukan</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Ringkasan Biaya -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Ringkasan Biaya</h3>
        </div>
        <div class="px-6 py-4">
            <div class="max-w-md ml-auto space-y-2">

                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-medium">Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">Diskon:</span>
                    <span class="font-medium text-red-600">
                        @if($sale->discount_amount > 0)
                            Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </span>
                </div>
                
                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">Subtotal Setelah Diskon:</span>
                    <span class="font-medium">Rp {{ number_format(($sale->subtotal ?? 0) - ($sale->discount_amount ?? 0), 0, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">Biaya Pengiriman:</span>
                    <span class="font-medium">
                        @if($sale->fob_cost > 0)
                            Rp {{ number_format((float)$sale->fob_cost, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </span>
                </div>
                
                <div class="flex justify-between text-sm py-2 border-b border-gray-200">
                    <span class="text-gray-600">PPN:</span>
                    <span class="font-medium">
                        @if($sale->ppn_amount > 0)
                            Rp {{ number_format($sale->ppn_amount, 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </span>
                </div>
                
                <div class="flex justify-between text-lg font-bold pt-3 border-t-2 border-gray-300">
                    <span class="text-gray-900">TOTAL:</span>
                    <span class="text-blue-600">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
