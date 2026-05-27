<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PenjualanAgregatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("Memulai sinkronisasi data tabel penjualan_agregat...");

        // 1. Truncate table penjualan_agregat
        DB::table('penjualan_agregat')->truncate();

        // 2. Query aggregated data from transaksi_keluar
        $this->command->info("Mengambil dan mengelompokkan data dari transaksi_keluar...");
        $aggregates = DB::table('transaksi_keluar')
            ->select('id_barang', 'tanggal', DB::raw('SUM(jumlah) as total_terjual'))
            ->groupBy('id_barang', 'tanggal')
            ->orderBy('tanggal', 'asc')
            ->orderBy('id_barang', 'asc')
            ->get();

        $totalAggregates = count($aggregates);
        $this->command->info("Ditemukan {$totalAggregates} baris hasil agregasi harian.");

        // Convert stdClass object to array
        $insertData = [];
        $nowStr = Carbon::now()->toDateTimeString();

        foreach ($aggregates as $row) {
            $insertData[] = [
                'id_barang' => $row->id_barang,
                'tanggal' => $row->tanggal,
                'total_terjual' => (int) $row->total_terjual,
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ];
        }

        // 3. Insert in chunks of 1000
        $this->command->info("Memasukkan data ke tabel penjualan_agregat...");
        foreach (array_chunk($insertData, 1000) as $chunk) {
            DB::table('penjualan_agregat')->insert($chunk);
        }

        $this->command->info("Sinkronisasi data penjualan_agregat berhasil diselesaikan!");
    }
}
