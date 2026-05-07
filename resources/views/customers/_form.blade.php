@if ($errors->any())
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
    <ul class="list-disc list-inside">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(session('success'))
<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
    {{ session('success') }}
</div>
@endif

<div class="mb-4">
    <label for="code" class="block text-sm font-medium text-gray-700">Kode Pelanggan</label>
    <input type="text" name="code" id="code" 
           value="{{ old('code', $customer->code ?? '') }}"
           class="mt-1 block w-full rounded-md border-gray-300 {{ isset($customer) ? 'bg-gray-100' : '' }} shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
           placeholder="Kosongkan untuk generate otomatis"
           {{ isset($customer) ? 'readonly' : '' }}>
</div>

<div class="mb-4">
    <label for="name" class="block text-sm font-medium text-gray-700">Nama <span class="text-red-500">*</span></label>
    <input type="text" name="name" id="name" required
           value="{{ old('name', $customer->name ?? '') }}"
           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div>
        <label for="phone" class="block text-sm font-medium text-gray-700">Telepon <span class="text-red-500">*</span></label>
        <input type="text" name="phone" id="phone" required
               value="{{ old('phone', $customer->phone ?? '') }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    </div>
</div>

<div class="mb-4">
    <label for="address" class="block text-sm font-medium text-gray-700">Alamat</label>
    <textarea name="address" id="address" rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('address', $customer->address ?? '') }}</textarea>
</div>