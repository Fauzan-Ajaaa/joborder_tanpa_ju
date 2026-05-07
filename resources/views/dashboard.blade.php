<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12 bg-dashboard-theme">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Notifikasi POR belum dibuat --}}
            @if($porMissing)
            <div class="mb-6 flex items-center justify-between bg-orange-50 border border-orange-300 rounded-lg px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="text-orange-500 text-xl">⚠️</span>
                    <div>
                        <p class="text-sm font-semibold text-orange-800">Biaya Overhead Pabrik Bulan Ini Belum Dibuat</p>
                        <p class="text-xs text-orange-600 mt-0.5">Periode {{ $currentPeriod }} — Tarif BOP akan 0 sampai POR diinput.</p>
                    </div>
                </div>
                <a href="{{ route('overhead-por.index') }}" class="ml-4 shrink-0 inline-flex items-center px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold rounded-md transition">
                    Input POR Sekarang →
                </a>
            </div>
            @endif

            <!-- Statistics Cards -->
            <div class="space-y-4 mb-10">
                <!-- Row 1: Total Produk, Bahan Baku, Karyawan -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Produk</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['total_products'] }}</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Bahan Baku</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['total_raw_materials'] }}</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Karyawan</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['total_employees'] }}</div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Total Transaksi Pembelian Hari Ini, Total Transaksi Penjualan Hari Ini, Total Transaksi Sebulan -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Pembelian Hari Ini</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['today_purchase_transactions'] ?? 0 }}</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaksi Penjualan Hari Ini</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['today_sales_transactions'] ?? 0 }}</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transaksi Sebulan</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $stats['monthly_transactions'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Pembelian Hari Ini, Total Pembelian -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Pembelian Hari Ini</div>
                            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($stats['today_purchases'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pembelian</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($stats['total_purchases'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Row 4: Penjualan Hari Ini, Total Penjualan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Penjualan Hari Ini</div>
                            <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($stats['today_sales'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Penjualan</div>
                            <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($stats['total_sales'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Row 5: Total Hutang Hari Ini, Total Hutang Akumulatif -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Hutang Hari Ini</div>
                            <div class="mt-2 text-2xl font-semibold text-orange-600 dark:text-orange-400">Rp {{ number_format($stats['total_debt_today'] ?? 0, 0, ',', '.') }}</div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Pembelian kredit hari ini
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg h-full">
                        <div class="p-5 flex flex-col justify-between h-full text-center">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Hutang Akumulatif</div>
                            <div class="mt-2 text-3xl font-semibold text-red-600 dark:text-red-400">Rp {{ number_format($stats['total_debt_accumulated'] ?? 0, 0, ',', '.') }}</div>
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Sisa hutang belum lunas
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 6: Peringatan Stok Rendah Bahan Baku -->
                @if($stats['low_stock_materials']->count() > 0)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 1.884-1.36 1.884 0 1.119.602 1.884 1.36 0 .765-.602 1.884-1.36 1.884-1.36zm0 8.802c0 1.119-.602 1.884-1.36 1.884-1.36-.765-.602-1.884-1.36-1.884-1.36zm0 8.802c0 1.119-.602 1.884-1.36 1.884-1.36-.765-.602-1.884-1.36-1.884-1.36z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Peringatan: {{ $stats['low_stock_materials']->count() }} Bahan Baku Stok Rendah
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    Segera lakukan pembelian/pemesanan bahan baku berikut untuk menghindari gangguan produksi:
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <svg class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 1.884-1.36 1.884 0 1.119.602 1.884 1.36 0 .765-.602 1.884-1.36 1.884-1.36zm0 8.802c0 1.119-.602 1.884-1.36 1.884-1.36-.765-.602-1.884-1.36-1.884-1.36zm0 8.802c0 1.119-.602 1.884-1.36 1.884-1.36-.765-.602-1.884-1.36-1.884-1.36z" clip-rule="evenodd" />
                                </svg>
                                Daftar Bahan Baku Stok Kurang
                            </h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Bahan</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Saat Ini</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Minimal</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kekurangan</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($stats['low_stock_materials'] as $material)
                                            <tr class="hover:bg-red-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $material->code }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <div class="font-medium">{{ $material->name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $material->description }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                    <div class="space-y-1">
                                                        <div>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                {{ number_format($material->stock, 2, ',', '.') == number_format($material->stock, 0, ',', '.') . ',00' ? number_format($material->stock, 0, ',', '.') : number_format($material->stock, 2, ',', '.') }} {{ $material->unit }}
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            Saat ini
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                    <div class="space-y-1">
                                                        <div>
                                                            {{ number_format($material->effective_min_stock, 2, ',', '.') == number_format($material->effective_min_stock, 0, ',', '.') . ',00' ? number_format($material->effective_min_stock, 0, ',', '.') : number_format($material->effective_min_stock, 2, ',', '.') }} {{ $material->unit }}
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            @if($material->min_stock > 0)
                                                                Manual: {{ number_format($material->min_stock, 2, ',', '.') == number_format($material->min_stock, 0, ',', '.') . ',00' ? number_format($material->min_stock, 0, ',', '.') : number_format($material->min_stock, 2, ',', '.') }} {{ $material->unit }}
                                                            @else
                                                                Otomatis dari produk
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-600 text-white">
                                                        {{ number_format($material->effective_min_stock - $material->stock, 2, ',', '.') == number_format($material->effective_min_stock - $material->stock, 0, ',', '.') . ',00' ? number_format($material->effective_min_stock - $material->stock, 0, ',', '.') : number_format($material->effective_min_stock - $material->stock, 2, ',', '.') }} {{ $material->unit }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $material->supplier?->name ?? 'Tidak ada supplier' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($stats['low_stock_materials']->count() >= 10)
                                <div class="mt-4 text-sm text-gray-500 text-center">
                                    Menampilkan 10 bahan baku dengan stok terendah. 
                                    <a href="{{ route('raw-materials.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                        Lihat semua bahan baku →
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Semua Bahan Baku Stok Aman
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    Tidak ada bahan baku yang stoknya kurang dari batas minimal.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Row 7: Peringatan Stok Rendah Bahan Penolong -->
                @if($stats['low_stock_auxiliary_materials']->count() > 0)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 1.884-1.36 1.884 0 1.119.602 1.884 1.36 0 .765-.602 1.884-1.36 1.884-1.36zm0 8.802c0 1.119-.602 1.884-1.36 1.884-1.36-.765-.602-1.884-1.36-1.884-1.36zm0 8.802c0 1.119-.602 1.884-1.36 1.884-1.36-.765-.602-1.884-1.36-1.884-1.36z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Peringatan: {{ $stats['low_stock_auxiliary_materials']->count() }} Bahan Penolong Stok Rendah
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    Segera lakukan pembelian/pemesanan bahan penolong berikut untuk menghindari gangguan produksi:
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                <svg class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 1.884-1.36 1.884 0 1.119.602 1.884 1.36 0 .765-.602 1.884-1.36 1.884-1.36zm0 8.802c0 1.119-.602 1.884-1.36 1.884-1.36-.765-.602-1.884-1.36-1.884-1.36zm0 8.802c0 1.119-.602 1.884-1.36 1.884-1.36-.765-.602-1.884-1.36-1.884-1.36z" clip-rule="evenodd" />
                                </svg>
                                Daftar Bahan Penolong Stok Kurang
                            </h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Bahan</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Saat Ini</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Minimal</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kekurangan</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($stats['low_stock_auxiliary_materials'] as $material)
                                            @php
                                                $shortage = max(0, (float) $material->minimum_stock - (float) $material->stock);
                                            @endphp
                                            <tr class="hover:bg-red-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $material->code }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <div class="font-medium">{{ $material->name }}</div>
                                                    <div class="text-xs text-gray-500">{{ $material->description }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                    <div class="space-y-1">
                                                        <div>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                {{ number_format($material->stock, 2, ',', '.') == number_format($material->stock, 0, ',', '.') . ',00' ? number_format($material->stock, 0, ',', '.') : number_format($material->stock, 2, ',', '.') }} {{ $material->unit }}
                                                            </span>
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            Saat ini
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                    <div class="space-y-1">
                                                        <div>
                                                            {{ number_format($material->minimum_stock, 2, ',', '.') == number_format($material->minimum_stock, 0, ',', '.') . ',00' ? number_format($material->minimum_stock, 0, ',', '.') : number_format($material->minimum_stock, 2, ',', '.') }} {{ $material->unit }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-600 text-white">
                                                        {{ number_format($shortage, 2, ',', '.') == number_format($shortage, 0, ',', '.') . ',00' ? number_format($shortage, 0, ',', '.') : number_format($shortage, 2, ',', '.') }} {{ $material->unit }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $material->supplier?->name ?? 'Tidak ada supplier' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($stats['low_stock_auxiliary_materials']->count() >= 10)
                                <div class="mt-4 text-sm text-gray-500 text-center">
                                    Menampilkan 10 bahan penolong dengan stok terendah. 
                                    <a href="{{ route('auxiliary-materials.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                        Lihat semua bahan penolong →
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Semua Bahan Penolong Stok Aman
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    Tidak ada bahan penolong yang stoknya kurang dari batas minimal.
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Combined Chart with Filters -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6 mb-10">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 space-y-4 sm:space-y-0">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Grafik Penjualan & Pembelian</h3>
                        
                        <!-- Filter Controls -->
                        <div class="flex flex-wrap gap-4 items-center">
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600 min-w-[40px]">Filter:</label>
                                <select id="filterType" name="filter_type" class="px-3 py-2 pr-8 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none bg-white">
                                    <option value="month" {{ $filterType == 'month' ? 'selected' : '' }}>Per Bulan</option>
                                    <option value="year" {{ $filterType == 'year' ? 'selected' : '' }}>Per Tahun</option>
                                </select>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600 min-w-[40px]">Tahun:</label>
                                <select id="yearFilter" name="year" class="px-3 py-2 pr-8 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none bg-white">
                                    @foreach($years as $y)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="flex items-center gap-2" id="monthFilterContainer">
                                <label class="text-sm text-gray-600 min-w-[40px]">Bulan:</label>
                                <select id="monthFilter" name="month" class="px-3 py-2 pr-8 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none bg-white">
                                    @foreach($months as $m => $monthName)
                                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $monthName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <button onclick="applyFilters()" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Terapkan
                            </button>
                            
                            <div class="flex items-center gap-2">
                                @if($filterType == 'month')
                                    @if(!$showAll)
                                        <button onclick="showAllData()" class="px-3 py-2 bg-gray-600 text-white rounded-md text-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                            Tampilkan Semua Hari
                                        </button>
                                                                            @else
                                        <button onclick="showLimitedData()" class="px-3 py-2 bg-orange-600 text-white rounded-md text-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
                                            Tampilkan 15 Hari
                                        </button>
                                        <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded whitespace-nowrap">
                                            {{ count($charts['labels']) }} hari
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative" style="height: 200px;">
                        <canvas id="combinedChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Charts: Pembelian & Penjualan (Hidden by default) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6 mb-10 space-y-4 lg:space-y-0" style="display: none;">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Grafik Pembelian per Bulan</h3>
                        <canvas id="purchaseChart" height="200"></canvas>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Grafik Penjualan per Bulan</h3>
                        <canvas id="salesChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Pembelian Terbaru -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-4 space-y-4 lg:space-y-0">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Pembelian Terbaru</h3>
                        <div class="space-y-3">
                            @forelse($recent_transactions as $transaction)
                                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">#{{ $transaction->purchase_number }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $transaction->created_at?->format('Y-m-d H:i:s') }}</div>
                                        <!-- Due date hidden but available for future use -->
                                        @if($transaction->payment_method === 'credit' && $transaction->due_date)
                                            <div class="hidden" data-due-date="{{ $transaction->due_date->format('Y-m-d') }}"></div>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">Rp {{ number_format($transaction->total_amount ?? 0, 0, ',', '.') }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($transaction->payment_method === 'cash') bg-green-100 text-green-800
                                                @else bg-orange-100 text-orange-800
                                                @endif">
                                                {{ $transaction->payment_method === 'cash' ? 'Tunai' : 'Hutang' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Belum ada pembelian</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Penjualan Terbaru -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Penjualan Terbaru</h3>
                        <div class="space-y-3">
                            @forelse($recent_sales as $sale)
                                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">#{{ $sale->transaction_number }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $sale->created_at?->format('Y-m-d H:i:s') }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">Rp {{ number_format($sale->total_amount ?? 0, 0, ',', '.') }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ $sale->status === 'completed' ? 'Completed' : ($sale->status === 'pending' ? 'Sold' : ucfirst($sale->status)) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Belum ada penjualan</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaksi Terbaru -->
            <div class="mt-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Semua Transaksi Terbaru</h3>
                        <div class="space-y-3">
                            {{-- Gabungkan semua transaksi --}}
                            @php
                                $allTransactions = collect();
                                
                                // Tambahkan pembelian
                                if(isset($recent_transactions)) {
                                    foreach($recent_transactions as $trans) {
                                        $trans->type = 'purchase';
                                        $trans->number = $trans->purchase_number ?? 'PO-' . $trans->id;
                                        $trans->date = $trans->created_at;
                                        $trans->amount = $trans->total_amount;
                                        $allTransactions->push($trans);
                                    }
                                }
                                
                                // Tambahkan penjualan
                                if(isset($recent_sales)) {
                                    foreach($recent_sales as $sale) {
                                        $sale->type = 'sales';
                                        $sale->number = $sale->transaction_number ?? 'SA-' . $sale->id;
                                        $sale->date = $sale->created_at;
                                        $sale->amount = $sale->total_amount;
                                        $allTransactions->push($sale);
                                    }
                                }
                                
                                // Urutkan berdasarkan tanggal terbaru
                                $allTransactions = $allTransactions->sortByDesc('date')->take(10);
                            @endphp
                            
                            @forelse($allTransactions as $transaction)
                                <div class="flex justify-between items-center border-b border-gray-200 dark:border-gray-700 pb-2">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">#{{ $transaction->number }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $transaction->date?->format('Y-m-d H:i:s') ?? $transaction->date }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($transaction->amount ?? 0, 0, ',', '.') }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            @switch($transaction->type)
                                                @case('purchase')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        Pembelian
                                                    </span>
                                                    @break
                                                @case('sales')
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        Penjualan
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        Lainnya
                                                    </span>
                                            @endswitch
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 dark:text-gray-400 text-center py-4">Belum ada transaksi</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize month filter visibility based on current filter type
            const filterType = document.getElementById('filterType').value;
            const monthContainer = document.getElementById('monthFilterContainer');
            
            if (filterType === 'year') {
                monthContainer.style.display = 'none';
            } else {
                monthContainer.style.display = 'flex';
            }

            // Chart initialization
            const chartData = @json($charts);
            
            console.log('Chart data:', chartData);
            
            if (!chartData.labels || chartData.labels.length === 0) {
                console.error('No chart data available');
                return;
            }

            const currencyFormatter = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0,
            });

            // Custom formatter for juta (jt)
            const jutaFormatter = new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 2,
            });

            function formatToJuta(value) {
                if (value >= 1000000) {
                    return jutaFormatter.format(value / 1000000).replace('Rp', 'Rp ') + ' jt';
                }
                return currencyFormatter.format(value);
            }

            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y || 0;
                                return context.dataset.label + ': ' + formatToJuta(value);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + ' jt';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'k';
                                }
                                return formatToJuta(value).replace('Rp ', '');
                            },
                            maxTicksLimit: 5
                        }
                    },
                    x: {
                        ticks: {
                            maxTicksLimit: 8,
                            autoSkip: true
                        }
                    }
                }
            };

            // Combined Chart
            const canvas = document.getElementById('combinedChart');
            if (canvas) {
                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [
                            {
                                label: 'Penjualan',
                                data: chartData.sales,
                                borderColor: 'rgba(22, 163, 74, 1)',
                                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: false,
                            },
                            {
                                label: 'Pembelian',
                                data: chartData.purchases,
                                borderColor: 'rgba(37, 99, 235, 1)',
                                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: false,
                            }
                        ],
                    },
                    options: commonOptions,
                });
                console.log('Chart created successfully');
            } else {
                console.error('Canvas not found');
            }
        });

        // Filter functionality
        function applyFilters() {
            const filterType = document.getElementById('filterType').value;
            const year = document.getElementById('yearFilter').value;
            const month = document.getElementById('monthFilter').value;
            
            const url = new URL(window.location);
            url.searchParams.set('filter_type', filterType);
            url.searchParams.set('year', year);
            url.searchParams.set('month', month);
            url.searchParams.delete('show_all');
            
            window.location.href = url.toString();
        }

        // Show all data functionality
        function showAllData() {
            const filterType = document.getElementById('filterType').value;
            const year = document.getElementById('yearFilter').value;
            const month = document.getElementById('monthFilter').value;
            
            const url = new URL(window.location);
            url.searchParams.set('filter_type', filterType);
            url.searchParams.set('year', year);
            url.searchParams.set('month', month);
            url.searchParams.set('show_all', '1');
            
            window.location.href = url.toString();
        }

        // Show limited data functionality
        function showLimitedData() {
            const filterType = document.getElementById('filterType').value;
            const year = document.getElementById('yearFilter').value;
            const month = document.getElementById('monthFilter').value;
            
            const url = new URL(window.location);
            url.searchParams.set('filter_type', filterType);
            url.searchParams.set('year', year);
            url.searchParams.set('month', month);
            url.searchParams.delete('show_all');
            
            window.location.href = url.toString();
        }

        // Show/hide month filter based on filter type
        document.getElementById('filterType').addEventListener('change', function() {
            const monthContainer = document.getElementById('monthFilterContainer');
            
            if (this.value === 'year') {
                monthContainer.style.display = 'none';
            } else {
                monthContainer.style.display = 'flex';
            }
        });
    </script>
</x-app-layout>
