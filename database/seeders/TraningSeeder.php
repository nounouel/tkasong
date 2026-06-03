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
            
            DB::table('traning')->truncate();

            foreach ($queries as $query) {
                $trimmed = trim($query);
                if (empty($trimmed)) {
                    $bar->advance();
                    continue;
                }
                $upper = strtoupper($trimmed);
                if (str_starts_with($upper, 'CREATE TABLE') || 
                    str_starts_with($upper, 'TRUNCATE TABLE') || 
                    str_starts_with($upper, 'SET FOREIGN_KEY_CHECKS') ||
                    str_starts_with($upper, 'INSERT INTO BARANG') || // Skip inserting into barang since it is already seeded
                    str_starts_with($upper, 'INSERT INTO `BARANG`')) {
                    $bar->advance();
                    continue;
                }
                // Semicolon was removed during explode, so add it back
                DB::unprepared($trimmed . ';');
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
