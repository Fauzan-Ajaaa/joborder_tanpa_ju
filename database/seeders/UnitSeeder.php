<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        try {
            // Clear existing data
            DB::table('units')->delete();
            
            // Satuan untuk Manufaktur Job Order
            $units = [
                ['name' => 'EKOR', 'code' => 'EKOR'],
                ['name' => 'POTONG', 'code' => 'POTONG'],
                ['name' => 'BUNGKUS', 'code' => 'BUNGKUS'],
                ['name' => 'PIECES', 'code' => 'PCS'],
                ['name' => 'KILOGRAM', 'code' => 'KG'],
                ['name' => 'GRAM', 'code' => 'GRAM'],
                ['name' => 'SENDOK MAKAN', 'code' => 'SDM'],
                ['name' => 'SENDOK TEH', 'code' => 'SDT'],
                ['name' => 'MILILITER', 'code' => 'ML'],
                ['name' => 'LITER', 'code' => 'LITER'],
                ['name' => 'GALON', 'code' => 'GALON'],
            ];
            
            foreach ($units as $unit) {
                DB::table('units')->insert([
                    'name' => $unit['name'],
                    'code' => $unit['code'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            $this->command->info('✅ Seeder Unit berhasil dijalankan!');
            $this->command->info('📊 Total satuan: ' . count($units) . ' unit');
            $this->command->info('🎯 Satuan siap digunakan untuk sistem manufaktur');
        } catch (\Exception $e) {
            $this->command->error('❌ Error saat menjalankan seeder: ' . $e->getMessage());
            throw $e;
        }
    }
}
