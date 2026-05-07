@extends('layouts.app')

@section('title', 'Laporan Buku Kas')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-lg mb-6 p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">CATATAN BUKU KAS KELUAR - MASUK</h1>
                    <p class="text-sm text-gray-500">Laporan semua transaksi kas masuk dan keluar</p>
                </div>
            </div>
        </div>

        {{-- Header Laporan --}}
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6 text-center">
            <div class="text-lg font-semibold text-gray-800">{{ auth()->user()->nama_perusahaan ?? 'Perusahaan Manufaktur' }}</div>
            <div class="text-sm text-gray-500 mt-1">Laporan Keuangan</div>
            <div class="text-base font-medium text-gray-700 mt-1">Buku Kas</div>
        </div>

        <div class="bg-white shadow-sm rounded-lg mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Laporan</h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Filter</label>
                    <select id="filterType" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="daily">Harian</option>
                        <option value="monthly">Bulanan</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>

                <div id="startDateWrap">
                    <label class="block text-sm font-medium text-gray-700 mb-2" id="startDateLabel">Tanggal</label>
                    <input type="date" id="startDate" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div id="endDateWrap">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
                    <input type="date" id="endDate" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div id="monthWrap" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bulan</label>
                    <select id="monthSelect" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>

                <div id="yearWrap" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                    <input type="number" id="yearInput" min="2000" max="2100" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                </div>

                <div class="flex items-end space-x-2 md:col-span-4">
                    <button onclick="loadData()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                        <i class="fas fa-search mr-2"></i>Tampilkan Data
                    </button>
                    <button onclick="exportPdf()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-medium">
                        <i class="fas fa-file-pdf mr-2"></i>Cetak PDF
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <p class="text-sm text-gray-500">Total Kas Masuk</p>
                <p class="text-2xl font-bold text-blue-600" id="totalDebit">Rp 0</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-6">
                <p class="text-sm text-gray-500">Total Kas Keluar</p>
                <p class="text-2xl font-bold text-red-600" id="totalCredit">Rp 0</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-6">
                <p class="text-sm text-gray-500">Saldo Akhir</p>
                <p class="text-2xl font-bold text-green-600" id="finalBalance">Rp 0</p>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">Detail Transaksi</h2>
                    <p class="text-sm text-gray-500" id="periodInfo">Semua transaksi</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan/Uraian</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Bukti</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Kredit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="transactionTableBody"></tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            <button onclick="showModal()" class="px-4 py-2 bg-gray-900 text-white rounded-md hover:bg-gray-800 text-sm font-medium">
                Tambah Transaksi Manual
            </button>
        </div>

        <div id="addTransactionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full z-50 hidden">
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Tambah Transaksi Manual</h3>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form id="addTransactionForm" onsubmit="addTransaction(event)">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Transaksi</label>
                                <input type="date" name="transaction_date" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                                <textarea name="description" rows="3" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">No. Bukti</label>
                                <input type="text" name="reference_no" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Debit (Masuk)</label>
                                <input type="number" name="debit" step="0.01" min="0" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="0">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kredit (Keluar)</label>
                                <input type="number" name="credit" step="0.01" min="0" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="0">
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 text-sm font-medium">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
let currentData = [];
let summary = { total_debit: 0, total_credit: 0, final_balance: 0 };

function getDateRange() {
    const filterType = document.getElementById('filterType').value;

    if (filterType === 'monthly') {
        const year = parseInt(document.getElementById('yearInput').value || '0', 10);
        const month = parseInt(document.getElementById('monthSelect').value || '0', 10);
        if (!year || !month) {
            return { error: 'Silakan pilih bulan dan tahun' };
        }

        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        const startDate = firstDay.toISOString().split('T')[0];
        const endDate = lastDay.toISOString().split('T')[0];

        return { startDate, endDate, filterType };
    }

    const startDate = document.getElementById('startDate').value;
    const endDateInput = document.getElementById('endDate').value;

    if (!startDate) {
        return { error: 'Silakan pilih tanggal' };
    }

    if (filterType === 'daily') {
        return { startDate, endDate: startDate, filterType };
    }

    if (!endDateInput) {
        return { error: 'Silakan pilih tanggal awal dan tanggal akhir' };
    }

    return { startDate, endDate: endDateInput, filterType };
}

function applyFilterUi() {
    const filterType = document.getElementById('filterType').value;
    const startDateLabel = document.getElementById('startDateLabel');
    const startDateWrap = document.getElementById('startDateWrap');
    const endDateWrap = document.getElementById('endDateWrap');
    const monthWrap = document.getElementById('monthWrap');
    const yearWrap = document.getElementById('yearWrap');

    if (filterType === 'daily') {
        startDateLabel.textContent = 'Tanggal';
        startDateWrap.classList.remove('hidden');
        endDateWrap.classList.add('hidden');
        monthWrap.classList.add('hidden');
        yearWrap.classList.add('hidden');
        document.getElementById('endDate').value = document.getElementById('startDate').value;
        return;
    }

    if (filterType === 'monthly') {
        startDateWrap.classList.add('hidden');
        endDateWrap.classList.add('hidden');
        monthWrap.classList.remove('hidden');
        yearWrap.classList.remove('hidden');
        return;
    }

    startDateLabel.textContent = 'Tanggal Awal';
    startDateWrap.classList.remove('hidden');
    endDateWrap.classList.remove('hidden');
    monthWrap.classList.add('hidden');
    yearWrap.classList.add('hidden');
}

function loadData() {
    const range = getDateRange();
    if (range.error) {
        alert(range.error);
        return;
    }

    fetch(`/reports/cash-book/data?filter_type=${range.filterType}&start_date=${range.startDate}&end_date=${range.endDate}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(r => r.json())
    .then(data => {
        currentData = data.data;
        summary = data.summary;
        updateTable();
        updateSummary();
        updatePeriodInfo();
    })
    .catch(() => alert('Terjadi kesalahan saat memuat data'));
}

function updateTable() {
    const tbody = document.getElementById('transactionTableBody');
    tbody.innerHTML = '';

    currentData.forEach((t, idx) => {
        const row = document.createElement('tr');
        row.className = idx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
        row.innerHTML = `
            <td class="px-6 py-3 text-sm text-gray-900">${idx + 1}</td>
            <td class="px-6 py-3 text-sm text-gray-900">${t.transaction_date}</td>
            <td class="px-6 py-3 text-sm text-gray-900">${t.description}</td>
            <td class="px-6 py-3 text-sm text-gray-900">${t.reference}</td>
            <td class="px-6 py-3 text-sm text-gray-900 text-right font-medium text-green-600">Rp ${formatNumber(t.debit)}</td>
            <td class="px-6 py-3 text-sm text-gray-900 text-right font-medium text-red-600">Rp ${formatNumber(t.credit)}</td>
            <td class="px-6 py-3 text-sm font-bold ${t.running_balance >= 0 ? 'text-green-600' : 'text-red-600'} text-right">Rp ${formatNumber(t.running_balance)}</td>
        `;
        tbody.appendChild(row);
    });
}

function updateSummary() {
    document.getElementById('totalDebit').textContent = `Rp ${formatNumber(summary.total_debit)}`;
    document.getElementById('totalCredit').textContent = `Rp ${formatNumber(summary.total_credit)}`;
    document.getElementById('finalBalance').textContent = `Rp ${formatNumber(summary.final_balance)}`;
}

function updatePeriodInfo() {
    const range = getDateRange();
    if (range.error) {
        document.getElementById('periodInfo').textContent = 'Semua transaksi';
        return;
    }

    const start = new Date(range.startDate).toLocaleDateString('id-ID');
    const end = new Date(range.endDate).toLocaleDateString('id-ID');

    document.getElementById('periodInfo').textContent = range.filterType === 'daily'
        ? `Periode ${start}`
        : `Periode ${start} - ${end}`;
}

function formatNumber(num) {
    return parseFloat(num || 0).toLocaleString('id-ID');
}

function exportPdf() {
    const range = getDateRange();
    if (range.error) {
        alert(range.error);
        return;
    }

    window.open(`/reports/cash-book/export-pdf?start_date=${range.startDate}&end_date=${range.endDate}`, '_blank');
}

function showModal() {
    document.getElementById('addTransactionModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('addTransactionModal').classList.add('hidden');
    document.getElementById('addTransactionForm').reset();
}

function addTransaction(event) {
    event.preventDefault();

    const formData = new FormData(event.target);

    fetch('/reports/cash-book/store', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Transaksi berhasil ditambahkan');
            closeModal();
            loadData();
        } else {
            alert('Gagal menambah transaksi: ' + data.message);
        }
    })
    .catch(() => alert('Terjadi kesalahan saat menambah transaksi'));
}

document.addEventListener('DOMContentLoaded', function() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    document.getElementById('startDate').value = firstDay.toISOString().split('T')[0];
    document.getElementById('endDate').value = lastDay.toISOString().split('T')[0];
    document.getElementById('monthSelect').value = String(today.getMonth() + 1);
    document.getElementById('yearInput').value = String(today.getFullYear());
    document.getElementById('filterType').value = 'monthly';

    applyFilterUi();

    document.getElementById('filterType').addEventListener('change', function () {
        applyFilterUi();
        updatePeriodInfo();
    });

    document.getElementById('startDate').addEventListener('change', function () {
        if (document.getElementById('filterType').value === 'daily') {
            document.getElementById('endDate').value = document.getElementById('startDate').value;
        }
        updatePeriodInfo();
    });

    document.getElementById('endDate').addEventListener('change', updatePeriodInfo);
    document.getElementById('monthSelect').addEventListener('change', updatePeriodInfo);
    document.getElementById('yearInput').addEventListener('change', updatePeriodInfo);
});
</script>
@endsection
