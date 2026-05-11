<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CorrectCoaSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ASSET
            ['code' => '11',   'name' => 'ASSET',                              'type' => 'asset',     'group' => 'Aset',       'parent' => null, 'is_header' => true],
            ['code' => '1101',  'name' => 'Kas Bank',                           'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1102',  'name' => 'Kas',                                'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1103',  'name' => 'Kas Kecil',                          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1104',  'name' => 'Pers. Bahan Baku',                   'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1105',  'name' => 'Pers. Barang Jadi',                  'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1106',  'name' => 'Pers. Barang dalam Proses',          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '110601',  'name' => 'BDP - BBB',          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '110602',  'name' => 'BDP - BTKL',          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '110603',  'name' => 'BDP - BOP',          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1107',  'name' => 'Pers. Bahan Penolong',               'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1108',  'name' => 'Piutang',                            'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1109',  'name' => 'Peralatan',                          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1110', 'name' => 'Akumulasi Penyusutan Peralatan',     'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1111', 'name' => 'Gedung',                             'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1112', 'name' => 'Akumulasi Penyusutan Gedung',        'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1113', 'name' => 'Mesin',                              'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1114', 'name' => 'Akumulasi Penyusutan Mesin',         'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1115', 'name' => 'Kendaraan',                          'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1116', 'name' => 'Akumulasi Penyusutan Kendaraan',     'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1117', 'name' => 'PPN Masukkan',                       'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            ['code' => '1118', 'name' => 'Uang Muka Pembelian',                'type' => 'asset',     'group' => 'Aset',       'parent' => '11'],
            
            // HUTANG
            ['code' => '21',  'name' => 'HUTANG',       'type' => 'liability', 'group' => 'Kewajiban', 'parent' => null, 'is_header' => true],
            ['code' => '2101', 'name' => 'Hutang Usaha', 'type' => 'liability', 'group' => 'Kewajiban', 'parent' => '21'],
            ['code' => '2102', 'name' => 'Hutang Gaji',  'type' => 'liability', 'group' => 'Kewajiban', 'parent' => '21'],
            ['code' => '2103', 'name' => 'Hutang Lainnya','type' => 'liability','group' => 'Kewajiban', 'parent' => '21'],
            ['code' => '22',  'name' => 'PPN Keluaran',       'type' => 'liability', 'group' => 'Kewajiban', 'parent' => null, 'is_header' => true],
            // MODAL
            ['code' => '31',  'name' => 'MODAL',        'type' => 'equity', 'group' => 'Ekuitas', 'parent' => null, 'is_header' => true],
            ['code' => '3101', 'name' => 'Modal Usaha',  'type' => 'equity', 'group' => 'Ekuitas', 'parent' => '31'],
            ['code' => '3102', 'name' => 'Prive',        'type' => 'equity', 'group' => 'Ekuitas', 'parent' => '31'],

            // PENJUALAN
            ['code' => '41', 'name' => 'PENJUALAN', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => null, 'is_header' => true],
            ['code' => '42', 'name' => 'Retur dan Potongan Penjualan', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => null, 'is_header' => true],
            ['code' => '4201', 'name' => 'Retur Penjualan', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => '41'],
            ['code' => '4202', 'name' => 'Diskon Penjualan', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => '41'],
            ['code' => '43', 'name' => 'Pendapatan Lainnya', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => null, 'is_header' => true],
            ['code' => '4301', 'name' => 'Beban Transport Penjualan', 'type' => 'revenue', 'group' => 'Pendapatan', 'parent' => '41'],

            // BIAYA BAHAN BAKU
            ['code' => '51', 'name' => 'BBB-Biaya Bahan Baku', 'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => true],

            // BIAYA TENAGA KERJA LANGSUNG
            ['code' => '52', 'name' => 'BTKL-Biaya Tenaga Kerja Langsung', 'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => true],

            // BIAYA OVERHEAD PABRIK
            ['code' => '53',   'name' => 'BOP-Biaya Overhead Pabrik',              'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => true],
            ['code' => '5301', 'name' => 'BOP-Iklan Produksi',                     'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5302', 'name' => 'BOP-Listrik Produksi',                   'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5303', 'name' => 'BOP-Air Produksi',                       'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5304', 'name' => 'BOP-Telepon & Internet Produksi',        'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5305', 'name' => 'BOP-Transport Produksi',                 'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5306', 'name' => 'BOP-Konsumsi Karyawan Produksi',         'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5307', 'name' => 'BOP-Asuransi Produksi',                  'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5308', 'name' => 'BOP-Pemeliharaan & Perawatan Produksi',  'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5309', 'name' => 'BOP-Perlengkapan Produksi',              'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5310', 'name' => 'BOP-Sewa Produksi',                      'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5311', 'name' => 'BOP-Penyusutan Produksi',                'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5312', 'name' => 'BOP-Pajak Produksi',                     'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
             ['code' => '5313', 'name' => 'BOP-Gas',                     'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5314', 'name' => 'BOP-Penyusutan Peralatan',               'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5315', 'name' => 'BOP-Penyusutan Mesin',                   'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5316', 'name' => 'BOP-Penyusutan Kendaraan',               'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5317', 'name' => 'BOP-Penyusutan Bangunan',               'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5318', 'name' => 'BOP-Dibebankan',        'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],
            ['code' => '5319', 'name' => 'BOP-Lainnya',                            'type' => 'expense', 'group' => 'Beban', 'parent' => '53'],

            // BIAYA TENAGA KERJA TIDAK LANGSUNG
            ['code' => '54', 'name' => 'BOP BTKTL-Biaya Tenaga Kerja Tidak Langsung', 'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => true],
            
            // BAHAN PENOLONG
            ['code' => '55', 'name' => 'BOP BP-Bahan Penolong', 'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => true],
            
            // BEBAN
            ['code' => '56', 'name' => 'Beban Transport Pembelian', 'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => true],
            ['code' => '57', 'name' => 'Beban Gaji dan Upah ', 'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => true],
             ['code' => '58', 'name' => 'Diskon Pembelian', 'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => true],
              ['code' => '59', 'name' => 'Harga Pokok Penjualan', 'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => true],
              ['code' => '5999', 'name' => 'Kerugian Produk Cacat', 'type' => 'expense', 'group' => 'Beban', 'parent' => null, 'is_header' => false],
        ];

        foreach ($accounts as $account) {
            $data = [
                'code'                    => $account['code'],
                'account_name'            => $account['name'],
                'account_type'            => $account['type'],
                'account_group_name'      => $account['group'],
                'description'             => 'Akun ' . $account['name'],
                'opening_balance'         => 0,
                'normal_balance_position' => $this->getNormalBalance($account['type']),
                'is_active'               => true,
                'parent_code'             => $account['parent'] ?? null,
                'is_header'               => $account['is_header'] ?? false,
            ];

            try {
                // Cek apakah COA dengan kode ini sudah ada
                $existingCoa = ChartOfAccount::where('code', $account['code'])->first();
                
                if ($existingCoa) {
                    // Jika sudah ada, skip dan beri info
                    $this->command->info("⏭️  Skipped (already exists): {$account['code']} - {$account['name']}");
                } else {
                    // Jika belum ada, buat baru
                    ChartOfAccount::create($data);
                    $this->command->info("✅ Created: {$account['code']} - {$account['name']}");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Failed {$account['code']}: " . $e->getMessage());
            }
        }

        $this->command->info("\nTotal COA: " . ChartOfAccount::count());
    }

    private function getNormalBalance(string $type): string
    {
        return match($type) {
            'asset', 'expense' => 'debit',
            default            => 'credit',
        };
    }
}
