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

        // Ambil stok_minimum dari tabel barang untuk menghitung ROP
        $barangStokMinimum = DB::table('barang')->pluck('stok_minimum', 'id')->all();

        foreach ($aggregates as $row) {
            $ss = $barangStokMinimum[$row->id_barang] ?? 10;
            $d = (int) $row->total_terjual;
            $L = 2; // Lead Time 2 hari dari supplier
            $rop = ($d * $L) + $ss;

            $insertData[] = [
                'id_barang' => $row->id_barang,
                'tanggal' => $row->tanggal,
                'total_terjual' => $d,
                'reorder_point' => $rop,
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ];
        }

        // 3. Insert in chunks of 1000
        $this->command->info("Memasukkan data ke tabel penjualan_agregat...");
        foreach (array_chunk($insertData, 1000) as $chunk) {
            DB::table('penjualan_agregat')->insert($chunk);
        }

        // 4. Populate stok_terakhir and rata_rata_penjualan_perhari using ultra-fast in-memory PHP calculations
        $this->command->info("Menghitung stok_terakhir dan rata_rata_penjualan_perhari...");
        
        $this->command->info("- Membaca data transaksi masuk...");
        $masukByBarang = [];
        $tmRows = DB::table('transaksi_masuk')->orderBy('tanggal', 'asc')->get();
        foreach ($tmRows as $row) {
            $masukByBarang[$row->id_barang][] = $row;
        }

        $this->command->info("- Membaca data transaksi keluar...");
        $keluarByBarang = [];
        $tkRows = DB::table('transaksi_keluar')->orderBy('tanggal', 'asc')->get();
        foreach ($tkRows as $row) {
            $keluarByBarang[$row->id_barang][] = $row;
        }

        $this->command->info("- Membaca data penjualan agregat...");
        $agregatesByBarang = [];
        $paRows = DB::table('penjualan_agregat')->orderBy('tanggal', 'asc')->get();
        foreach ($paRows as $row) {
            $agregatesByBarang[$row->id_barang][] = $row;
        }

        $this->command->info("- Memproses perhitungan...");
        $updateRows = [];
        foreach ($agregatesByBarang as $idBarang => $rows) {
            $masukList = $masukByBarang[$idBarang] ?? [];
            $keluarList = $keluarByBarang[$idBarang] ?? [];
            
            $masukCount = count($masukList);
            $keluarCount = count($keluarList);
            
            $masukIdx = 0;
            $keluarIdx = 0;
            
            $totalMasuk = 0;
            $totalKeluar = 0;
            
            $runningTotalTerjual = 0;
            $firstDate = null;
            
            foreach ($rows as $pa) {
                // Accumulate masuk up to pa->tanggal
                while ($masukIdx < $masukCount && $masukList[$masukIdx]->tanggal <= $pa->tanggal) {
                    $totalMasuk += $masukList[$masukIdx]->jumlah;
                    $masukIdx++;
                }
                
                // Accumulate keluar up to pa->tanggal
                while ($keluarIdx < $keluarCount && $keluarList[$keluarIdx]->tanggal <= $pa->tanggal) {
                    $totalKeluar += $keluarList[$keluarIdx]->jumlah;
                    $keluarIdx++;
                }
                
                $stokTerakhir = $totalMasuk - $totalKeluar;
                
                if ($firstDate === null) {
                    $firstDate = $pa->tanggal;
                }
                
                $runningTotalTerjual += $pa->total_terjual;
                $daysSpan = \Carbon\Carbon::parse($firstDate)->diffInDays(\Carbon\Carbon::parse($pa->tanggal)) + 1;
                $rata = $daysSpan > 0 ? ($runningTotalTerjual / $daysSpan) : 0;
                
                $updateRows[] = [
                    'id' => $pa->id,
                    'stok_terakhir' => $stokTerakhir,
                    'rata_rata_penjualan_perhari' => round($rata, 2),
                ];
            }
        }

        $this->command->info("- Memperbarui database (" . count($updateRows) . " baris)...");
        if (DB::getDriverName() === 'sqlite') {
            DB::transaction(function() use ($updateRows) {
                foreach ($updateRows as $row) {
                    DB::table('penjualan_agregat')
                        ->where('id', $row['id'])
                        ->update([
                            'stok_terakhir' => $row['stok_terakhir'],
                            'rata_rata_penjualan_perhari' => $row['rata_rata_penjualan_perhari']
                        ]);
                }
            });
        } else {
            DB::statement("
                CREATE TEMPORARY TABLE temp_update_agregat (
                    id INT PRIMARY KEY,
                    stok_terakhir INT,
                    rata_rata_penjualan_perhari DECIMAL(8,2)
                )
            ");
            
            foreach (array_chunk($updateRows, 2000) as $chunk) {
                DB::table('temp_update_agregat')->insert($chunk);
            }
            
            DB::statement("
                UPDATE penjualan_agregat pa
                JOIN temp_update_agregat tua ON pa.id = tua.id
                SET pa.stok_terakhir = tua.stok_terakhir,
                    pa.rata_rata_penjualan_perhari = tua.rata_rata_penjualan_perhari
            ");
            
            DB::statement("DROP TEMPORARY TABLE temp_update_agregat");
        }

        $this->command->info("Sinkronisasi data penjualan_agregat berhasil diselesaikan!");
    }
}
