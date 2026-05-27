<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('seeders/perhari.sql');
        if (!file_exists($filePath)) {
            $this->command->error("File perhari.sql tidak ditemukan!");
            return;
        }

        $this->command->info("Membaca file perhari.sql...");
        
        // Reading file line by line to reduce memory usage
        $handle = fopen($filePath, "r");
        if (!$handle) {
            $this->command->error("Gagal membuka file perhari.sql!");
            return;
        }

        $this->command->info("Mengekstrak data transaksi...");

        $masukData = [];
        $keluarData = [];
        $startDate = Carbon::create(2025, 4, 1);
        $totalParsedRows = 0;
        $nowStr = Carbon::now()->toDateTimeString();

        // Match pattern: (id_barang, tahun, bulan, persediaan_awal, pembelian, penjualan, persediaan_akhir, created_at, updated_at)
        // E.g.: (1, 2025, 4, 29, 0, 1, 28, '2026-05-22 16:11:07', '2026-05-22 16:11:07'),
        $pattern = '/\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,/';

        while (($line = fgets($handle)) !== false) {
            // We match the line against our pattern
            // A line could have multiple matches if formatted differently, but in our SQL file, each record is on its own line
            if (preg_match($pattern, $line, $match)) {
                $idBarang = (int) $match[1];
                $pembelian = (int) $match[5];
                $penjualan = (int) $match[6];

                // Hitung tanggal berdasarkan day index
                // Since each day has exactly 126 records (1 per goods)
                $dayIndex = (int) ($totalParsedRows / 126);
                $dateStr = $startDate->copy()->addDays($dayIndex)->toDateString();

                if ($pembelian > 0) {
                    $masukData[] = [
                        'id_barang' => $idBarang,
                        'tanggal' => $dateStr,
                        'jumlah' => $pembelian,
                        'keterangan' => null,
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                }

                if ($penjualan > 0) {
                    $keluarData[] = [
                        'id_barang' => $idBarang,
                        'tanggal' => $dateStr,
                        'jumlah' => $penjualan,
                        'keterangan' => null,
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                }

                $totalParsedRows++;
            }
        }
        fclose($handle);

        $this->command->info("Total data baris traning ditemukan & diproses: {$totalParsedRows}");

        $this->command->info("Memasukkan data ke tabel transaksi_masuk (" . count($masukData) . " baris)...");
        // Clear table first to avoid duplicate seeds
        DB::table('transaksi_masuk')->truncate();
        foreach (array_chunk($masukData, 1000) as $chunk) {
            DB::table('transaksi_masuk')->insert($chunk);
        }

        $this->command->info("Memasukkan data ke tabel transaksi_keluar (" . count($keluarData) . " baris)...");
        DB::table('transaksi_keluar')->truncate();
        foreach (array_chunk($keluarData, 1000) as $chunk) {
            DB::table('transaksi_keluar')->insert($chunk);
        }

        $this->command->info("Seeding transaksi masuk dan keluar selesai!");
    }
}
