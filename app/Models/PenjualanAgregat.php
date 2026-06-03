<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanAgregat extends Model
{
    protected $table = 'penjualan_agregat';

    protected $fillable = [
        'id_barang',
        'tanggal',
        'total_terjual',
        'reorder_point',
        'stok_terakhir',
        'rata_rata_penjualan_perhari',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function getStokTerakhirAttribute()
    {
        return $this->attributes['stok_terakhir'] ?? 0;
    }

    /**
     * Sinkronisasikan semua record penjualan_agregat untuk id_barang sejak tanggal tertentu.
     */
    public static function syncAgregatForBarang(int $idBarang, string $tanggal)
    {
        // 1. Sync the specific date first to create/update/delete the record
        self::syncAgregat($idBarang, $tanggal);

        // 2. Sync all subsequent aggregates
        $dates = self::where('id_barang', $idBarang)
            ->where('tanggal', '>', $tanggal)
            ->orderBy('tanggal', 'asc')
            ->pluck('tanggal');

        foreach ($dates as $date) {
            self::syncAgregat($idBarang, $date);
        }
    }

    /**
     * Sinkronisasikan record penjualan_agregat untuk id_barang dan tanggal tertentu.
     */
    public static function syncAgregat(int $idBarang, string $tanggal)
    {
        // Hitung total terjual dari transaksi keluar
        $totalTerjual = \Illuminate\Support\Facades\DB::table('transaksi_keluar')
            ->where('id_barang', $idBarang)
            ->where('tanggal', $tanggal)
            ->sum('jumlah');

        $barang = \Illuminate\Support\Facades\DB::table('barang')->where('id', $idBarang)->first();
        if (!$barang) {
            \Illuminate\Support\Facades\DB::table('penjualan_agregat')
                ->where('id_barang', $idBarang)
                ->where('tanggal', $tanggal)
                ->delete();
            return;
        }

        $ss = $barang->stok_minimum;
        $d = (int) $totalTerjual;
        $L = 2; // Lead Time 2 hari
        $rop = ($d * $L) + $ss;
// dd([
    
//     'ss' => $ss,
//     'demand' => $d,
//     'total_terjual' => $d,
//                     'reorder_point' => $rop
// ]);
        if ($d > 0) {
            // Update or insert basic record
            \Illuminate\Support\Facades\DB::table('penjualan_agregat')->updateOrInsert(
                [
                    'id_barang' => $idBarang,
                    'tanggal'   => $tanggal,
                ],
                [
                    'total_terjual' => $d,
                    'reorder_point' => $rop,
                    'updated_at'    => now(),
                ]
            );

            // Calculate stok_terakhir
            $totalMasuk = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
                ->where('id_barang', $idBarang)
                ->where('tanggal', '<=', $tanggal)
                ->sum('jumlah');

            $totalKeluar = \Illuminate\Support\Facades\DB::table('transaksi_keluar')
                ->where('id_barang', $idBarang)
                ->where('tanggal', '<=', $tanggal)
                ->sum('jumlah');

            $stokTerakhir = $totalMasuk - $totalKeluar;

            // Calculate rata_rata_penjualan_perhari (total sold divided by days span from first sale to current date)
            $oldestDate = \Illuminate\Support\Facades\DB::table('penjualan_agregat')
                ->where('id_barang', $idBarang)
                ->min('tanggal');

            $minTanggal = $oldestDate ?: $tanggal;
            if ($tanggal < $minTanggal) {
                $minTanggal = $tanggal;
            }
            $daysSpan = \Carbon\Carbon::parse($minTanggal)->diffInDays(\Carbon\Carbon::parse($tanggal)) + 1;

            $totalSoldAccumulated = \Illuminate\Support\Facades\DB::table('penjualan_agregat')
                ->where('id_barang', $idBarang)
                ->where('tanggal', '<=', $tanggal)
                ->sum('total_terjual');

            $rata = $totalSoldAccumulated / $daysSpan;

            // Update calculated columns
            \Illuminate\Support\Facades\DB::table('penjualan_agregat')
                ->where('id_barang', $idBarang)
                ->where('tanggal', $tanggal)
                ->update([
                    'stok_terakhir' => $stokTerakhir,
                    'rata_rata_penjualan_perhari' => round($rata ?: 0, 2),
                ]);
        } else {
            // Hapus record jika tidak ada penjualan lagi pada tanggal tersebut
            \Illuminate\Support\Facades\DB::table('penjualan_agregat')
                ->where('id_barang', $idBarang)
                ->where('tanggal', $tanggal)
                ->delete();
        }
    }
}
