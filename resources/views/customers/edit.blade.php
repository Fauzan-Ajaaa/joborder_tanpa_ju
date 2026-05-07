@extends('layouts.app')

@section('title', 'Edit Pelanggan')

@section('content')
<div class="p-4 sm:ml-64">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <h2 class="text-2xl font-bold mb-4">Edit Pelanggan: {{ $customer->name }}</h2>
        
        <form action="{{ route('customers.update', $customer->id) }}" method="POST" class="max-w-2xl">
            @csrf
            @method('PUT')
            @include('customers._form')
            
            <div class="flex justify-end space-x-2 mt-6">
                <a href="{{ route('customers.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Perbarui
                </button>
            </div>
        </form>
    </div>
</div>
@endsection