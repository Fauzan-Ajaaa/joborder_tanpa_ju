@extends('layouts.app')

@section('title', 'Buat Pengurangan Produk')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Buat Pengurangan Produk</h1>
            <p class="text-sm text-gray-500">Kurangi stok produk dari job order</p>
        </div>
        <a href="{{ route('product-cancellations.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
            Kembali
        </a>
    </div>

    <div class="bg-white shadow sm:rounded-lg p-6">
        <form method="POST" action="{{ route('product-cancellations.store') }}" class="space-y-6">
            @csrf

            <!-- Job Order (Read-only jika dari parameter) -->
            <div>
                <label for="job_order_display" class="block text-sm font-medium text-gray-700">Kode Job Order</label>
                @if($jobOrder)
                    <input type="hidden" name="job_order_id" value="{{ $jobOrder->id }}">
                    <input type="text" id="job_order_display" value="{{ $jobOrder->kode_job }}" 
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50" readonly>
                @else
                    <select name="job_order_id" id="job_order_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="">Pilih Job Order</option>
                        @foreach(\App\Models\JobOrder::whereIn('status', ['in_progress', 'completed'])->orderBy('created_at', 'desc')->get() as $job)
                            <option value="{{ $job->id }}">{{ $job->kode_job }} - {{ $job->customer_name ?? $job->customer?->name ?? '-' }}</option>
                        @endforeach
                    </select>
                @endif
                @error('job_order_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Product Selection -->
            <div>
                <label for="product_id" class="block text-sm font-medium text-gray-700">Produk</label>
                <select name="product_id" id="product_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Pilih produk dari job order</option>
                </select>
                @error('product_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Quantity yang Diretur -->
            <div>
                <label for="quantity_cancelled" class="block text-sm font-medium text-gray-700">
                    Quantity yang Dikurangi 
                    <span id="qty_info" class="text-xs text-gray-500 font-normal">(Max: -)</span>
                </label>
                <input type="number" name="quantity_cancelled" id="quantity_cancelled" step="1" min="1" 
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                       placeholder="0" required inputmode="numeric" pattern="[0-9]*">
                <div id="qty_warning" class="mt-2 text-xs text-orange-600 hidden">
                    ⚠️ Quantity tidak boleh melebihi <span id="max_qty_display">0</span>
                </div>
                @error('quantity_cancelled')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Alasan Pembatalan -->
            <div>
                <label for="reason" class="block text-sm font-medium text-gray-700">Alasan Pengurangan</label>
                <textarea name="reason" id="reason" rows="3" 
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                          placeholder="Jelaskan alasan pengurangan (produk cacat, kesalahan input, dll.)" required>{{ old('reason') }}</textarea>
                @error('reason')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Perhitungan Nilai Retur (Preview) -->
            <div id="cost_preview" class="hidden bg-blue-50 border border-blue-200 rounded-md p-4">
                <h3 class="text-sm font-medium text-blue-900 mb-3">Perhitungan Nilai Pengurangan untuk Jurnal:</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-white p-3 rounded border">
                        <div class="text-gray-600">BBB (Bahan Baku)</div>
                        <div id="preview_bbb" class="text-lg font-semibold text-gray-900">Rp 0</div>
                    </div>
                    <div class="bg-white p-3 rounded border">
                        <div class="text-gray-600">BTKL (Tenaga Kerja)</div>
                        <div id="preview_btkl" class="text-lg font-semibold text-gray-900">Rp 0</div>
                    </div>
                    <div class="bg-white p-3 rounded border">
                        <div class="text-gray-600">BOP (Overhead Pabrik)</div>
                        <div id="preview_bop" class="text-lg font-semibold text-gray-900">Rp 0</div>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded">
                    <div class="flex justify-between items-center">
                        <span class="text-red-900 font-semibold">Total Nilai Kerugian:</span>
                        <span id="preview_total" class="text-xl font-bold text-red-600">Rp 0</span>
                    </div>
                    <p class="text-xs text-red-700 mt-1">Nilai ini akan dijurnal sebagai "Kerugian Produk Cacat"</p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('product-cancellations.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Ajukan Pengurangan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
}

document.addEventListener('DOMContentLoaded', function() {
    const jobOrderSelect = document.getElementById('job_order_id');
    const productSelect = document.getElementById('product_id');
    const quantityInput = document.getElementById('quantity_cancelled');
    const costPreview = document.getElementById('cost_preview');
    const qtyInfo = document.getElementById('qty_info');
    const qtyWarning = document.getElementById('qty_warning');
    const maxQtyDisplay = document.getElementById('max_qty_display');

    let currentJobOrderId = @if($jobOrder) {{ $jobOrder->id }} @else null @endif;
    let productCostData = {};
    let maxQuantity = 0;

    function loadProducts(jobOrderId) {
        if (!jobOrderId) {
            productSelect.innerHTML = '<option value="">Pilih job order terlebih dahulu</option>';
            return;
        }
        
        productSelect.innerHTML = '<option value="">Memuat produk...</option>';
        costPreview.classList.add('hidden');
        productCostData = {};

        console.log('Loading products for job order:', jobOrderId);

        fetch(`/job-orders/${jobOrderId}/products`)
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(products => {
                console.log('Raw response:', products);
                console.log('Products type:', typeof products);
                console.log('Products is array:', Array.isArray(products));
                console.log('Products length:', products ? products.length : 'null');
                
                productSelect.innerHTML = '<option value="">Pilih produk dari job order</option>';
                
                if (!products || products.length === 0) {
                    console.warn('No products returned from API');
                    productSelect.innerHTML = '<option value="">Tidak ada produk dalam job order ini</option>';
                    return;
                }

                console.log('Processing ' + products.length + ' products');
                
                products.forEach((product, index) => {
                    console.log('Product ' + index + ':', product);
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = `${product.name} (Qty: ${Math.floor(product.quantity)})`;
                    option.dataset.bbbPerUnit = product.bbb_per_unit;
                    option.dataset.btklPerUnit = product.btkl_per_unit;
                    option.dataset.bopPerUnit = product.bop_per_unit;
                    option.dataset.jobStatus = product.job_status;
                    option.dataset.quantity = product.quantity;
                    option.dataset.cancelledQuantity = product.cancelled_quantity;
                    option.dataset.availableQuantity = product.available_quantity;
                    
                    productCostData[product.id] = {
                        bbb_per_unit: parseFloat(product.bbb_per_unit),
                        btkl_per_unit: parseFloat(product.btkl_per_unit),
                        bop_per_unit: parseFloat(product.bop_per_unit),
                        job_status: product.job_status,
                        quantity: parseFloat(product.quantity),
                        cancelled_quantity: parseFloat(product.cancelled_quantity),
                        available_quantity: parseFloat(product.available_quantity)
                    };
                    
                    productSelect.appendChild(option);
                    console.log('Added option for product:', product.name);
                });
                
                console.log('Total products added:', products.length);
            })
            .catch(error => {
                console.error('Error loading products:', error);
                console.error('Error message:', error.message);
                productSelect.innerHTML = '<option value="">Gagal memuat produk: ' + error.message + '</option>';
            });
    }

    if (jobOrderSelect) {
        jobOrderSelect.addEventListener('change', function() {
            currentJobOrderId = this.value;
            loadProducts(currentJobOrderId);
        });
    }

    productSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            const quantity = Math.floor(parseFloat(selectedOption.dataset.quantity) || 0);
            const cancelledQuantity = Math.floor(parseFloat(selectedOption.dataset.cancelledQuantity) || 0);
            const availableQuantity = Math.floor(parseFloat(selectedOption.dataset.availableQuantity) || 0);
            
            maxQuantity = availableQuantity;
            
            // Update quantity info
            qtyInfo.textContent = `(Qty Pesanan: ${quantity}, Sudah Dibatalkan: ${cancelledQuantity}, Tersedia: ${availableQuantity})`;
            maxQtyDisplay.textContent = availableQuantity;
            quantityInput.max = availableQuantity;
            quantityInput.value = '';
            
            costPreview.classList.remove('hidden');
            updateCostPreview();
        } else {
            costPreview.classList.add('hidden');
            qtyInfo.textContent = '(Max: -)';
        }
    });

    quantityInput.addEventListener('input', function() {
        const value = parseFloat(this.value) || 0;
        
        // Validasi bilangan bulat
        if (this.value && !Number.isInteger(parseFloat(this.value))) {
            this.value = Math.floor(parseFloat(this.value));
        }
        
        if (value > maxQuantity && maxQuantity > 0) {
            qtyWarning.classList.remove('hidden');
        } else {
            qtyWarning.classList.add('hidden');
        }
        
        updateCostPreview();
    });

    function updateCostPreview() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const productId = productSelect.value;

        if (!quantity || !productId || !productCostData[productId]) {
            document.getElementById('preview_bbb').textContent = 'Rp 0';
            document.getElementById('preview_btkl').textContent = 'Rp 0';
            document.getElementById('preview_bop').textContent = 'Rp 0';
            document.getElementById('preview_total').textContent = 'Rp 0';
            return;
        }

        const costs = productCostData[productId];
        const bbbTotal = costs.bbb_per_unit * quantity;
        
        // BTKL dan BOP selalu dihitung, tidak hanya untuk completed jobs
        const btklTotal = costs.btkl_per_unit * quantity;
        const bopTotal = costs.bop_per_unit * quantity;

        const total = bbbTotal + btklTotal + bopTotal;

        document.getElementById('preview_bbb').textContent = formatCurrency(bbbTotal);
        document.getElementById('preview_btkl').textContent = formatCurrency(btklTotal);
        document.getElementById('preview_bop').textContent = formatCurrency(bopTotal);
        document.getElementById('preview_total').textContent = formatCurrency(total);
    }

    if (currentJobOrderId) {
        loadProducts(currentJobOrderId);
    }
});
</script>
@endsection