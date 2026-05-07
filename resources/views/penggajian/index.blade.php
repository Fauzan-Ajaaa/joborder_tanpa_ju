@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Penggajian</h2>
            <div class="space-x-2">
                <a href="{{ route('penggajian.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">Tambah</a>
                <a href="{{ route('penggajian.print.index', request()->only(['from','to','employee_id'])) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">Cetak Daftar</a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
            <div class="p-6">
                <form method="GET" action="{{ route('penggajian.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                    <div>
                        <label class="block text-sm text-gray-700">Dari</label>
                        <input type="date" name="from" value="{{ request('from') }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700">Sampai</label>
                        <input type="date" name="to" value="{{ request('to') }}" class="mt-1 block w-full border-gray-300 rounded-md" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm text-gray-700">Karyawan</label>
                        <select name="employee_id" class="mt-1 block w-full border-gray-300 rounded-md">
                            <option value="">Semua</option>
                            @foreach(($employees ?? []) as $e)
                                <option value="{{ $e->id }}" {{ (string)request('employee_id') === (string)$e->id ? 'selected' : '' }}>
                                    {{ $e->name }} ({{ $e->employee_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button class="px-4 py-2 bg-gray-800 text-white rounded-md">Filter</button>
                        <a href="{{ route('penggajian.index') }}" class="px-4 py-2 border rounded-md">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Transaksi</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Karyawan</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bersih</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($items as $row)
                                <tr>
                                    <td class="px-4 py-2">{{ optional($row->tanggal_penggajian)->format('d M Y') }}</td>
                                    <td class="px-4 py-2">{{ $row->no_transaksi_gaji }}</td>
                                    <td class="px-4 py-2">{{ $row->employee->name ?? '-' }}</td>
                                    <td class="px-4 py-2">
                                        @php $tipe = $row->employee->employee_type ?? null; @endphp
                                        @if($tipe === 'BTKL')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">BTKL</span>
                                        @elseif($tipe === 'BTKTL')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">BTKTL</span>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right">Rp {{ number_format((float) $row->total_gaji_bersih, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2">
                                        @if($row->status == 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Belum Bayar
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Selesai
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right space-x-2">
                                        @if($row->status == 0)
                                            <form action="{{ route('penggajian.pay', $row->id_gaji) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" onclick="return confirm('Proses pembayaran untuk {{ $row->employee->name ?? 'karyawan' }}?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                    Bayar
                                                </button>
                                            </form>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-500 bg-gray-100 cursor-not-allowed">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Sudah Dibayar
                                            </span>
                                        @endif

                                        @if($row->status == 1)
                                            <a href="{{ route('penggajian.slip', $row->id_gaji) }}" target="_blank" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                Cetak Slip
                                            </a>
                                        @endif

                                        <a href="{{ route('penggajian.show', $row->id_gaji) }}" class="text-indigo-600 hover:underline">Lihat</a>
                                        <a href="{{ route('penggajian.edit', $row->id_gaji) }}" class="text-amber-600 hover:underline">Edit</a>
                                        <form action="{{ route('penggajian.destroy', $row->id_gaji) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Hapus data?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">Belum ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
