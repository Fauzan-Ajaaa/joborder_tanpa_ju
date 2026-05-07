@extends('layouts.app')

@section('title', 'Buat Jurnal Umum')

@push('scripts')
<script>
let itemIndex = 0;

function addItem() {
    itemIndex++;
    const itemsDiv = document.getElementById('items-container');
    const newItem = document.createElement('div');
    newItem.className = 'item-row border rounded-lg p-4 mb-4 bg-gray-50';
    newItem.innerHTML = `
        <div class="flex justify-between items-start mb-3">
            <h4 class="text-sm font-medium text-gray-700">Transaksi #${itemIndex}</h4>
            <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Akun</label>
                <select name="items[${itemIndex}][chart_of_account_id]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">-- Pilih Akun --</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" data-code="{{ $account->account_code }}">
                            {{ $account->account_code }} - {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Debit</label>
                <input type="number" step="0.01" min="0" name="items[${itemIndex}][debit]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="0" onchange="calculateTotals()">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Kredit</label>
                <input type="number" step="0.01" min="0" name="items[${itemIndex}][credit]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="0" onchange="calculateTotals()">
            </div>
        </div>
        <div class="mt-3">
            <label class="block text-sm font-medium text-gray-700">Keterangan</label>
            <input type="text" name="items[${itemIndex}][description]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Keterangan transaksi (opsional)">
        </div>
    `;
    itemsDiv.appendChild(newItem);
}

function removeItem(button) {
    const itemRow = button.closest('.item-row');
    itemRow.remove();
    calculateTotals();
}

function calculateTotals() {
    let totalDebit = 0;
    let totalCredit = 0;
    
    document.querySelectorAll('input[name*="[debit]"]').forEach(input => {
        totalDebit += parseFloat(input.value) || 0;
    });
    
    document.querySelectorAll('input[name*="[credit]"]').forEach(input => {
        totalCredit += parseFloat(input.value) || 0;
    });
    
    document.getElementById('total-debit').textContent = 'Rp ' + totalDebit.toLocaleString('id-ID');
    document.getElementById('total-credit').textContent = 'Rp ' + totalCredit.toLocaleString('id-ID');
    
    const balanceElement = document.getElementById('balance-status');
    if (Math.abs(totalDebit - totalCredit) < 0.01) {
        balanceElement.textContent = 'BALANCE';
        balanceElement.className = 'text-green-600 font-bold';
    } else {
        balanceElement.textContent = 'TIDAK BALANCE: Rp ' + Math.abs(totalDebit - totalCredit).toLocaleString('id-ID');
        balanceElement.className = 'text-red-600 font-bold';
    }
}

// Initialize dengan 2 item
document.addEventListener('DOMContentLoaded', function() {
    addItem();
    addItem();
    calculateTotals();
});
</script>
@endpush

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Buat Jurnal Umum</h1>
        <a href="{{ route('jurnal-umum.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Kembali</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="text-sm text-red-600">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form action="{{ route('jurnal-umum.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="transaction_date" class="block text-sm font-medium text-gray-700">Tanggal Transaksi</label>
                    <input type="date" name="transaction_date" id="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    @error('transaction_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Keterangan Jurnal</label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: Mencatat distribusi BTK" required>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Detail Transaksi</h3>
                <button type="button" onclick="addItem()" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                    + Tambah Transaksi
                </button>
            </div>
            
            <div id="items-container">
                <!-- Items will be added here dynamically -->
            </div>

            <div class="mt-6 border-t pt-4">
                <div class="grid grid-cols-3 gap-4 text-lg font-bold">
                    <div></div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600">Total Debit:</div>
                        <div id="total-debit" class="text-emerald-600">Rp 0</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-600">Total Kredit:</div>
                        <div id="total-credit" class="text-rose-600">Rp 0</div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <div id="balance-status" class="text-green-600 font-bold">BALANCE</div>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('jurnal-umum.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Batal</a>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Simpan Jurnal
            </button>
        </div>
    </form>
</div>
@endsection
