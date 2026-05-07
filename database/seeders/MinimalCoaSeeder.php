<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class MinimalCoaSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // ASET
            ['code' => '111', 'account_name' => 'Kas', 'account_type' => 'asset', 'opening_balance' => 0],
	        ['code' => '112', 'account_name' => 'Bank', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '113', 'account_name' => 'Piutang Usaha', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '114', 'account_name' => 'Persediaan Bahan Baku', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '115', 'account_name' => 'Persediaan Barang Dalam Proses', 'account_type' => 'asset', 'opening_balance' => 0],
	        ['code' => '1151', 'account_name' => 'Barang Dalam Proses - Bahan Baku', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '1152', 'account_name' => 'Barang Dalam Proses - Tenaga Kerja Langsung', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '1153', 'account_name' => 'Barang Dalam Proses - Overhead Pabrik', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '116', 'account_name' => 'Persediaan Barang Jadi', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '117', 'account_name' => 'Persediaan Bahan Penolong', 'account_type' => 'asset', 'opening_balance' => 0],
	        ['code' => '118', 'account_name' => 'PPN Masukan', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '119', 'account_name' => 'Beban Dibayar di Muka', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '121', 'account_name' => 'Peralatan', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '122', 'account_name' => 'Akumulasi Penyusutan Peralatan', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '123', 'account_name' => 'Mesin', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '124', 'account_name' => 'Akumulasi Penyusutan Mesin', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '125', 'account_name' => 'Kendaraan', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '126', 'account_name' => 'Akumulasi Penyusutan Kendaraan', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '127', 'account_name' => 'Bangunan', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '128', 'account_name' => 'Akumulasi Penyusutan Bangunan', 'account_type' => 'asset', 'opening_balance' => 0],
            ['code' => '129', 'account_name' => 'Tanah', 'account_type' => 'asset', 'opening_balance' => 0],
           
            // KEWAJIBAN
            ['code' => '211', 'account_name' => 'Utang Usaha', 'account_type' => 'liability', 'opening_balance' => 0],
	        ['code' => '212', 'account_name' => 'Utang PPN', 'account_type' => 'liability', 'opening_balance' => 0],
            ['code' => '213', 'account_name' => 'Utang Gaji', 'account_type' => 'liability', 'opening_balance' => 0],
            ['code' => '214', 'account_name' => 'PPN Keluaran', 'account_type' => 'liability', 'opening_balance' => 0],
            ['code' => '221', 'account_name' => 'Utang Bank', 'account_type' => 'liability', 'opening_balance' => 0],
            
            // EKUITAS
            ['code' => '311', 'account_name' => 'Modal', 'account_type' => 'equity', 'opening_balance' => 0],
            ['code' => '312', 'account_name' => 'Prive', 'account_type' => 'equity', 'opening_balance' => 0],
            
            // PENDAPATAN
            ['code' => '411', 'account_name' => 'Penjualan', 'account_type' => 'revenue', 'opening_balance' => 0],
            ['code' => '412', 'account_name' => 'Retur Penjualan', 'account_type' => 'revenue', 'opening_balance' => 0],
            ['code' => '413', 'account_name' => 'Potongan Penjualan', 'account_type' => 'revenue', 'opening_balance' => 0],
            ['code' => '421', 'account_name' => 'Pendapatan Lain-lain', 'account_type' => 'revenue', 'opening_balance' => 0],
                        
            // HARGA POKOK PENJUALAN
            ['code' => '511', 'account_name' => 'Harga Pokok Penjualan', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '512', 'account_name' => 'Biaya Bahan Baku', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '513', 'account_name' => 'Biaya Tenaga Kerja Langsung', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '514', 'account_name' => 'Biaya Overhead Pabrik', 'account_type' => 'expense', 'opening_balance' => 0],
            
            // BEBAN OPERASIONAL
            ['code' => '521', 'account_name' => 'Beban Iklan', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '522', 'account_name' => 'Beban Gaji', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '523', 'account_name' => 'Beban Listrik', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '524', 'account_name' => 'Beban Air', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '525', 'account_name' => 'Beban Telepon & Internet', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '526', 'account_name' => 'Beban Transport Penjualan', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '527', 'account_name' => 'Beban Konsumsi Karyawan', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '528', 'account_name' => 'Beban Asuransi', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '529', 'account_name' => 'Beban Pemeliharaan & Perbaikan', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '530', 'account_name' => 'Beban Perlengkapan', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '531', 'account_name' => 'Beban Sewa', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '532', 'account_name' => 'Beban Penyusutan', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '533', 'account_name' => 'Beban Lain-lain', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '591', 'account_name' => 'Beban Pajak', 'account_type' => 'expense', 'opening_balance' => 0],
            ['code' => '592', 'account_name' => 'Overhead Pabrik dibebankan', 'account_type' => 'expense', 'opening_balance' => 0],
            ];

        foreach ($rows as $r) {
            ChartOfAccount::updateOrCreate(
                ['code' => $r['code']],
                [
                    'account_name' => $r['account_name'],
                    'account_type' => $r['account_type'],
                    'is_active' => true,
                    'opening_balance' => $r['opening_balance'],
                    'normal_balance_position' => match($r['account_type']) {
                        'asset', 'expense' => 'debit',
                        'liability', 'equity', 'revenue' => 'credit',
                        default => 'debit'
                    },
                    'account_group_name' => match($r['account_type']) {
                        'asset' => 'Aset',
                        'liability' => 'Kewajiban',
                        'equity' => 'Modal',
                        'revenue' => 'Pendapatan',
                        'expense' => 'Beban',
                        default => 'Lainnya'
                    }
                ]
            );
        }
    }
}