@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Penggajian</h2>
            <div class="space-x-2 flex flex-wrap gap-2">
                @if(session('success'))
                    <span class="text-green-600 text-sm self-center">{{ session('success') }}</span>
                @endif
                @if(session('error'))
                    <span class="text-red-600 text-sm self-center">{{ session('error') }}</span>
                @endif

                {{-- 🔵 Tombol Pengakuan Gaji --}}
                @if(!$item->journal_status)
                    <form action="{{ route('penggajian.recognize', $item->id_gaji) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Buat jurnal pengakuan gaji?\nDr Beban Gaji / Cr Utang Gaji')"
                            class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            🔵 Pengakuan Gaji
                        </button>
                    </form>
                @elseif($item->journal_status === 'recognized')
                    <span class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-800 rounded-md text-sm">✅ Sudah Diakui</span>

                    {{-- 🔴 Tombol Distribusi Gaji --}}
                    <form action="{{ route('penggajian.distribute', $item->id_gaji) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Distribusi gaji ke akun biaya?\nDr BDP-BTKL/BOP-BTKTL/Beban Adm / Cr Beban Gaji')"
                            class="inline-flex items-center px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm">
                            🔴 Distribusi Gaji
                        </button>
                    </form>
                @elseif($item->journal_status === 'distributed')
                    <span class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-800 rounded-md text-sm">✅ Sudah Diakui</span>
                    <span class="inline-flex items-center px-3 py-2 bg-red-100 text-red-800 rounded-md text-sm">✅ Sudah Didistribusi</span>
                @endif

                {{-- 🟢 Tombol Bayar --}}
                @if($item->status == 1)
                    <a href="{{ route('penggajian.slip', $item->id_gaji) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                        Cetak Slip Gaji
                    </a>
                @elseif($item->journal_status === 'distributed')
                    <form action="{{ route('penggajian.pay', $item->id_gaji) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Proses pembayaran gaji?\nDr Utang Gaji / Cr Kas')"
                            class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                            🟢 Bayar Sekarang
                        </button>
                    </form>
                @endif

                <a href="{{ route('penggajian.edit', $item->id_gaji) }}" class="px-3 py-2 bg-amber-500 text-white rounded-md hover:bg-amber-600 text-sm">Edit</a>
                <a href="{{ route('penggajian.index') }}" class="px-3 py-2 border rounded-md hover:bg-gray-50 text-sm">Kembali</a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 space-y-6">
                <div>
                    <h3 class="font-semibold mb-2">Informasi Umum</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500">No Transaksi</div>
                            <div class="font-medium">{{ $item->no_transaksi_gaji }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Tanggal</div>
                            <div class="font-medium">{{ optional($item->tanggal_penggajian)->format('d M Y') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Karyawan</div>
                            <div class="font-medium">{{ $item->employee->name ?? '-' }} ({{ $item->employee->employee_number ?? '-' }})</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Status Pembayaran</div>
                            <div class="font-medium">
                                @if($item->status == 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Belum Bayar
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Selesai
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Komponen Gaji</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="text-sm text-gray-500">Tarif</div>
                            <div class="font-medium">Rp {{ number_format((float) $item->tarif, 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Bonus</div>
                            <div class="font-medium">Rp {{ number_format((float) $item->bonus, 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Tunjangan Makan</div>
                            <div class="font-medium">Rp {{ number_format((float) $item->tunjangan_makan, 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Tunjangan Jabatan</div>
                            <div class="font-medium">Rp {{ number_format((float) $item->tunjangan_jabatan, 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Tunjangan Transportasi</div>
                            <div class="font-medium">Rp {{ number_format((float) ($item->tunjangan_lainnya ?? 0), 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Lembur</div>
                            <div class="font-medium">Rp {{ number_format((float) $item->lembur, 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Potongan</div>
                            <div class="font-medium">Rp {{ number_format((float) $item->potongan_gaji, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold mb-2">Total</h3>
                    <div class="text-2xl font-bold">Rp {{ number_format((float) $item->total_gaji_bersih, 0, ',', '.') }}</div>
                </div>

                @if ($item->detail_potongan)
                    <div>
                        <h3 class="font-semibold mb-2">Detail Potongan</h3>
                        <div class="whitespace-pre-wrap text-gray-700">{{ $item->detail_potongan }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
