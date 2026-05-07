<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Log;

class CoaAutoGenerateService
{
    /**
     * Auto-generate COA untuk bahan baku (BBB)
     * Format: BBB-[Nama Bahan Baku] dengan kode 51X (dimulai dari 5101)
     */
    public static function createCoaForRawMaterial($rawMaterial): ?int
    {
        return self::generateCoa('51', 5101, 'BBB-' . $rawMaterial->name, 'expense', 'Beban', 'Akun biaya bahan baku untuk ' . $rawMaterial->name);
    }

    /**
     * Auto-generate COA untuk persediaan bahan baku
     * Format: Pers. Bahan Baku [Nama] dengan kode 1104XX (dimulai dari 110401)
     */
    public static function createCoaForRawMaterialInventory($rawMaterial): ?int
    {
        return self::generateCoa('1104', 110401, 'Pers. Bahan Baku ' . $rawMaterial->name, 'asset', 'Aset', 'Persediaan bahan baku ' . $rawMaterial->name);
    }

    /**
     * Auto-generate COA untuk karyawan BTKL
     * Format: BTKL-[Posisi] dengan kode 52X (dimulai dari 5201)
     * Contoh: 5201 - BTKL-Chef
     */
    public static function createCoaForBtklEmployee($employee): ?int
    {
        return self::generateCoa('52', 5201, 'BTKL-' . $employee->position, 'expense', 'Beban', 'Biaya tenaga kerja langsung untuk ' . $employee->position);
    }

    /**
     * Auto-generate COA untuk BDP - BTKL spesifik karyawan
     * Format: BDP - BTKL [Posisi] dengan kode 1106021, 1106022, dst (sub-akun dari 110602)
     * Contoh: 1106021 - BDP - BTKL Chef
     */
    public static function createCoaForBdpBtklEmployee($employee): ?int
    {
        return self::generateCoa('110602', 1106021, 'BDP - BTKL ' . $employee->position, 'asset', 'Aset', 'Barang dalam proses - biaya tenaga kerja langsung untuk ' . $employee->position);
    }

    /**
     * Auto-generate COA untuk karyawan BTKTL dengan posisi spesifik
     * Format: BOP BTKTL - [Posisi] dengan kode 54X (dimulai dari 5401)
     * Contoh: 5401 - BOP BTKTL - Supervisor
     */
    public static function createCoaForBtktlEmployee($employee): ?int
    {
        return self::generateCoa('54', 5401, 'BOP BTKTL - ' . $employee->position, 'expense', 'Beban', 'Biaya overhead pabrik tenaga kerja tidak langsung untuk ' . $employee->position);
    }

    /**
     * Auto-generate COA untuk persediaan barang jadi per produk
     * Format: Pers. Barang Jadi - [Nama Produk] dengan kode 1105XX (dimulai dari 110501)
     * Contoh: 110501 - Pers. Barang Jadi - Ayam Goreng
     */
    public static function createCoaForProductInventory($product): ?int
    {
        return self::generateCoa('1105', 110501, 'Pers. Barang Jadi - ' . $product->name, 'asset', 'Aset', 'Persediaan barang jadi untuk produk ' . $product->name);
    }

    /**
     * Auto-generate COA untuk retur penjualan per produk
     * Format: Retur Penjualan - [Nama Produk] dengan kode 4201XX (dimulai dari 420101)
     * Sub-akun dari 4201 (Retur Penjualan)
     */
    public static function createCoaForProductSalesReturn($product): ?int
    {
        return self::generateCoa('4201', 420101, 'Retur Penjualan - ' . $product->name, 'revenue', 'Pendapatan', 'Retur penjualan untuk produk ' . $product->name);
    }

    /**
     * Auto-generate COA untuk penjualan per produk
     * Format: Penjualan - [Nama Produk] dengan kode 41X (dimulai dari 4101)
     */
    public static function createCoaForProductSales($product): ?int
    {
        return self::generateCoa('41', 4101, 'Penjualan - ' . $product->name, 'revenue', 'Pendapatan', 'Pendapatan penjualan untuk ' . $product->name);
    }

    /**
     * Auto-generate COA untuk biaya overhead bahan penolong (BOP BP)
     * Format: BOP BP - [Nama Bahan Penolong] dengan kode 55X (dimulai dari 5501)
     */
    public static function createCoaForAuxiliaryMaterialExpense($auxiliaryMaterial): ?int
    {
        return self::generateCoa('55', 5501, 'BOP BP - ' . $auxiliaryMaterial->name, 'expense', 'Beban', 'Biaya overhead bahan penolong untuk ' . $auxiliaryMaterial->name);
    }

    /**
     * Auto-generate COA untuk persediaan bahan penolong
     * Format: Pers. Bahan Penolong - [Nama] dengan kode 1107X (dimulai dari 110701)
     */
    public static function createCoaForAuxiliaryMaterialInventory($auxiliaryMaterial): ?int
    {
        return self::generateCoa('1107', 110701, 'Pers. Bahan Penolong - ' . $auxiliaryMaterial->name, 'asset', 'Aset', 'Persediaan bahan penolong ' . $auxiliaryMaterial->name);
    }

    /**
     * Helper method untuk generate COA dengan pattern yang sama.
     *
     * Kode akun bersifat unik secara global di seluruh database (dicari tanpa scope company)
     * agar tidak ada duplikat kode antar perusahaan.
     * Namun pengecekan nama akun dilakukan PER PERUSAHAAN (dengan global scope),
     * sehingga tiap perusahaan bisa punya akun dengan nama yang sama dan dimulai
     * dari kode awal prefix-nya sendiri (bukan melanjutkan kode perusahaan lain).
     */
    private static function generateCoa($prefix, $startNumber, $accountName, $accountType, $accountGroup, $description): ?int
    {
        // Cek apakah COA dengan nama yang sama sudah ada untuk perusahaan ini (WITH scope)
        // Ini memastikan tiap perusahaan bisa punya akun "Pers. Barang Jadi - X" sendiri
        $existingCoa = ChartOfAccount::where('account_name', $accountName)->first();
        if ($existingCoa) {
            return $existingCoa->id;
        }

        // Cari kode COA terakhir yang dimulai dengan prefix UNTUK PERUSAHAAN INI (dengan scope)
        // Agar tiap perusahaan mulai dari kode awal prefix-nya sendiri (misal 110501)
        $lastCoaCode = ChartOfAccount::where('code', 'like', $prefix . '%')
            ->where('code', 'regexp', '^' . $prefix . '[0-9]+$')
            ->orderByRaw('CAST(code AS UNSIGNED) DESC')
            ->value('code');

        // Generate kode baru: ambil angka buntutnya saja dari kode terakhir lalu tambah 1
        $nextCode = (string)$startNumber;
        if ($lastCoaCode) {
            $suffixStr = substr($lastCoaCode, strlen($prefix));
            if (ctype_digit($suffixStr)) {
                $nextSuffix = (int)$suffixStr + 1;
                // Gunakan padding minimal 2 digit atau sepanjang suffix sebelumnya
                $nextCode = $prefix . str_pad((string)$nextSuffix, max(2, strlen($suffixStr)), '0', STR_PAD_LEFT);
            }
        }

        // Pastikan kode tidak bentrok untuk PERUSAHAAN INI (mengikuti scope)
        $finalNextNumber = $nextCode;
        while (ChartOfAccount::where('code', $finalNextNumber)->exists()) {
            $suffixValue = (int)substr($finalNextNumber, strlen($prefix)) + 1;
            $finalNextNumber = $prefix . str_pad((string)$suffixValue, 2, '0', STR_PAD_LEFT);
        }

        $coaCode = $finalNextNumber;

        try {
            $coa = ChartOfAccount::create([
                'code'                    => $coaCode,
                'account_name'            => $accountName,
                'account_type'            => $accountType,
                'account_group_name'      => $accountGroup,
                'description'             => $description,
                'opening_balance'         => 0,
                'normal_balance_position' => self::getNormalBalance($accountType),
                'is_active'               => true,
                'parent_code'             => $prefix,
            ]);

            Log::info('COA created: ' . $coaCode . ' - ' . $accountName);
            return $coa->id;
        } catch (\Exception $e) {
            Log::error('Failed to create COA: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get normal balance position based on account type
     */
    private static function getNormalBalance($type): string
    {
        return match($type) {
            'asset', 'expense' => 'debit',
            'liability', 'equity', 'revenue' => 'credit',
            default => 'debit'
        };
    }
}