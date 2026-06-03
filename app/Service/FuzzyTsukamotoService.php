<?php

namespace App\Service;

class FuzzyTsukamotoService
{
    /**
     * Hitung rekomendasi pembelian dengan Fuzzy Tsukamoto
     * @param float $penjualan Rata-rata penjualan harian (atau periode tertentu)
     * @param float $stok Stok aktual saat ini
     * @param int|null $idBarang ID barang untuk mencari batasan dinamis
     * @return int Jumlah pembelian yang direkomendasikan (dibulatkan)
     */
    public function hitungRekomendasi($penjualan, $stok, $idBarang = null)
    {
        if ($idBarang) {
            $domains = $this->getDynamicDomains($idBarang);
        } else {
            // Default backward compatible domains jika idBarang tidak disuplai
            $domains = [
                'penjualan' => ['min' => 0, 'max' => 200],
                'stok'      => ['min' => 0, 'max' => 300],
                'pembelian' => ['min' => 0, 'max' => 200]
            ];
        }

        $result = $this->hitungRekomendasiDinamis($penjualan, $stok, $domains);
        return $result['nilai'];
    }

    /**
     * Hitung rekomendasi pembelian dengan Fuzzy Tsukamoto menggunakan domain dinamis
     * @param float $penjualan Penjualan harian
     * @param float $stok Stok saat ini
     * @param array $domains Batasan min/max dinamis untuk variabel
     * @return array ['nilai' => float, 'kategori' => string]
     */
    public function hitungRekomendasiDinamis($penjualan, $stok, $domains)
    {
        // Ambil batasan dinamis
        $minPenjualan = $domains['penjualan']['min'];
        $maxPenjualan = $domains['penjualan']['max'];

        $minStok = $domains['stok']['min'];
        $maxStok = $domains['stok']['max'];

        $minPembelian = $domains['pembelian']['min'];
        $maxPembelian = $domains['pembelian']['max'];

        // Fuzzifikasi input Penjualan
        $midPenjualan = ($minPenjualan + $maxPenjualan) / 2;
        $q1Penjualan = $minPenjualan + ($midPenjualan - $minPenjualan) / 2;
        $q3Penjualan = $midPenjualan + ($maxPenjualan - $midPenjualan) / 2;

        $muPenjualan = [
            'sedikit' => $this->segitiga($penjualan, $minPenjualan, $minPenjualan, $midPenjualan),
            'sedang'  => $this->segitiga($penjualan, $q1Penjualan, $midPenjualan, $q3Penjualan),
            'banyak'  => $this->segitiga($penjualan, $midPenjualan, $maxPenjualan, $maxPenjualan),
        ];

        // Fuzzifikasi input Stok
        $midStok = ($minStok + $maxStok) / 2;
        $q1Stok = $minStok + ($midStok - $minStok) / 2;
        $q3Stok = $midStok + ($maxStok - $midStok) / 2;

        $muStok = [
            'sedikit' => $this->segitiga($stok, $minStok, $minStok, $midStok),
            'sedang'  => $this->segitiga($stok, $q1Stok, $midStok, $q3Stok),
            'banyak'  => $this->segitiga($stok, $midStok, $maxStok, $maxStok),
        ];

        // 9 Rules
        $rules = [
            ['p' => 'sedikit', 's' => 'sedikit',  'out' => 'sedang_banyak'],
            ['p' => 'sedikit', 's' => 'sedang',   'out' => 'sedang_sedikit'],
            ['p' => 'sedikit', 's' => 'banyak',   'out' => 'sedikit'],
            ['p' => 'sedang',  's' => 'sedikit',  'out' => 'banyak'],
            ['p' => 'sedang',  's' => 'sedang',   'out' => 'sedang_banyak'],
            ['p' => 'sedang',  's' => 'banyak',   'out' => 'sedang_sedikit'],
            ['p' => 'banyak',  's' => 'sedikit',  'out' => 'banyak'],
            ['p' => 'banyak',  's' => 'sedang',   'out' => 'banyak'],
            ['p' => 'banyak',  's' => 'banyak',   'out' => 'sedang_banyak'],
        ];

        $pembilang = 0;
        $penyebut = 0;

        foreach ($rules as $i => $rule) {
            $alpha = min($muPenjualan[$rule['p']], $muStok[$rule['s']]);
            if ($alpha <= 0) {
                continue;
            }

            $z = 0;
            switch ($rule['out']) {
                case 'sedikit':
                    $z = ($maxPembelian * 0.25) * (1 - $alpha);
                    break;
                case 'sedang_sedikit':
                    $z = ($maxPembelian * 0.25) + ($maxPembelian * 0.20) * $alpha;
                    break;
                case 'sedang_banyak':
                    $z = ($maxPembelian * 0.45) + ($maxPembelian * 0.30) * $alpha;
                    break;
                case 'banyak':
                    $z = ($maxPembelian * 0.75) + ($maxPembelian * 0.25) * $alpha;
                    break;
            }

            $pembilang += $alpha * $z;
            $penyebut += $alpha;
        }

        if ($penyebut == 0) {
            $hasilCrisp = ($minPembelian + $maxPembelian) / 2; // default
        } else {
            $hasilCrisp = $pembilang / $penyebut;
        }

        // Kategori Output
        if ($hasilCrisp <= ($maxPembelian * 0.30)) {
            $kategori = 'Sedikit';
        } elseif ($hasilCrisp <= ($maxPembelian * 0.50)) {
            $kategori = 'Sedang-Sedikit';
        } elseif ($hasilCrisp <= ($maxPembelian * 0.75)) {
            $kategori = 'Sedang-Banyak';
        } else {
            $kategori = 'Banyak';
        }

        return [
            'nilai' => (int) round($hasilCrisp),
            'kategori' => $kategori
        ];
    }

    /**
     * Mengambil batasan dinamis untuk barang dari data transaksi & penjualan_agregat
     */
    private function getDynamicDomains($idBarang)
    {
        // 1. Max Penjualan (dari penjualan_agregat)
        $maxPenjualan = \Illuminate\Support\Facades\DB::table('penjualan_agregat')
            ->where('id_barang', $idBarang)
            ->max('total_terjual') ?: 50;

        // 2. Max Stok (dari transaksi masuk - transaksi keluar)
        $totalMasuk = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
            ->where('id_barang', $idBarang)
            ->sum('jumlah');

        $totalKeluar = \Illuminate\Support\Facades\DB::table('transaksi_keluar')
            ->where('id_barang', $idBarang)
            ->sum('jumlah');

        $maxStok = $totalMasuk - $totalKeluar;
        if ($maxStok <= 0) {
            $maxStok = 100;
        }

        // 3. Max Pembelian (dari transaksi_masuk)
        $maxPembelian = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
            ->where('id_barang', $idBarang)
            ->max('jumlah') ?: 100;

        return [
            'penjualan' => ['min' => 0, 'max' => $maxPenjualan],
            'stok'      => ['min' => 0, 'max' => $maxStok],
            'pembelian' => ['min' => 0, 'max' => $maxPembelian]
        ];
    }

    /**
     * Fungsi keanggotaan segitiga
     */
    private function segitiga($x, $a, $b, $c)
    {
        if ($x <= $a || $x >= $c) {
            return 0;
        }
        if ($x == $b) {
            return 1;
        }
        if ($x < $b) {
            if ($b == $a) return 0;
            return ($x - $a) / ($b - $a);
        }
        if ($c == $b) return 0;
        return ($c - $x) / ($c - $b);
    }
}