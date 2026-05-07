<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            CorrectCoaSeeder::class,
            UnitSeeder::class,
            // MinimalCoaSeeder::class, // Commented out karena sudah ada CorrectCoaSeeder
            // RawMaterialSeeder::class,
            // ProductWithMaterialsSeeder::class,
            // EmployeeSeeder::class,
            JavaRegionsSeeder::class,
        ]);
    }
}
