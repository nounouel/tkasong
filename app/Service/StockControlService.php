<?php

namespace App\Service;

use App\Models\Barang;
use App\Models\PenjualanAgregat;
use App\Models\Rekomendasi;
use App\Models\TransaksiKeluar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockControlService
{
    protected $fuzzyService;

    public function __construct(FuzzyTsukamotoService $fuzzyService)
    {
        $this->fuzzyService = $fuzzyService;
    }

    /**
     * Hitung stok aktual berdasarkan transaksi masuk dan keluar
     * (Implementasi sederhana: stok_awal + masuk - keluar)
     * Bisa juga menggunakan kolom stok_saat_ini di tabel barang jika ada.
     */
    public function getStokAktual($barangId)
    {
        // Asumsi: ada tabel stock_mutations atau gunakan summing dari transaksi
        $totalMasuk = DB::table('transaksi_masuk')
            ->where('id_barang', $barangId)
            ->sum('jumlah');

        $totalKeluar = DB::table('transaksi_keluar')
            ->where('id_barang', $barangId)
            ->sum('jumlah');

        // Asumsi stok awal 0, atau bisa ambil dari kolom stok_awal barang
        return $totalMasuk - $totalKeluar;
    }

    /**
     * Hitung rata-rata penjualan harian dari tabel agregat
     * @param int $barangId
     * @param int $hari (default 30)
     * @return float
     */
    public function hitungRataPenjualanHarian($barangId, $hari = 30)
    {
        $startDate = Carbon::now()->subDays($hari)->startOfDay();

        $rata = PenjualanAgregat::where('id_barang', $barangId)
            ->where('tanggal', '>=', $startDate)
            ->avg('total_terjual');

        return round($rata, 2);
    }

    /**
     * Proses agregasi penjualan harian (dipanggil oleh command atau event)
     * Menghitung total penjualan per barang per hari dari tabel detail
     */
    public function agregasiPenjualanHarian($tanggal = null)
    {
        $tanggal = $tanggal ?: Carbon::yesterday()->toDateString();

        // Ambil semua detail penjualan pada tanggal tersebut dari transaksi_keluar
        $penjualanPerBarang = TransaksiKeluar::where('tanggal', $tanggal)
            ->select('id_barang', DB::raw('SUM(jumlah) as total'))
            ->groupBy('id_barang')
            ->get();

        foreach ($penjualanPerBarang as $item) {
            PenjualanAgregat::updateOrCreate(
                [
                    'id_barang' => $item->id_barang,
                    'tanggal'   => $tanggal,
                ],
                [
                    'total_terjual' => $item->total,
                ]
            );
        }
    }

    /**
     * Cek kondisi stok terhadap ROP untuk satu barang, jika perlu hasil fuzzy
     * @param Barang $barang
     * @return array|null
     */
    public function cekDanRekomendasi(Barang $barang)
    {
        $stokAktual = $this->getStokAktual($barang->id);

        // 1. Bandingkan dengan ROP
        if ($stokAktual > $barang->reorder_point) {
            return null; // stok aman, tidak perlu rekomendasi
        }

        // 2. Hitung rata-rata penjualan harian (30 hari terakhir)
        $rataPenjualan = $this->hitungRataPenjualanHarian($barang->id, 30);

        // 3. Fuzzy Tsukamoto
        $rekomendasiJumlah = $this->fuzzyService->hitungRekomendasi($rataPenjualan, $stokAktual);

        // 4. Simpan rekomendasi ke database
        $rekom = Rekomendasi::create([
            'id_barang'     => $barang->id,
            'stok_saat_ini' => $stokAktual,
            'rata_penjualan'=> $rataPenjualan,
            'jumlah_direkomendasikan' => $rekomendasiJumlah,
            'status'        => 'pending',
            'dihasilkan_pada' => now(),
        ]);

        return [
            'id_rekomendasi' => $rekom->id,
            'barang'         => $barang->nama_barang,
            'stok_saat_ini'  => $stokAktual,
            'reorder_point'  => $barang->reorder_point,
            'rata_penjualan' => $rataPenjualan,
            'rekomendasi'    => $rekomendasiJumlah,
        ];
    }

    /**
     * Loop semua barang dan kumpulkan rekomendasi
     * @return array
     */
    public function cekSemuaBarang()
    {
        $barangs = Barang::all();
        $hasil = [];

        foreach ($barangs as $barang) {
            $rekom = $this->cekDanRekomendasi($barang);
            if ($rekom) {
                $hasil[] = $rekom;
            }
        }
        return $hasil;
    }
}