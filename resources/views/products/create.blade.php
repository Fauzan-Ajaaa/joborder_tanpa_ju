@extends('layouts.app')

@section('title', 'Tambah Produk')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">Tambah Produk</h1>
        <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-md shadow-sm hover:bg-gray-100">
            Kembali
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <!-- Gambar Produk -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Produk</label>
                <input type="file" name="product_image" id="product_image" accept="image/png,image/jpeg,image/jpg"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="mt-1 text-xs text-gray-500">Format: PNG atau JPG, maksimal 10MB. Opsional.</p>
                @error('product_image')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="product_code" class="block text-sm font-medium text-gray-700">Kode Produk</label>
                    <input type="text" id="product_code" value="{{ $nextCode ?? 'akan digenerate otomatis' }}" readonly
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                    <p class="mt-1 text-sm text-gray-500">Kode produk digenerate otomatis dan tidak dapat diubah.</p>
                </div>

                <div>
                    <label for="product_name" class="block text-sm font-medium text-gray-700">Nama Produk *</label>
                    <input type="text" name="product_name" id="product_name" value="{{ old('product_name') }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('product_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="barcode" class="block text-sm font-medium text-gray-700">Barcode</label>
                        <button type="button" 
                            onclick="document.getElementById('barcode').value = '{{ $nextBarcode }}'"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Generate Barcode
                        </button>
                    </div>
                    <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}" maxlength="50"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Kosongkan untuk generate otomatis">
                    <p class="mt-1 text-sm text-gray-500">Barcode akan digenerate otomatis jika dikosongkan. Contoh format: {{ $nextBarcode }}</p>
                    @error('barcode')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="selling_price_display" class="block text-sm font-medium text-gray-700">Harga Jual *</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Rp.</span>
                        </div>
                        <input type="text" id="selling_price_display"
                            value="{{ old('selling_price') ? number_format((int)old('selling_price'), 0, ',', '.') : '' }}"
                            class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="0" autocomplete="off"
                            oninput="formatHargaJual(this)">
                    </div>
                    <input type="hidden" name="selling_price" id="selling_price" value="{{ old('selling_price') }}">
                    @error('selling_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function fmtRp(input, hiddenId) {
    var raw = input.value.replace(/[^0-9]/g, '');
    var num = parseInt(raw || '0', 10);
    input.value = num > 0 ? num.toLocaleString('id-ID') : '';
    if (hiddenId) document.getElementById(hiddenId).value = raw || '';
}
function formatHargaJual(input) { fmtRp(input, 'selling_price'); }
</script>
@endpush
