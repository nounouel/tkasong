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
        // User::factory(10)->create();

        User::firstOrCreate(
            ['username' => 'testuser'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'), // default password for testing
            ]
        );

        $this->call([
            BarangSeeder::class,
            TraningSeeder::class,
            TransaksiSeeder::class,
            PenjualanAgregatSeeder::class,
        ]);
    }
}
