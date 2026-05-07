<nav x-data="{ open: false }" class="github-nav nav-with-batik relative">
    <!-- Navigation Content Container -->
    <div class="relative nav-content-wrapper">
        <!-- Primary Navigation Menu -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center flex-1">
                    <!-- Logo -->
                    <div class="shrink-0 flex items-center">
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                            <x-application-logo class="block h-8 w-auto fill-current text-gray-900" />
                            <span
                                class="text-lg font-semibold text-gray-900">{{ auth()->user()?->nama_perusahaan ?? config('app.name', 'Manufaktur') }}</span>
                        </a>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden sm:flex items-center space-x-1 mx-auto">
                        <!-- Dashboard -->
                        <a href="{{ route('dashboard') }}"
                            class="github-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            {{ __('Dashboard') }}
                        </a>

                        <!-- E-Katalog -->
                        <a href="{{ route('catalog.index') }}" target="_blank" class="github-nav-link">
                            E-Katalog
                        </a>

                        <!-- Master Data Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.away="open = false" class="github-nav-link {{ request()->routeIs('products.*') || request()->routeIs('raw-materials.*') || request()->routeIs('auxiliary-materials.*') || request()->routeIs('suppliers.*') || request()->routeIs('employees.*') || request()->routeIs('customers.*') || request()->routeIs('chart-of-accounts.*') || request()->routeIs('biaya-overhead.*') || request()->routeIs('units.*') || request()->routeIs('aset.*')
    ? 'active' : '' }}">
                                <span>Master Data</span>
                                <svg class="ms-1 h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.085l3.71-3.854a.75.75 0 111.08 1.04l-4.25 4.417a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="github-dropdown">
                                <div class="py-1">
                                    <a href="{{ route('chart-of-accounts.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('chart-of-accounts.index') ? 'active' : '' }}">Chart
                                        Of Account</a>
                                    <a href="{{ route('products.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('products.*') ? 'active' : '' }}">Produk</a>
                                    <a href="{{ route('raw-materials.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('raw-materials.*') ? 'active' : '' }}">Bahan
                                        Baku</a>
                                    <a href="{{ route('auxiliary-materials.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('auxiliary-materials.*') ? 'active' : '' }}">Bahan
                                        Penolong</a>
                                    <a href="{{ route('units.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('units.*') ? 'active' : '' }}">Satuan</a>
                                    <a href="{{ route('suppliers.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">Supplier</a>
                                    <a href="{{ route('employees.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('employees.*') ? 'active' : '' }}">Karyawan</a>
                                    <a href="{{ route('aset.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('aset.*') ? 'active' : '' }}">Aset</a>
                                    {{-- <a href="{{ route('customers.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">Pelanggan</a>
                                    --}}
                                    <a href="{{ route('overhead-por.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('overhead-por.*') ? 'active' : '' }}">Overhead
                                        Perbulan</a>
                                </div>
                            </div>
                        </div>

                        <!-- Transaksi Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.away="open = false" class="github-nav-link {{ request()->routeIs('job-orders.*') || request()->routeIs('purchases.*') || request()->routeIs('transactions.*') || request()->routeIs('overhead-por.*') || request()->routeIs('sales.*') || request()->routeIs('sales-returns.*') || request()->routeIs('sales_reports.*') || request()->routeIs('penggajian.*')
    ? 'active' : '' }}">
                                <span>Transaksi</span>
                                <svg class="ms-1 h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.085l3.71-3.854a.75.75 0 111.08 1.04l-4.25 4.417a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="github-dropdown">
                                <div class="py-1">
                                    <a href="{{ route('job-orders.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('job-orders.*') ? 'active' : '' }}">Pemesanan</a>
                                    <a href="{{ route('bill-of-materials.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('bill-of-materials.*') ? 'active' : '' }}">Bill
                                        of Materials</a>
                                    <a href="{{ route('purchases.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}">Pembelian</a>
                                    <a href="{{ route('transactions.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">Transaksi
                                        Umum</a>
                                    <a href="{{ route('biaya-overhead.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('biaya-overhead.*') ? 'active' : '' }}">Beban
                                        dan Pengeluaran Lainnya</a>
                                    <a href="{{ route('hutang.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('hutang.*') ? 'active' : '' }}">Hutang</a>
                                    <a href="{{ route('sales.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('sales.*') ? 'active' : '' }}">Penjualan</a>
                                    <a href="{{ route('sales-returns.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('sales-returns.*') ? 'active' : '' }}">Retur
                                        Penjualan</a>
                                    <a href="{{ route('penggajian.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('penggajian.*') ? 'active' : '' }}">Penggajian</a>
                                    <a href="{{ route('stock-posting.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('stock-posting.*') ? 'active' : '' }}">Posting
                                        Stok</a>
                                </div>
                            </div>
                        </div>

                        <!-- Laporan Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" @click.away="open = false"
                                class="github-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                                <span>Laporan</span>
                                <svg class="ms-1 h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.085l3.71-3.854a.75.75 0 111.08 1.04l-4.25 4.417a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="github-dropdown">
                                <div class="py-1">
                                    <a href="{{ route('purchase_reports.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('purchase_reports.*') ? 'active' : '' }}">
                                        Laporan Pembelian
                                    </a>
                                    <a href="{{ route('sales_reports.index') }}"
                                        class="github-dropdown-item {{ request()->routeIs('sales_reports.*') ? 'active' : '' }}">
                                        Laporan Penjualan
                                    </a>
                                    <a href="{{ route('reports.neraca') }}"
                                        class="github-dropdown-item {{ request()->routeIs('reports.neraca') ? 'active' : '' }}">
                                        Laporan Posisi Keuangan
                                    </a>
                                    <a href="{{ route('reports.laba-rugi') }}"
                                        class="github-dropdown-item {{ request()->routeIs('reports.laba-rugi') ? 'active' : '' }}">
                                        Laba Rugi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Dropdown -->
                <div class="hidden sm:flex sm:items-center sm:ms-6">
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false"
                            class="github-btn flex items-center space-x-2">
                            <div
                                class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center text-xs font-medium text-gray-700">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="fill-current h-3 w-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="github-dropdown right-0">
                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}" class="github-dropdown-item">
                                    {{ __('Profile') }}
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="github-dropdown-item w-full text-left">
                                        {{ __('Log Out') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hamburger -->
                <div class="-me-2 flex items-center sm:hidden">
                    <button @click="open = ! open" class="github-btn">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Responsive Navigation Menu -->
        <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-gray-300">
            <div class="pt-2 pb-3 space-y-1 bg-white">
                <a href="{{ route('dashboard') }}"
                    class="block px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-gray-100 text-gray-900' : '' }}">
                    {{ __('Dashboard') }}
                </a>

                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Master Data</div>
                <a href="{{ route('chart-of-accounts.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('chart-of-accounts.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Chart
                    Of Account</a>
                <a href="{{ route('products.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('products.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Produk</a>
                <a href="{{ route('raw-materials.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('raw-materials.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Bahan
                    Baku</a>
                <a href="{{ route('units.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('units.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Satuan</a>
                <a href="{{ route('suppliers.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('suppliers.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Supplier</a>
                <a href="{{ route('employees.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('employees.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Karyawan</a>
                <a href="{{ route('aset.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('aset.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Aset</a>
                {{-- <a href="{{ route('customers.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('customers.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Pelanggan</a>
                --}}
                <a href="{{ route('biaya-overhead.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('biaya-overhead.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Beban
                    Overhead</a>

                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-4">Transaksi</div>
                <a href="{{ route('job-orders.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('job-orders.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Pemesanan</a>
                <a href="{{ route('purchases.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('purchases.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Pembelian</a>
                <a href="{{ route('transactions.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('transactions.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Transaksi
                    Umum</a>
                <a href="{{ route('hutang.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('hutang.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Hutang</a>
                <a href="{{ route('overhead-por.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('overhead-por.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Overhead
                    Perbulan</a>
                <a href="{{ route('sales.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('sales.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Penjualan</a>
                <a href="{{ route('sales-returns.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('sales-returns.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Retur
                    Penjualan</a>
                <a href="{{ route('penggajian.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('penggajian.*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Penggajian</a>

                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-4">Laporan</div>
                <a href="{{ route('reports.neraca') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('reports.neraca') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Laporan
                    Posisi Keuangan</a>
                <a href="{{ route('reports.laba-rugi') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('reports.laba-rugi') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">Laba
                    Rugi</a>
                <a href="{{ route('reports.job-order-hpp') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('reports.job-order-hpp') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">HPP
                    Job Order</a>
            </div>

            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-1 border-t border-gray-300 bg-white">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-900">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <a href="{{ route('profile.edit') }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        {{ __('Profile') }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>