@extends('layouts.app')

@section('title', 'Edit Produk')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900">Edit Produk</h1>
        <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-md shadow-sm hover:bg-gray-100">
            Kembali
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Gambar Produk -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gambar Produk</label>
                @if($product->has_image)
                    <div class="mb-4">
                        <img src="{{ route('products.image', $product) }}?t={{ $product->updated_at->timestamp }}" alt="{{ $product->name }}" class="h-32 w-32 object-cover rounded-md border border-gray-200">
                        <p class="mt-1 text-sm text-gray-500">Gambar saat ini</p>
                    </div>
                @endif
                <div class="flex items-center space-x-2">
                    <input type="file" name="product_image" id="product_image" accept="image/png,image/jpeg,image/jpg"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    @if($product->has_image)
                        <button type="button" onclick="if(confirm('Hapus gambar produk?')) { document.getElementById('delete-image-form').submit(); }" 
                            class="mt-1 inline-flex items-center px-3 py-2 border border-red-300 text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Hapus Gambar
                        </button>
                    @endif
                </div>
                <p class="mt-1 text-xs text-gray-500">Format: PNG atau JPG, maksimal 10MB. Kosongkan jika tidak ingin mengubah.</p>
                @error('product_image')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="product_code" class="block text-sm font-medium text-gray-700">Kode Produk</label>
                    <input type="text" id="product_code" value="{{ $product->code }}" readonly
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                    <p class="mt-1 text-sm text-gray-500">Kode produk tidak dapat diubah.</p>
                </div>

                <div>
                    <label for="product_name" class="block text-sm font-medium text-gray-700">Nama Produk *</label>
                    <input type="text" name="product_name" id="product_name" value="{{ $product->name }}" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('product_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="barcode" class="block text-sm font-medium text-gray-700">Barcode</label>
                        @if($product->barcode)
                            <button type="button" 
                                onclick="event.preventDefault(); document.getElementById('generate-barcode-form').submit();"
                                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                Generate Baru
                            </button>
                        @endif
                    </div>
                    <div class="mt-1 flex items-start space-x-3">
                        <div class="flex-grow">
                            <input type="text" name="barcode" id="barcode" value="{{ old('barcode', $product->barcode) }}" readonly
                                class="block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    @if($product->barcode_image)
                        <div class="mt-2 p-2 bg-gray-50 rounded border border-gray-200 inline-block">
                            <img src="{{ $product->barcode_image }}" alt="Barcode {{ $product->barcode }}" class="h-12">
                            <p class="text-[10px] text-gray-400 font-mono text-center mt-1">{{ $product->barcode }}</p>
                        </div>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">Barcode akan digenerate otomatis jika dikosongkan. Format: PROD0000000001</p>
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
                            value="{{ number_format((int)$product->price, 0, ',', '.') }}"
                            class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="0" autocomplete="off"
                            oninput="formatHargaJual(this)">
                    </div>
                    <input type="hidden" name="selling_price" id="selling_price" value="{{ $product->price }}">
                    @error('selling_price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea name="description" id="description" rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $product->description }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Update
                </button>
            </div>
        </form>
        
        <form id="generate-barcode-form" action="{{ route('products.generateBarcode', $product) }}" method="POST" style="display: none;">
            @csrf
        </form>
        
        <form id="delete-image-form" action="{{ route('products.deleteImage', $product) }}" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
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
