<?php

namespace App\Service;

use App\Models\Barang;
use App\Models\PenjualanAgregat;
use App\Models\Fuzzy;
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
     * @param int|null $hari
     * @return float
     */
    public function hitungRataPenjualanHarian($barangId, $hari = null)
    {
        // Get the latest penjualan_agregat record's rata_rata_penjualan_perhari, or calculate it.
        $latest = PenjualanAgregat::where('id_barang', $barangId)
            ->orderBy('tanggal', 'desc')
            ->first();

        if ($latest && $latest->rata_rata_penjualan_perhari !== null) {
            return floatval($latest->rata_rata_penjualan_perhari);
        }

        // Fallback calculation if not in database
        $oldestDate = DB::table('penjualan_agregat')
            ->where('id_barang', $barangId)
            ->min('tanggal');

        if (!$oldestDate) {
            return 0.0;
        }

        $latestDate = DB::table('penjualan_agregat')
            ->where('id_barang', $barangId)
            ->max('tanggal');

        $daysSpan = Carbon::parse($oldestDate)->diffInDays(Carbon::parse($latestDate)) + 1;

        $totalSoldAccumulated = DB::table('penjualan_agregat')
            ->where('id_barang', $barangId)
            ->sum('total_terjual');

        return round($daysSpan > 0 ? ($totalSoldAccumulated / $daysSpan) : 0, 2);
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
            PenjualanAgregat::syncAgregat($item->id_barang, $tanggal);
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

        // Fetch reorder_point from latest penjualan_agregat
        $latestAgregat = PenjualanAgregat::where('id_barang', $barang->id)
            ->orderBy('tanggal', 'desc')
            ->first();
        $reorderPoint = $latestAgregat ? $latestAgregat->reorder_point : 10;

        // 1. Bandingkan dengan ROP
        if ($stokAktual > $reorderPoint) {
            // Hapus rekomendasi pending/stale yang sudah aman stoknya
            Fuzzy::where('id_barang', $barang->id)->delete();
            return null; // stok aman, tidak perlu rekomendasi
        }

        // 2. Hitung rata-rata penjualan harian (30 hari terakhir)
        $rataPenjualan = $this->hitungRataPenjualanHarian($barang->id, 30);

        // 3. Fuzzy Tsukamoto
        $rekomendasiJumlah = $this->fuzzyService->hitungRekomendasi($rataPenjualan, $stokAktual, $barang->id);

        // 4. Simpan / update rekomendasi ke database (fuzzies)
        $maxPembelian = DB::table('transaksi_masuk')
            ->where('id_barang', $barang->id)
            ->max('jumlah') ?: 100;

        if ($rekomendasiJumlah <= ($maxPembelian * 0.30)) {
            $kategori = 'Sedikit';
        } elseif ($rekomendasiJumlah <= ($maxPembelian * 0.50)) {
            $kategori = 'Sedang-Sedikit';
        } elseif ($rekomendasiJumlah <= ($maxPembelian * 0.75)) {
            $kategori = 'Sedang-Banyak';
        } else {
            $kategori = 'Banyak';
        }

        $rekom = Fuzzy::updateOrCreate(
            [
                'id_barang' => $barang->id,
                'tanggal'   => now()->toDateString(),
            ],
            [
                'stok'        => $stokAktual,
                'permintaan'  => $rataPenjualan,
                'nilai_crisp' => (int) round($rekomendasiJumlah),
                'hasil_fuzzy' => $kategori,
            ]
        );

        return [
            'id_rekomendasi' => $rekom->id,
            'barang'         => $barang->nama_barang,
            'stok_saat_ini'  => $stokAktual,
            'reorder_point'  => $reorderPoint,
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

    /**
     * Cek kondisi stok dan hitung rekomendasi menggunakan ID barang
     */
    public function cekDanRekomendasiById($barangId)
    {
        $barang = Barang::find($barangId);
        if ($barang) {
            return $this->cekDanRekomendasi($barang);
        }
        return null;
    }
}