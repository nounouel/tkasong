<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TraningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Path ke file SQL dummy
        $sqlPath = database_path('seeders/dummy_traning.sql');
        
        if (File::exists($sqlPath)) {
            $this->command->info('Memulai import data dummy dari file SQL...');
            
            $sqlContent = File::get($sqlPath);
            
            // Split queries by semicolon to execute individually
            $queries = array_filter(
                array_map('trim', explode(';', $sqlContent)),
                function ($query) {
                    return !empty($query);
                }
            );
            
            $totalQueries = count($queries);
            $this->command->info("Ditemukan {$totalQueries} perintah SQL. Mulai mengimpor...");
            
            $bar = $this->command->getOutput()->createProgressBar($totalQueries);
            $bar->start();
            
            foreach ($queries as $query) {
                // Semicolon was removed during explode, so add it back
                DB::unprepared($query . ';');
                $bar->advance();
            }
            
            $bar->finish();
            $this->command->newLine();
            $this->command->info('Data barang dan histori traning berhasil di-seed!');
        } else {
            $this->command->error('File SQL tidak ditemukan di: ' . $sqlPath);
        }
    }
}
