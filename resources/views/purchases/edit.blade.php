@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')
<div class="space-y-6">
    <!-- Debug Error Display -->
    @if(session('debug_error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <strong>Debug Error:</strong> {{ session('debug_error') }}
        </div>
    @endif
    
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Purchase Order #{{ $purchase->purchase_number }}</h1>
            <p class="mt-1 text-sm text-gray-600">Edit pembelian bahan baku atau bahan penolong dengan FOB dan PPN</p>
        </div>
        <a href="{{ route('purchases.show', $purchase) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Kembali
        </a>
    </div>

    <form action="{{ route('purchases.update', $purchase) }}" method="POST" id="purchaseForm" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Header Section -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Informasi Purchase Order</h3>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="purchase_date" class="block text-sm font-medium text-gray-700">Tanggal Pembelian *</label>
                        <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('purchase_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="supplier_id" class="block text-sm font-medium text-gray-700">Supplier *</label>
                        <select name="supplier_id" id="supplier_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="toggleNewSupplierFields()">
                            <option value="">Pilih Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                            <option value="new" {{ old('supplier_id') == 'new' ? 'selected' : '' }}>Supplier Lainnya</option>
                        </select>
                        @error('supplier_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- New Supplier Fields (Hidden by default) -->
                    <div id="new-supplier-fields" style="display: none;" class="col-span-1 md:col-span-2">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-blue-900 mb-3">Data Supplier Baru</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="new_supplier_name" class="block text-sm font-medium text-gray-700">Nama Supplier *</label>
                                    <input type="text" name="new_supplier_name" id="new_supplier_name" 
                                        value="{{ old('new_supplier_name') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Masukkan nama supplier">
                                    @error('new_supplier_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="new_supplier_phone" class="block text-sm font-medium text-gray-700">Nomor Telepon *</label>
                                    <input type="text" name="new_supplier_phone" id="new_supplier_phone" 
                                        value="{{ old('new_supplier_phone') }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Masukkan nomor telepon">
                                    @error('new_supplier_phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="purchase_type" class="block text-sm font-medium text-gray-700">Tipe Pembelian *</label>
                        <select name="purchase_type" id="purchase_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="switchPurchaseType()">
                            <option value="raw_material" {{ old('purchase_type', $purchase->purchase_type) == 'raw_material' ? 'selected' : '' }}>Bahan Baku</option>
                            <option value="auxiliary_material" {{ old('purchase_type', $purchase->purchase_type) == 'auxiliary_material' ? 'selected' : '' }}>Bahan Penolong</option>
                        </select>
                        @error('purchase_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fob_type" class="block text-sm font-medium text-gray-700">FOB Type *</label>
                        <select name="fob_type" id="fob_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="toggleFobCost()">
                            <option value="shipping_point" {{ old('fob_type', $purchase->fob_type) == 'shipping_point' ? 'selected' : '' }}>
                                Shipping Point (Pembeli bayar ongkir)
                            </option>
                            <option value="destination" {{ old('fob_type') == 'destination' ? 'selected' : '' }}>
                                Destination (Penjual bayar ongkir)
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            <strong>Shipping Point:</strong> Biaya pengiriman ditanggung pembeli (muncul FOB Cost)<br>
                            <strong>Destination:</strong> Biaya pengiriman ditanggung penjual (FOB Cost = 0)
                        </p>
                        @error('fob_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="fob-cost-container">
                        <label for="fob_cost" class="block text-sm font-medium text-gray-700">FOB Cost (Biaya Pengiriman) *</label>
                        <input type="number" name="fob_cost" id="fob_cost" value="{{ old('fob_cost', $purchase->fob_cost) }}" min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="calculateTotals()">
                        @error('fob_cost')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="ppn_rate" class="block text-sm font-medium text-gray-700">PPN Rate (%) *</label>
                        <input type="number" name="ppn_rate" id="ppn_rate" value="{{ old('ppn_rate', $purchase->ppn_rate) }}" required min="0" max="100" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="calculateTotals()">
                        @error('ppn_rate')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="discount_rate" class="block text-sm font-medium text-gray-700">Diskon (%)</label>
                        <input type="number" name="discount_rate" id="discount_rate" value="{{ old('discount_rate', $purchase->discount_rate) }}" min="0" max="100" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            onchange="calculateTotals()">
                        @error('discount_rate')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">Metode Pembayaran *</label>
                    <select name="payment_method" id="payment_method" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        onchange="toggleDueDate()">
                        <option value="cash" {{ old('payment_method', $purchase->payment_method) == 'cash' ? 'selected' : '' }}>
                            Tunai
                        </option>
                        <option value="credit" {{ old('payment_method') == 'credit' ? 'selected' : '' }}>
                            Hutang
                        </option>
                    </select>
                    @error('payment_method')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Due date hidden but still functional -->
                <div id="due-date-container" style="display: none;">
                    <input type="hidden" name="due_date" id="due_date" value="{{ old('due_date', $purchase->due_date ? $purchase->due_date->format('Y-m-d') : now()->addDays(30)->format('Y-m-d')) }}">
                </div>

                <div id="down-payment-container" style="display: none;">
                    <label for="down_payment" class="block text-sm font-medium text-gray-700">Uang Muka (DP)</label>
                    <input type="number" name="down_payment" id="down_payment" 
                        value="{{ old('down_payment', $purchase->down_payment ?? 0) }}" min="0" step="1"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="0"
                        oninput="updateSummary()">
                    @error('down_payment')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Catatan</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $purchase->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Items Section -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Item Pembelian</h3>
                <button type="button" onclick="addItem()" class="inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm leading-4 font-medium rounded-md text-gray-900 shadow-sm hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Item
                </button>
            </div>
            <div class="px-6 py-4">
                <div id="items-container" class="space-y-4">
                    <!-- Items will be added here dynamically -->
                </div>
                @error('items')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Summary Section -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Ringkasan Biaya</h3>
            </div>
            <div class="px-6 py-4">
                <div class="space-y-1">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Total Harga Termasuk PPN:</span>
                        <span class="font-medium text-blue-600" id="display-total-harga-termasuk-ppn">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">DPP Asli:</span>
                        <span class="font-medium text-purple-600" id="display-dpp-asli">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-sm" id="discount-row" style="display:none;">
                        <span class="text-gray-600">Diskon <span id="display-discount-rate">0</span>% dari DPP Asli:</span>
                        <span class="font-medium text-red-600" id="display-discount">− Rp 0</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">DPP Baru:</span>
                        <span class="font-medium text-green-600" id="display-dpp-baru">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-sm" id="ppn-row">
                        <span class="text-gray-600">PPN <span id="display-ppn-rate">11</span>%</span>
                        <span class="font-medium" id="display-ppn">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-sm text-xs text-gray-500" id="ppn-detail-row" style="display:none;">
                        <span class="text-gray-500">Detail PPN:</span>
                        <span class="font-medium text-gray-500" id="display-ppn-detail">-</span>
                    </div>
                    <div class="border-t border-gray-300 my-1"></div>
                    <div class="flex justify-between text-sm font-medium">
                        <span class="text-gray-700">Subtotal + PPN</span>
                        <span class="font-medium" id="display-subtotal-plus-ppn">Rp 0</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">FOB</span>
                        <span class="font-medium" id="display-fob">Rp 0</span>
                    </div>
                    <div class="border-t-2 border-gray-400 my-1"></div>
                    <div class="flex justify-between text-lg font-bold">
                        <span class="text-gray-900">TOTAL</span>
                        <span class="text-blue-600" id="display-total">Rp 0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('purchases.show', $purchase) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                Batal
            </a>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Update Purchase Order
            </button>
        </div>
    </form>
</div>

<script>
    /*
     * rawMaterials dari controller sudah mengandung array "units":
     * [
     *   { unit: 'Ekor', unit_label: 'Ekor', price_per_unit: 60000 },
     *   { unit: 'Potong', unit_label: 'Potong', price_per_unit: 12000 },  ← dari RawMaterialUnitConversion
     * ]
     * Saat user ganti satuan, harga otomatis ikut berubah sesuai satuan.
     */
    const rawMaterials = @json($rawMaterials);
    const auxiliaryMaterials = @json($auxiliaryMaterials);

    let itemIndex = 0;

    /* ── HELPERS ─────────────────────────────────────────────────────── */
    function getPurchaseType() {
        return document.getElementById('purchase_type').value;
    }

    function isAuxiliaryType() {
        return getPurchaseType() === 'auxiliary_material';
    }

    function formatRupiah(n) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));
    }

    function formatNumber(input) {
        // Remove existing formatting
        let value = input.value.replace(/\./g, '');
        
        // Convert to number and back to string with formatting
        if (value === '') value = '0';
        let num = parseFloat(value);
        
        if (!isNaN(num)) {
            // Format with dots after every 3 digits and remove trailing zeros
            let formatted = num.toLocaleString('id-ID', { maximumFractionDigits: 20 });
            // Remove trailing zeros and decimal point if not needed
            formatted = formatted.replace(/,?0+$/, '');
            input.value = formatted;
            
            // Update hidden field if this is a conversion factor input
            const index = input.id.match(/conversion-factor-(\d+)/);
            if (index) {
                const hiddenField = document.getElementById(`hidden-conversion-factor-${index[1]}`);
                if (hiddenField) {
                    hiddenField.value = num.toString(); // Store raw number in hidden field
                }
                // Update dual quantity display
                updateDualQuantityDisplay(index[1]);
            }
        }
    }

    /**
     * Escape untuk HTML attribute (single quote & double quote)
     */
    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/'/g, '&#39;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Ambil array satuan dari data material.
     * Controller sudah menyiapkan field "units" — fallback ke satuan tunggal jika tidak ada.
     */
    function getMaterialUnits(material) {
        if (material.units && Array.isArray(material.units) && material.units.length > 0) {
            return material.units.map(u => ({
                unit: u.unit,
                unit_label: u.unit_label || u.unit,
                price: parseFloat(u.price_per_unit || 0),
                is_base_unit: u.unit === material.unit,
                factor: parseFloat(u.factor || 1)
            }));
        }
        // fallback satuan tunggal
        return [{
            unit: material.unit || '',
            unit_label: material.unit_label || material.unit || '',
            price: parseFloat(material.price_per_unit || 0),
            is_base_unit: true,
            factor: 1.0
        }];
    }

    /* ── INIT ─────────────────────────────────────────────────────────── */
        const existingItems = @json($purchase->items);

    document.addEventListener('DOMContentLoaded', function() {
        filterMaterialsBySupplier();
        
        if (existingItems && existingItems.length > 0) {
            existingItems.forEach(item => {
                addItem();
                const idx = itemIndex - 1;
                
                const aux = !!item.auxiliary_material_id;
                const matId = aux ? item.auxiliary_material_id : item.raw_material_id;
                const matSel = aux 
                   ? document.querySelector(`select[name="items[${idx}][auxiliary_material_id]"]`)
                   : document.querySelector(`select[name="items[${idx}][raw_material_id]"]`);
                
                if (matSel) {
                    matSel.value = matId;
                    updateMaterialInfo(idx);
                }
                
                const unitSel = document.getElementById(`unit-${idx}`);
                if (unitSel && item.unit) {
                    unitSel.value = item.unit;
                    onUnitChange(idx);
                }

                const taxSel = document.querySelector(`select[name="items[${idx}][tax_type]"]`);
                if (taxSel && item.tax_type) {
                    taxSel.value = item.tax_type;
                }

                const convFactor = parseFloat(item.conversion_factor || 1);
                const convInput = document.getElementById(`conversion-factor-${idx}`);
                if (convInput) {
                    convInput.value = convFactor;
                    onConversionFactorChange(idx);
                }

                // Correct display quantity and price
                const displayQty = parseFloat(item.quantity || 0) * convFactor;
                const qDisp = document.querySelector(`input[name="items[${idx}][quantity_display]"]`);
                if (qDisp) qDisp.value = displayQty;

                const displayPrice = parseFloat(item.unit_price || 0) / convFactor;
                const pDisp = document.querySelector(`input[name="items[${idx}][unit_price_display]"]`);
                const pHid = document.querySelector(`input[name="items[${idx}][unit_price]"]`);
                if (pDisp) pDisp.value = formatRupiah(displayPrice);
                if (pHid) pHid.value = displayPrice;

                onManualPriceInput(idx);
                calculateItemSubtotal(idx);
            });
        } else {
            addItem();
        }

        toggleFobCost();
        toggleDueDate();
        toggleNewSupplierFields();
        calculateTotals();
    });

    function switchPurchaseType() {
        document.getElementById('items-container').innerHTML = '';
        itemIndex = 0;
        addItem();
    }

    /* ── SUPPLIER ─────────────────────────────────────────────────────── */
    function toggleNewSupplierFields() {
        const val = document.getElementById('supplier_id').value;
        const fields = document.getElementById('new-supplier-fields');
        const nameEl = document.getElementById('new_supplier_name');
        const phoneEl = document.getElementById('new_supplier_phone');

        if (val === 'new') {
            fields.style.display = 'block';
            nameEl.setAttribute('required', 'required');
            phoneEl.setAttribute('required', 'required');
        } else {
            fields.style.display = 'none';
            nameEl.removeAttribute('required');
            nameEl.value = '';
            phoneEl.removeAttribute('required');
            phoneEl.value = '';
        }
    }

    function filterMaterialsBySupplier() {
        const supplierId = document.getElementById('supplier_id').value;
        const aux = isAuxiliaryType();
        const source = aux ? auxiliaryMaterials : rawMaterials;
        const filtered = source; // Show all materials regardless of supplier

        const selector = aux ?
            'select[name*="[auxiliary_material_id]"]' :
            'select[name*="[raw_material_id]"]';

        document.querySelectorAll(selector).forEach(select => {
            const currentVal = select.value;
            const placeholder = aux ? 'Pilih Bahan Penolong' : 'Pilih Bahan';
            select.innerHTML = `<option value="">${placeholder}</option>`;

            filtered.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.name;
                opt.dataset.unitsJson = JSON.stringify(getMaterialUnits(m));
                if (m.id == currentVal) opt.selected = true;
                select.appendChild(opt);
            });

            const idx = (select.name.match(/\[(\d+)\]/) || [])[1];
            if (idx !== undefined) rebuildUnitSelect(idx);
        });
    }

    /* ── ADD / REMOVE ITEM ────────────────────────────────────────────── */
    function addItem() {
        const container = document.getElementById('items-container');
        const supplierId = document.getElementById('supplier_id').value;
        const aux = isAuxiliaryType();
        const source = aux ? auxiliaryMaterials : rawMaterials;
        const filtered = source; // Show all materials regardless of supplier

        const label = aux ? 'Bahan Penolong *' : 'Bahan Baku *';
        const selName = aux ?
            `items[${itemIndex}][auxiliary_material_id]` :
            `items[${itemIndex}][raw_material_id]`;
        const placeholder = aux ? 'Pilih Bahan Penolong' : 'Pilih Bahan';

        const options = filtered.map(m =>
            `<option value="${m.id}" data-units-json='${esc(JSON.stringify(getMaterialUnits(m)))}'>${m.name}</option>`
        ).join('');

        const idx = itemIndex;
        const div = document.createElement('div');
        div.className = 'border border-gray-200 rounded-lg p-4 item-row';
        div.dataset.index = idx;
        div.dataset.manualPrice = 'false';

        div.innerHTML = `
        <div class="flex items-start gap-4">
            <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-4">

                <!-- Kolom 1: Pilih Bahan -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">${label}</label>
                    <select name="${selName}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 item-material-select"
                        onchange="updateMaterialInfo(${idx})">
                        <option value="">${placeholder}</option>
                        ${options}
                    </select>
                </div>

                <!-- Kolom 2: Quantity + Satuan -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Quantity *</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" name="items[${idx}][quantity_display]" required min="0.01" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            oninput="calculateItemSubtotal(${idx}); updateDualQuantityDisplay(${idx})">
                        <input type="hidden" name="items[${idx}][quantity]">
                        <span id="base-unit-display-${idx}" class="mt-1 text-sm text-gray-600 font-medium"></span>
                    </div>
                    <div id="dual-quantity-display-${idx}" class="mt-1 text-xs text-gray-600 hidden"></div>

                    <!--
                        Dropdown satuan diisi oleh rebuildUnitSelect().
                        Setiap <option> membawa data-price = harga untuk satuan itu.
                        Saat user ganti satuan → onUnitChange() update harga otomatis.
                    -->
                    <select id="unit-${idx}" name="items[${idx}][unit]"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs"
                        onchange="onUnitChange(${idx})">
                        <option value="">-</option>
                    </select>
                    <p class="mt-1 text-xs text-blue-500" id="unit-hint-${idx}"></p>
                    
                    <!-- Kolom konversi yang muncul saat unit konversi dipilih -->
                    <div id="conversion-section-${idx}" class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-md hidden">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Informasi Konversi</label>
                        <div class="flex items-center space-x-2">
                            <input type="number" id="conversion-factor-${idx}" 
                                class="w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs bg-white"
                                step="any" min="0" value="0"
                                placeholder="Faktor"
                                onchange="onConversionFactorChange(${idx})"
                                oninput="updateConversionInfo(${idx})">
                            <input type="hidden" name="items[${idx}][conversion_factor]" id="hidden-conversion-factor-${idx}" value="1">
                            <span class="text-xs text-gray-600 font-medium whitespace-nowrap flex items-center">
                                <span id="conv-label-prefix-${idx}"></span>
                                <select id="conv-target-unit-${idx}" name="items[${idx}][conversion_target_unit]" class="ml-1 hidden py-0 text-xs rounded border-gray-300 w-auto" onchange="updateConversionInfo(${idx}); onConversionFactorChange(${idx})"></select>
                                <span id="conv-label-suffix-${idx}"></span>
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-blue-600" id="conversion-info-${idx}"></p>
                    </div>
                </div>

                <!-- Kolom 3: Harga Satuan -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Harga Satuan *</label>
                    <input type="text" name="items[${idx}][unit_price_display]" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        autocomplete="off"
                        oninput="onManualPriceInput(${idx})"
                        onchange="calculateItemSubtotal(${idx})">
                    <input type="hidden" name="items[${idx}][unit_price]">
                    <div id="price-before-tax-${idx}" class="mt-1 text-xs text-gray-500" style="display:none;">
                        Harga sebelum pajak: <span id="price-before-tax-value-${idx}">Rp 0</span>
                    </div>
                </div>

                <!-- Kolom 4: Pajak -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pajak</label>
                    <select name="items[${idx}][tax_type]"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        onchange="calculateItemSubtotal(${idx})">
                        <option value="before_tax">Sebelum Pajak</option>
                        <option value="after_tax" selected>Termasuk Pajak</option>
                    </select>
                </div>

                <!-- Kolom 5: Subtotal -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Subtotal</label>
                    <input type="text" readonly
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm"
                        id="subtotal-${idx}" value="Rp 0">
                    <div id="subtotal-before-tax-${idx}" class="mt-1 text-xs text-gray-500" style="display: none;">
                        Sebelum PPN: <span id="subtotal-before-tax-value-${idx}">Rp 0</span>
                    </div>
                </div>
            </div>

            <!-- Hapus -->
            <div class="flex-shrink-0 pt-6">
                <button type="button" onclick="removeItem(${idx})" class="text-red-600 hover:text-red-900">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    `;

        container.appendChild(div);
        itemIndex++;
    }

    function removeItem(index) {
        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        if (row) {
            row.remove();
            calculateTotals();
        }
    }

    /* ── UPDATE MATERIAL INFO ─────────────────────────────────────────── */
    /**
     * Dipanggil saat user pilih bahan baku/penolong.
     * Reset flag harga manual, lalu rebuild dropdown satuan.
     */
    function updateMaterialInfo(index) {
        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        if (row) row.dataset.manualPrice = 'false';
        rebuildUnitSelect(index);
        calculateItemSubtotal(index);
    }

    /**
     * Rebuild dropdown satuan berdasarkan bahan yang dipilih.
     * Setiap <option> membawa data-price = harga untuk satuan itu.
     * Harga sudah dihitung di controller: price_alternatif = price_dasar / factor
     *   Contoh: Ekor = Rp 60.000, factor ke Potong = 5 → Potong = Rp 12.000
     */
    function rebuildUnitSelect(index) {
        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        const matSel = row ?
            row.querySelector('select[name*="raw_material_id"], select[name*="auxiliary_material_id"]') :
            null;
        const unitSel = document.getElementById(`unit-${index}`);
        const hintEl = document.getElementById(`unit-hint-${index}`);
        const baseUnitDisplay = document.getElementById(`base-unit-display-${index}`);
        if (!unitSel) return;

        const selOpt = matSel ? matSel.options[matSel.selectedIndex] : null;
        let units = [];
        let baseUnit = null;
        try {
            units = JSON.parse(selOpt?.dataset?.unitsJson || '[]');
            const baseUnitData = units.find(u => u.is_base_unit);
            baseUnit = baseUnitData ? baseUnitData.unit : units[0]?.unit;
        } catch (e) {}

        const prevUnit = unitSel.value;
        unitSel.innerHTML = '';

        if (!units.length || !selOpt?.value) {
            unitSel.innerHTML = '<option value="">-</option>';
            if (hintEl) hintEl.textContent = '';
            if (baseUnitDisplay) baseUnitDisplay.textContent = '';
            setItemPrice(index, 0);
            return;
        }

        units.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.unit;
            opt.textContent = u.unit_label || u.unit;
            opt.dataset.price = u.price;
            unitSel.appendChild(opt);
        });

        // Pertahankan pilihan sebelumnya; kalau tidak ada → pakai unit pertama
        const match = units.find(u => u.unit === prevUnit);
        unitSel.value = match ? prevUnit : units[0].unit;

        // Display base unit next to quantity - removed to hide unit display
        if (baseUnitDisplay && baseUnit) {
            baseUnitDisplay.textContent = ''; // Hide the unit display
        }

        // Hint satuan yang tersedia (hanya tampil jika > 1 satuan)
        if (hintEl) {
            hintEl.textContent = units.length > 1 ?
                'Tersedia: ' + units.map(u => u.unit_label || u.unit).join(' / ') :
                '';
        }

        // Set harga otomatis kecuali sudah diketik manual
        if (!row || row.dataset.manualPrice !== 'true') {
            const active = units.find(u => u.unit === unitSel.value) || units[0];
            setItemPrice(index, active.price);
        }
        
        // Always trigger onUnitChange to show conversion info
        onUnitChange(index);
    }

    /* ── EVENT: user ganti satuan ─────────────────────────────────────── */
    /**
     * Saat user ganti satuan:
     * - Reset flag manual (ganti satuan → ikut harga default satuan baru)
     * - Update harga ke harga satuan yang dipilih
     * - Hitung ulang subtotal
     */
    function onConversionFactorChange(index) {
        const conversionFactor = document.getElementById(`conversion-factor-${index}`);
        const hiddenConversionFactor = document.getElementById(`hidden-conversion-factor-${index}`);
        const rawValue = conversionFactor.value;
        conversionFactor.value = rawValue; // Store raw value for calculations
        hiddenConversionFactor.value = rawValue; // Update hidden field for form submission
        updateConversionInfo(index);
        updateDualQuantityDisplay(index);
        calculateItemSubtotal(index);
    }

    function updateConversionInfo(index) {
        const conversionFactor = document.getElementById(`conversion-factor-${index}`);
        const conversionLabel = document.getElementById(`conversion-label-${index}`);
        const conversionInfo = document.getElementById(`conversion-info-${index}`);
        const unitSel = document.getElementById(`unit-${index}`);
        const matSel = document.querySelector(`select[name="items[${index}][raw_material_id]"]`) || 
                      document.querySelector(`select[name="items[${index}][auxiliary_material_id]"]`);
        
        let units = [];
        let baseUnit = null;
        try {
            if (matSel) {
                const selOpt = matSel.options[matSel.selectedIndex];
                units = JSON.parse(selOpt?.dataset?.unitsJson || '[]');
                const baseUnitData = units.find(u => u.is_base_unit);
                baseUnit = baseUnitData ? baseUnitData.unit : units[0]?.unit;
            }
        } catch (e) {}
        
        const active = unitSel?.options[unitSel.selectedIndex];
        const factor = parseFloat(conversionFactor?.value || 0);
        
        const prefix = document.getElementById(`conv-label-prefix-${index}`);
        const suffix = document.getElementById(`conv-label-suffix-${index}`);
        const targetSel = document.getElementById(`conv-target-unit-${index}`);
        
        if (active && baseUnit && conversionInfo && prefix && suffix && targetSel) {
            
            if (active.value === baseUnit) {
                // Populate target dropdown with ONLY non-base units
                if(targetSel.options.length === 0 || targetSel.dataset.forBase !== baseUnit) {
                    targetSel.innerHTML = '';
                    units.filter(u => u.unit !== baseUnit).forEach(u => {
                        targetSel.add(new Option(u.unit_label || u.unit, u.unit));
                    });
                    targetSel.dataset.forBase = baseUnit;
                }
                
                targetSel.classList.remove('hidden');
                suffix.classList.add('hidden');
                
                prefix.textContent = `1 ${baseUnit} =`;
                
                const selectedTarget = targetSel.value;
                conversionInfo.textContent = `Referensi: 1 ${baseUnit} sama dengan ${factor} ${selectedTarget}`;
            } else {
                targetSel.classList.add('hidden');
                suffix.classList.remove('hidden');
                
                prefix.textContent = `1 ${baseUnit} = `;
                suffix.textContent = `${active.value}`;
                
                // Get correct conversion factor from global conversion table
                let correctFactor = factor;
                if (factor <= 0 || factor === 1) {
                    const selectedUnitData = units.find(u => u.unit === active.value);
                    if (selectedUnitData && selectedUnitData.factor) {
                        correctFactor = selectedUnitData.factor;
                    } else {
                        // Try to get the correct conversion factor
                        correctFactor = getConversionFactor(baseUnit, active.value) || 1;
                    }
                    // Update the conversion factor input
                    if (conversionFactor && !conversionFactor.disabled) {
                        conversionFactor.value = correctFactor;
                        const hiddenConversionFactor = document.getElementById(`hidden-conversion-factor-${index}`);
                        if (hiddenConversionFactor) {
                            hiddenConversionFactor.value = correctFactor;
                        }
                    }
                }
                
                conversionInfo.textContent = `Setiap 1 ${baseUnit} sama dengan ${correctFactor} ${active.value}`;
                
                // Update harga berdasarkan faktor konversi kustom
                const baseUnitData = units.find(u => u.unit === baseUnit);
                if (baseUnitData && correctFactor > 0 && !conversionFactor.disabled) {
                    // If selected unit is not base unit, adjust price per selected unit
                    // Example: if base price is Rp 9,000 per KG, then per GRAM it should be Rp 9,000 / 1000 = Rp 9
                    const newPrice = baseUnitData.price / correctFactor;
                    setItemPrice(index, newPrice);
                    
                    // Update data-price di option
                    if (active) {
                        active.dataset.price = newPrice.toFixed(2);
                    }
                }
            }
        }
    }

    function updateDualQuantityDisplay(index) {
        const quantityInput = document.querySelector(`input[name="items[${index}][quantity_display]"]`);
        const unitSel = document.getElementById(`unit-${index}`);
        const conversionFactor = document.getElementById(`conversion-factor-${index}`);
        const dualDisplay = document.getElementById(`dual-quantity-display-${index}`);
        
        if (!quantityInput || !unitSel || !dualDisplay) return;
        
        const quantity = parseFloat(quantityInput.value || 0);
        const selectedUnit = unitSel.value;
        
        // Get material data
        const matSel = document.querySelector(`select[name="items[${index}][raw_material_id]"]`) || 
                      document.querySelector(`select[name="items[${index}][auxiliary_material_id]"]`);
        
        if (!matSel || !selectedUnit) {
            dualDisplay.classList.add('hidden');
            return;
        }
        
        let units = [];
        let baseUnit = null;
        try {
            const selOpt = matSel.options[matSel.selectedIndex];
            units = JSON.parse(selOpt?.dataset?.unitsJson || '[]');
            const baseUnitData = units.find(u => u.is_base_unit);
            baseUnit = baseUnitData ? baseUnitData.unit : units[0]?.unit;
        } catch (e) {}
        
        if (!baseUnit || baseUnit === selectedUnit) {
            dualDisplay.classList.add('hidden');
            return;
        }
        
        // Get conversion factor
        let factor = parseFloat(conversionFactor?.value || 0);
        
        // Get correct conversion factor if the current one is invalid
        if (factor <= 0 || factor === 1) {
            const selectedUnitData = units.find(u => u.unit === selectedUnit);
            if (selectedUnitData && selectedUnitData.factor) {
                factor = selectedUnitData.factor;
            } else {
                factor = getConversionFactor(baseUnit, selectedUnit) || 1;
            }
            // Update the conversion factor input for consistency
            if (conversionFactor && !conversionFactor.disabled) {
                conversionFactor.value = factor;
                const hiddenConversionFactor = document.getElementById(`hidden-conversion-factor-${index}`);
                if (hiddenConversionFactor) {
                    hiddenConversionFactor.value = factor;
                }
            }
        }
        
        if (factor > 0) {
            let displayText = '';
            if (selectedUnit === baseUnit) {
                // Input in base unit, show converted to other unit
                const convertedQty = quantity * factor;
                const otherUnit = units.find(u => u.unit !== baseUnit)?.unit || '';
                displayText = `${quantity} ${baseUnit} = ${convertedQty.toLocaleString('id-ID', { maximumFractionDigits: 3 })} ${otherUnit}`;
            } else {
                // Input in conversion unit (GRAM), show as: 500 GRAM = 0.5 KG
                const baseQty = quantity / factor; // Convert GRAM to KG
                displayText = `${quantity} ${selectedUnit} = ${baseQty.toLocaleString('id-ID', { maximumFractionDigits: 3 })} ${baseUnit}`;
            }
            
            dualDisplay.textContent = displayText;
            dualDisplay.classList.remove('hidden');
        } else {
            dualDisplay.classList.add('hidden');
        }
    }

    function onUnitChange(index) {
        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        const unitSel = document.getElementById(`unit-${index}`);
        const matSel = document.querySelector(`select[name="items[${index}][raw_material_id]"]`) || 
                      document.querySelector(`select[name="items[${index}][auxiliary_material_id]"]`);
        
        const active = unitSel?.options[unitSel.selectedIndex];
        
        // Get material data to check conversion factors
        let units = [];
        let baseUnit = null;
        try {
            if (matSel) {
                const selOpt = matSel.options[matSel.selectedIndex];
                units = JSON.parse(selOpt?.dataset?.unitsJson || '[]');
                const baseUnitData = units.find(u => u.is_base_unit);
                baseUnit = baseUnitData ? baseUnitData.unit : units[0]?.unit;
            }
        } catch (e) {}
        
        // Always show conversion information for all units
        const conversionSection = document.getElementById(`conversion-section-${index}`);
        const prefix = document.getElementById(`conv-label-prefix-${index}`);
        const suffix = document.getElementById(`conv-label-suffix-${index}`);
        const targetSel = document.getElementById(`conv-target-unit-${index}`);
        const conversionFactor = document.getElementById(`conversion-factor-${index}`);
        
        if (active && baseUnit && conversionSection) {
            if (active.value !== baseUnit) {
                // This is a conversion unit - show conversion section with editable factor
                const selectedUnitData = units.find(u => u.unit === active.value);
                const baseUnitData = units.find(u => u.unit === baseUnit);
                
                if (selectedUnitData && baseUnitData) {
                    // Get the correct conversion factor
                    let factor = selectedUnitData.factor;
                    if (!factor) {
                        factor = getConversionFactor(baseUnit, active.value) || 1;
                    }
                    
                    // If the material already has predefined prices for units, use that
                    if (selectedUnitData.price && selectedUnitData.price > 0) {
                        // Use the predefined price for this unit
                        setItemPrice(index, selectedUnitData.price);
                    } else {
                        // Calculate price based on conversion factor
                        const newPrice = baseUnitData.price / factor;
                        setItemPrice(index, newPrice);
                    }
                    
                    if (conversionFactor) conversionFactor.value = factor;
                    const hiddenConversionFactor = document.getElementById(`hidden-conversion-factor-${index}`);
                    if (hiddenConversionFactor) {
                        hiddenConversionFactor.value = factor.toString();
                    }
                    updateConversionInfo(index);
                    conversionSection.classList.remove('hidden');
                }
            } else {
                // This is base unit - show simple conversion info (editable, start from 0)
                const otherUnits = units.filter(u => u.unit !== baseUnit);
                if (otherUnits.length > 0) {
                    // Start rendering base unit mode
                    conversionSection.classList.remove('hidden');
                    
                    if(targetSel.options.length === 0 || targetSel.dataset.forBase !== baseUnit) {
                        targetSel.innerHTML = '';
                        otherUnits.forEach(u => {
                            targetSel.add(new Option(u.unit_label || u.unit, u.unit));
                        });
                        targetSel.dataset.forBase = baseUnit;
                    }
                    targetSel.classList.remove('hidden');
                    suffix.classList.add('hidden');
                    
                    const selectedTarget = targetSel.value;
                    const targetUnitData = units.find(u => u.unit === selectedTarget);
                    const baseUnitData = units.find(u => u.unit === baseUnit);
                    
                    if (baseUnitData && targetUnitData) {
                        // Gunakan factor dari data unit, bukan dari rasio harga
                        const factor = targetUnitData.factor > 0 ? targetUnitData.factor : (targetUnitData.factor || 0);
                        
                        if (conversionFactor) {
                            conversionFactor.value = factor;
                            conversionFactor.disabled = false;
                            conversionFactor.classList.remove('bg-gray-100');
                        }
                        
                        // Update hidden field
                        const hiddenConversionFactor = document.getElementById(`hidden-conversion-factor-${index}`);
                        if (hiddenConversionFactor) {
                            hiddenConversionFactor.value = factor.toString();
                        }
                        
                        updateConversionInfo(index);
                    }
                } else {
                    // No other units available, hide conversion section
                    conversionSection.classList.add('hidden');
                }
            }
        }

        if (active && active.dataset.price !== undefined) {
            if (row) row.dataset.manualPrice = 'false';
            setItemPrice(index, parseFloat(active.dataset.price || 0));
        }
        calculateItemSubtotal(index);
        updateDualQuantityDisplay(index);
    }

    /* ── EVENT: user ketik harga manual ──────────────────────────────── */
    function onManualPriceInput(index) {
        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        if (row) row.dataset.manualPrice = 'true';
        calculateItemSubtotal(index);
    }

    /* ── SET HARGA KE INPUT ───────────────────────────────────────────── */
    function setItemPrice(index, price) {
        const disp = document.querySelector(`input[name="items[${index}][unit_price_display]"]`);
        const hid = document.querySelector(`input[name="items[${index}][unit_price]"]`);
        if (disp) disp.value = formatRupiah(price);
        if (hid) hid.value = price;
    }

    /* ── SUBTOTAL PER ITEM ────────────────────────────────────────────── */
    function calculateItemSubtotal(index) {
        const row = document.querySelector(`.item-row[data-index="${index}"]`);
        if (!row) return;

        const displayQty = parseFloat(document.querySelector(`input[name="items[${index}][quantity_display]"]`)?.value || 0);
        const rawPriceString = (document.querySelector(`input[name="items[${index}][unit_price_display]"]`)?.value || '0');
        // Handle both currency formatted strings and raw numeric strings
        let price = 0;
        if (rawPriceString.includes('Rp')) {
            price = parseFloat(rawPriceString.replace(/[^0-9]/g, '') || 0);
        } else {
            price = parseFloat(rawPriceString || 0);
        }
        const taxType = document.querySelector(`select[name="items[${index}][tax_type]"]`)?.value || 'after_tax';
        const ppnRate = parseFloat(document.getElementById('ppn_rate')?.value || 0);

        // Get unit information for conversion
        const unitSel = document.getElementById(`unit-${index}`);
        const matSel = row ?
            row.querySelector('select[name*="raw_material_id"], select[name*="auxiliary_material_id"]') :
            null;
        
        let baseQty = displayQty; // Default: quantity is already in base unit
        let units = [];
        let baseUnit = null;
        
        try {
            if (matSel && unitSel) {
                const selOpt = matSel.options[matSel.selectedIndex];
                units = JSON.parse(selOpt?.dataset?.unitsJson || '[]');
                const baseUnitData = units.find(u => u.is_base_unit);
                baseUnit = baseUnitData ? baseUnitData.unit : units[0]?.unit;
                
                // If selected unit is conversion unit, convert display quantity to base unit for backend
                const selectedUnit = unitSel.value;
                if (selectedUnit && selectedUnit !== baseUnit) {
                    const conversionFactorElement = document.getElementById(`conversion-factor-${index}`);
                    const rawConversionFactor = conversionFactorElement?.value;
                    const conversionFactor = parseFloat(rawConversionFactor || 0);
                    if (conversionFactor > 0) {
                        // Convert display quantity to base unit quantity
                        // Example: 500 GRAM / 1000 = 0.5 KG
                        baseQty = displayQty / conversionFactor;
                    }
                }
            }
        } catch (e) {}

        // Info harga sebelum pajak (mode after_tax)
        const ptDiv = document.getElementById(`price-before-tax-${index}`);
        const ptVal = document.getElementById(`price-before-tax-value-${index}`);
        if (taxType === 'after_tax' && ptDiv && ptVal) {
            const priceBeforeTax = price / (1 + ppnRate / 100);
            ptVal.textContent = formatRupiah(priceBeforeTax);
            ptDiv.style.display = 'block';
        } else if (ptDiv) {
            ptDiv.style.display = 'none';
        }

        // Info subtotal sebelum pajak (mode after_tax)
        const stDiv = document.getElementById(`subtotal-before-tax-${index}`);
        const stVal = document.getElementById(`subtotal-before-tax-value-${index}`);
        
        let subtotalQty = displayQty;
        
        if (taxType === 'after_tax' && stDiv && stVal) {
            const subtotalBeforeTax = (subtotalQty * price) / (1 + ppnRate / 100);
            stVal.textContent = formatRupiah(subtotalBeforeTax);
            stDiv.style.display = 'block';
        } else if (stDiv) {
            stDiv.style.display = 'none';
        }

        // Sync hidden fields yang dikirim ke backend (send raw display quantity, let controller handle conversion)
        const qHid = document.querySelector(`input[name="items[${index}][quantity]"]`);
        const pHid = document.querySelector(`input[name="items[${index}][unit_price]"]`);
        if (qHid) qHid.value = displayQty; 
        if (pHid) pHid.value = price;

        const stEl = document.getElementById(`subtotal-${index}`);
        if (stEl) stEl.value = formatRupiah(subtotalQty * price);

        calculateTotals();
    }

    /* ── TOTAL KESELURUHAN ────────────────────────────────────────────── */
    function calculateTotals() {
        let subtotal = 0;
        let subtotalAfterTax = 0;
        let subtotalBeforeTax = 0;
        let hasAfterTaxItems = false;

        document.querySelectorAll('.item-row').forEach(row => {
            const i = row.dataset.index;
            // Get the subtotal directly from the displayed subtotal field
            const subtotalText = document.getElementById(`subtotal-${i}`)?.value || '0';
            const subtotalRaw = subtotalText.replace(/[^0-9]/g, '');
            const item = parseFloat(subtotalRaw || 0);
            
            // Also get tax type for PPN calculation
            const taxType = document.querySelector(`select[name="items[${i}][tax_type]"]`)?.value || 'after_tax';
            
            subtotal += item;
            if (taxType === 'after_tax') {
                subtotalAfterTax += item;
                hasAfterTaxItems = true;
            } else subtotalBeforeTax += item;
        });

        const fobCost = parseFloat(document.getElementById('fob_cost').value || 0);
        const ppnRate = parseFloat(document.getElementById('ppn_rate').value || 0);
        const discRate = parseFloat(document.getElementById('discount_rate').value || 0);
        const downPayment = parseFloat(document.getElementById('down_payment').value || 0);

        // ── DPP & PPN ─────────────────────────────────────────────────────
        // Metode A: Diskon dari DPP Asli
        // 1. Hitung Total Harga Termasuk PPN (dari after_tax items) + Harga Sebelum PPN (dari before_tax items)
        const totalHargaTermasukPpn = subtotalBeforeTax + subtotalAfterTax;
        
        // 2. Hitung DPP Asli
        // Untuk before_tax items: sudah DPP, tidak perlu dibagi 1.11
        // Untuk after_tax items: perlu dibagi 1.11 untuk dapat DPP
        const dppAsli = subtotalBeforeTax + (subtotalAfterTax / (1 + (ppnRate / 100)));
        
        // 3. Hitung Diskon dari DPP Asli
        const discAmount = dppAsli * (discRate / 100);
        
        // 4. Hitung DPP Baru setelah diskon
        const dppBaru = dppAsli - discAmount;
        
        // 5. Hitung PPN: PPN = 11% x DPP Baru
        const ppnTotal = dppBaru * (ppnRate / 100);
        
        // 6. Total Barang = DPP Baru + PPN
        const totalBarang = dppBaru + ppnTotal;
        
        // 7. Total Keseluruhan = Total Barang + FOB
        const total = totalBarang + fobCost;

        // Untuk tampilan (jika diperlukan)
        const subtotalKotor = totalBarang;

        // ── Display helpers ───────────────────────────────────────────────
        const unitPriceBeforeTax = hasAfterTaxItems ? dppBaru : 0;
        const dppAsliDisplay = dppAsli; // DPP Asli untuk display

        let ppnDetail = '';
        if (hasAfterTaxItems) {
            ppnDetail = `DPP Baru: ${formatRupiah(dppBaru)} × ${ppnRate}% = ${formatRupiah(ppnTotal)}`;
        } else if (ppnTotal > 0) {
            ppnDetail = `DPP Baru: ${formatRupiah(dppBaru)} × ${ppnRate}% = ${formatRupiah(ppnTotal)}`;
        }

        // ── Visibility rows ───────────────────────────────────────────────
        document.getElementById('discount-row').style.display = discRate > 0 ? 'flex' : 'none';
        document.getElementById('ppn-row').style.display = (hasAfterTaxItems || ppnTotal > 0) ? 'flex' : 'none';
        document.getElementById('ppn-detail-row').style.display = (hasAfterTaxItems || ppnTotal > 0) ? 'flex' : 'none';

        // ── Update DOM ────────────────────────────────────────────────────
        document.getElementById('display-total-harga-termasuk-ppn').textContent = formatRupiah(totalHargaTermasukPpn);
        document.getElementById('display-dpp-asli').textContent = formatRupiah(dppAsli);
        document.getElementById('display-dpp-baru').textContent = formatRupiah(dppBaru);
        document.getElementById('display-ppn-rate').textContent = ppnRate;
        document.getElementById('display-discount-rate').textContent = discRate;
        document.getElementById('display-ppn').textContent = formatRupiah(ppnTotal);
        document.getElementById('display-ppn-detail').textContent = ppnDetail;
        document.getElementById('display-subtotal-plus-ppn').textContent = formatRupiah(totalBarang);
        document.getElementById('display-fob').textContent = formatRupiah(fobCost);
        document.getElementById('display-discount').textContent = '−' + formatRupiah(discAmount);
        document.getElementById('display-total').textContent = formatRupiah(total);

        const dpEl = document.getElementById('display-down-payment');
        if (dpEl) dpEl.textContent = formatRupiah(downPayment);
    }

    /* ── TOGGLE HELPERS ───────────────────────────────────────────────── */
    function toggleFobCost() {
        const type = document.getElementById('fob_type').value;
        const cont = document.getElementById('fob-cost-container');
        const input = document.getElementById('fob_cost');
        if (type === 'destination') {
            cont.style.display = 'none';
            input.value = 0;
            input.removeAttribute('required');
        } else {
            cont.style.display = 'block';
            input.setAttribute('required', 'required');
        }
        calculateTotals();
    }

    function toggleDueDate() {
        const method = document.getElementById('payment_method').value;
        const dueDateEl = document.getElementById('due_date');
        const dpCont = document.getElementById('down-payment-container');
        const dpInput = document.getElementById('down_payment');
        const dpSummary = document.getElementById('down-payment-summary');

        if (method === 'credit') {
            if (!dueDateEl.value) {
                const d = new Date();
                d.setDate(d.getDate() + 30);
                dueDateEl.value = d.toISOString().split('T')[0];
            }
            dpCont.style.display = 'block';
            dpSummary.style.display = 'flex';
            dpInput.removeAttribute('required');
        } else {
            dueDateEl.value = '';
            dpCont.style.display = 'none';
            dpSummary.style.display = 'none';
            dpInput.value = '0';
        }
        calculateTotals();
    }

    // (konversi)
    function getConversionFactor(fromUnit, toUnit) {
        const toGram = {
            'Kg': 1000,
            'KG': 1000,
            'Gram': 1,
            'GRAM': 1,
            'Milligram': 0.001,
            'MILLIGRAM': 0.001,
            'Liter': 1000,
            'LITER': 1000,
            'L': 1000,
            'Milliliter': 1,
            'MILLILITER': 1,
            'ML': 1,
            'Mililiter': 1,
            'MILILITER': 1,
        };

        if (!toGram[fromUnit] || !toGram[toUnit]) {
            return null;
        }

        // dari unit -> gram -> ke unit baru
        return toGram[fromUnit] / toGram[toUnit];
    }
</script>
@endsection
