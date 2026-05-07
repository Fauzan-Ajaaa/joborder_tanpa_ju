@extends('layouts.app')

@section('title', 'Buat Job Order')

@push('scripts')
<script>
let productIndex = 0;

function addProduct() {
    productIndex++;
    const productsDiv = document.getElementById('products-container');
    const newProduct = document.createElement('div');
    newProduct.className = 'product-row border rounded-lg p-4 mb-4 bg-gray-50';
    newProduct.innerHTML = `
        <div class="flex justify-between items-start mb-3">
            <h4 class="text-sm font-medium text-gray-700">Produk #${productIndex}</h4>
            <button type="button" onclick="removeProduct(this)" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Produk</label>
                <select name="products[${productIndex}][product_id]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="updateProductStock(this)" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-stock="{{ (int) ($product->available_stock ?? 0) }}" data-barcode="{{ $product->barcode }}">
                            {{ $product->code }} - {{ $product->name }} (Stok tersedia: {{ (int) ($product->available_stock ?? 0) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Qty Order</label>
                <input type="number" step="0.01" min="0.01" name="products[${productIndex}][quantity]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" value="1" required>
                <p class="mt-1 text-xs text-gray-500 stock-info">Stok tersedia: -</p>
            </div>
        </div>
    `;
    productsDiv.appendChild(newProduct);
}

function removeProduct(button) {
    const productRow = button.closest('.product-row');
    productRow.remove();
}

function updateProductStock(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const stock = selectedOption ? parseFloat(selectedOption.getAttribute('data-stock')) || 0 : 0;
    const row = selectEl.closest('.product-row');
    if (!row) return;

    const qtyInput = row.querySelector('input[name^="products"][name$="[quantity]"]');
    const info = row.querySelector('.stock-info');

    if (qtyInput) {
        // Tidak set max lagi, biarkan user input quantity berapapun
        if (parseFloat(qtyInput.value || 0) === 0) {
            qtyInput.value = 1;
        }
    }

    if (info) {
        info.textContent = 'Stok tersedia: ' + stock;
    }
}

// Initialize dengan satu produk
document.addEventListener('DOMContentLoaded', function() {
    addProduct();
    // Setelah row pertama dibuat, set stok awal jika produk sudah terpilih (misal dari old input di masa depan)
    document.querySelectorAll('#products-container select[name^="products"][name$="[product_id]"]').forEach(updateProductStock);
    
    // Focus barcode scanner on load
    const barcodeInput = document.getElementById('barcode-scanner');
    if (barcodeInput) barcodeInput.focus();
});

function handleBarcodeScan(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const barcode = event.target.value.trim();
        if (!barcode) return;

        // Visual feedback for scan
        const scannerInput = document.getElementById('barcode-scanner');
        scannerInput.classList.add('ring-2', 'ring-green-500', 'border-green-500');
        setTimeout(() => {
            scannerInput.classList.remove('ring-2', 'ring-green-500', 'border-green-500');
        }, 300);

        // Search for product with this barcode
        const productOptions = document.querySelector('select[name^="products"]').options;
        let productId = null;
        
        for (let i = 0; i < productOptions.length; i++) {
            if (productOptions[i].getAttribute('data-barcode') === barcode) {
                productId = productOptions[i].value;
                break;
            }
        }

        if (productId) {
            // Find an empty row or add new one
            let targetSelect = null;
            const allSelects = document.querySelectorAll('select[name$="[product_id]"]');
            
            // Look for the last row if it's empty
            if (allSelects.length > 0) {
                const lastSelect = allSelects[allSelects.length - 1];
                if (!lastSelect.value) {
                    targetSelect = lastSelect;
                }
            }

            if (!targetSelect) {
                addProduct();
                const newAllSelects = document.querySelectorAll('select[name$="[product_id]"]');
                targetSelect = newAllSelects[newAllSelects.length - 1];
            }

            targetSelect.value = productId;
            updateProductStock(targetSelect);
            
            // Clear scanner and keep focus
            event.target.value = '';
        } else {
            alert('Produk dengan barcode "' + barcode + '" tidak ditemukan.');
            event.target.value = '';
        }
    }
}

function focusScanner() {
    const scanner = document.getElementById('barcode-scanner');
    const status = document.getElementById('scanner-status');
    scanner.focus();
    
    // UI Feedback
    status.classList.remove('hidden');
    status.classList.add('flex');
    scanner.placeholder = "Siap menscan barcode...";
}

function onScannerBlur() {
    const status = document.getElementById('scanner-status');
    status.classList.remove('flex');
    status.classList.add('hidden');
    document.getElementById('barcode-scanner').placeholder = "Klik tombol scan untuk memulai...";
}

</script>
@endpush

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">Buat Job Order</h1>
    </div>

    <div class="bg-white shadow sm:rounded-lg p-6">
        <form action="{{ route('job-orders.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Products Section -->
            <div>
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4 gap-4">
                    <div class="flex-1 w-full md:w-auto">
                        <h3 class="text-lg font-medium text-gray-900 mb-2 md:mb-0">Produk yang Dipesan</h3>
                    </div>
                    <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
                        <div class="flex items-center space-x-2 w-full md:w-auto">
                            <button type="button" onclick="focusScanner()" class="inline-flex items-center px-4 py-2 bg-blue-50 border border-blue-200 rounded-md font-semibold text-xs text-blue-700 uppercase tracking-widest hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition ease-in-out duration-150">
                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                                Mode Scan
                            </button>
                            <div class="relative flex-1 md:w-64">
                                <input type="text" id="barcode-scanner" onkeydown="handleBarcodeScan(event)" onblur="onScannerBlur()"
                                    class="block w-full pl-3 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                                    placeholder="Klik tombol scan untuk memulai...">
                                
                                <!-- Scanner Ready Status Badge -->
                                <div id="scanner-status" class="hidden absolute -top-8 left-0 animate-pulse items-center bg-green-100 text-green-800 text-xs font-bold px-2.5 py-0.5 rounded border border-green-200">
                                    <span class="relative flex h-2 w-2 mr-1">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                    </span>
                                    SCANNER SIAP
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="addProduct()" class="w-full md:w-auto px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors whitespace-nowrap">
                            + Tambah Produk
                        </button>
                    </div>
                </div>
                <div id="products-container">
                    <!-- Products will be added here dynamically -->
                </div>
            </div>

            <div>
                <label for="employee_id" class="block text-sm font-medium text-gray-700">Kasir</label>
                <select name="employee_id" id="employee_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">-- Pilih Karyawan --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                            {{ $employee->employee_number }} - {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="order_date" class="block text-sm font-medium text-gray-700">Tanggal Order</label>
                <input type="date" name="order_date" id="order_date" value="{{ old('order_date', now()->toDateString()) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                @error('order_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Customer Information -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Pelanggan</h3>
                
                <!-- Form Pelanggan Baru -->
                <div id="new-customer-section" class="space-y-4">
                    <div>
                        <label for="new_customer_name" class="block text-sm font-medium text-gray-700">Nama Pelanggan *</label>
                        <input type="text" name="new_customer_name" id="new_customer_name" value="{{ old('new_customer_name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        @error('new_customer_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="new_customer_phone" class="block text-sm font-medium text-gray-700">No. Telepon</label>
                        <input type="text" name="new_customer_phone" id="new_customer_phone" value="{{ old('new_customer_phone') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: 081234567890">
                        @error('new_customer_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="new_customer_address" class="block text-sm font-medium text-gray-700">Alamat</label>
                        <textarea name="new_customer_address" id="new_customer_address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Alamat lengkap pelanggan">{{ old('new_customer_address') }}</textarea>
                        @error('new_customer_address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('job-orders.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">Batal</a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Simpan Pemesanan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
