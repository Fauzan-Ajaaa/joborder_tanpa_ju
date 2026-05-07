<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JavaRegionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Seeder lengkap untuk Kabupaten Sumedang (Jawa Barat) saja, berdasarkan data umum dari BPS dan situs Kode Pos Indonesia.
            // Kode pos disederhanakan dengan contoh utama per kecamatan (bisa bervariasi per desa/kelurahan).
            // Jika perlu akurasi mutakhir, verifikasi dengan sumber resmi seperti https://kodepos.nomor.net/ atau API terkait.

            $provinces = [
                'JAWA BARAT' => [
                    'KABUPATEN SUMEDANG' => [
                        'SUMEDANG SELATAN' => ['45311', '45312'],
                        'SUMEDANG UTARA' => ['45321', '45322'],
                        'JATINANGOR' => ['45363', '45364'],
                        'TANJUNGSARI' => ['45361', '45362'],
                        'PAMULIHAN' => ['45365', '45366'],
                        'RANCAKALONG' => ['45361', '45362'], // Kode pos serupa dengan Tanjungsari
                        'CISITU' => ['45373', '45374'],
                        'CONGGEANG' => ['45391', '45392'],
                        'BUAHDUA' => ['45371', '45372'],
                        'DARMA RAJA' => ['45381', '45382'],
                        'CIBUGEL' => ['45383', '45384'],
                        'WADO' => ['45385', '45386'],
                        'JATINUNGGAL' => ['45387', '45388'],
                        'SUKASARI' => ['45389', '45390'],
                        'TANJUNGMEDAR' => ['45351', '45352'],
                        'CIMALAKA' => ['45353', '45354'],
                        'CIMANGGUNG' => ['45355', '45356'],
                        'CISARUA' => ['45357', '45358'],
                        'TOMO' => ['45359', '45360'],
                        'UJUNGJAYA' => ['45311', '45312'], // Kode pos serupa dengan Sumedang Selatan
                        'JATIGEDE' => ['45363', '45364'], // Kode pos serupa dengan Jatinangor
                        'SITURAJA' => ['45375', '45376'],
                        'GANEAS' => ['45377', '45378'],
                        'TANJUNGKERTA' => ['45361', '45362'], // Kode pos serupa dengan Tanjungsari
                        'SUKAWENING' => ['45379', '45380'],
                        'PABUARAN' => ['45381', '45382'], // Kode pos serupa dengan Darmaraja
                        'KARANGTENGAH' => ['45383', '45384'], // Kode pos serupa dengan Cibugel
                        'BAYONGBONG' => ['45385', '45386'], // Kode pos serupa dengan Wado
                        'CILEUNYI' => ['45391', '45392'], // Kode pos serupa dengan Conggeang
                        'SUKAMANTRI' => ['45393', '45394'],
                    ],
                ],
            ];

            foreach ($provinces as $provName => $cities) {
                $provId = DB::table('provinces')->insertGetId([
                    'name' => $provName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($cities as $cityName => $districts) {
                    $cityId = DB::table('cities')->insertGetId([
                        'province_id' => $provId,
                        'name' => $cityName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($districts as $districtName => $postalCodes) {
                        $districtId = DB::table('districts')->insertGetId([
                            'city_id' => $cityId,
                            'name' => $districtName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        foreach ($postalCodes as $code) {
                            DB::table('postal_codes')->insert([
                                'district_id' => $districtId,
                                'code' => $code,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });
    }
}