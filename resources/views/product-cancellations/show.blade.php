@extends('layouts.app')

@section('title', 'Detail Pengurangan Produk')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $productCancellation->cancellation_number }}</h1>
            <p class="text-sm text-gray-500">Detail pengurangan produk</p>
        </div>
        <div class="space-x-2">
            <a href="{{ route('product-cancellations.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                Kembali
            </a>
        </div>
    </div>

    <!-- Status Badge -->
    <div class="bg-white shadow sm:rounded-lg p-6">
        <div class="flex items-center space-x-4">
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Selesai
                </span>
            </div>
            <div class="text-sm text-gray-600">
                Dibatalkan pada {{ $productCancellation->cancelled_at->format('d/m/Y H:i') }}
                oleh {{ $productCancellation->cancelledBy?->name ?? '-' }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Informasi Pembatalan -->
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Informasi Pengurangan</h2>
            <div class="space-y-3">
                <div>
                    <span class="text-sm font-medium text-gray-500">Job Order:</span>
                    <div class="mt-1">
                        <a href="{{ route('job-orders.show', $productCancellation->jobOrder) }}" class="text-blue-600 hover:text-blue-800">
                            {{ $productCancellation->jobOrder->kode_job }}
                        </a>
                    </div>
                </div>
                
                <div>
                    <span class="text-sm font-medium text-gray-500">Produk:</span>
                    <div class="mt-1 text-sm text-gray-900">{{ $productCancellation->product->name }}</div>
                </div>
                
                <div>
                    <span class="text-sm font-medium text-gray-500">Quantity Dibatalkan:</span>
                    <div class="mt-1 text-sm text-gray-900">{{ (int)$productCancellation->quantity_cancelled }}</div>
                </div>
                
                <div>
                    <span class="text-sm font-medium text-gray-500">Tanggal Pembatalan:</span>
                    <div class="mt-1 text-sm text-gray-900">{{ $productCancellation->cancelled_at->format('d/m/Y H:i') }}</div>
                </div>
                
                <div>
                    <span class="text-sm font-medium text-gray-500">Dibatalkan oleh:</span>
                    <div class="mt-1 text-sm text-gray-900">{{ $productCancellation->cancelledBy?->name ?? '-' }}</div>
                </div>
                
                <div>
                    <span class="text-sm font-medium text-gray-500">Alasan:</span>
                    <div class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-md">{{ $productCancellation->reason }}</div>
                </div>
                
                @if($productCancellation->approval_notes)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Catatan Persetujuan:</span>
                        <div class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-md">{{ $productCancellation->approval_notes }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Rincian Biaya -->
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Rincian Biaya Pengurangan</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">BBB (Bahan Baku):</span>
                    <span class="text-sm font-medium text-gray-900">Rp {{ number_format($productCancellation->bbb_cost, 0, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">BTKL (Tenaga Kerja Langsung):</span>
                    <span class="text-sm font-medium text-gray-900">Rp {{ number_format($productCancellation->btkl_cost, 0, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">BOP (Overhead Pabrik):</span>
                    <span class="text-sm font-medium text-gray-900">Rp {{ number_format($productCancellation->bop_cost, 0, ',', '.') }}</span>
                </div>
                
                <div class="border-t pt-3">
                    <div class="flex justify-between">
                        <span class="text-base font-semibold text-gray-900">Total Biaya:</span>
                        <span class="text-base font-semibold text-red-600">Rp {{ number_format($productCancellation->total_cost, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Jurnal Entries (jika sudah disetujui) -->
    @if($productCancellation->isCompleted() && $productCancellation->journalEntries->count() > 0)
    @endif
</div>

@endsection