<?php

namespace App\Service;

class FuzzyTsukamotoService
{
    // ======================= FUZZIFIKASI PENJUALAN =======================
    // Domain penjualan (misal 0-200), bisa disesuaikan
    private function membershipPenjualanSedikit($x)
    {
        if ($x <= 0) return 1;
        if ($x >= 70) return 0;
        return (70 - $x) / 70;
    }

    private function membershipPenjualanSedang($x)
    {
        if ($x <= 70 || $x >= 130) return 0;
        if ($x <= 100) return ($x - 70) / 30;
        return (130 - $x) / 30;
    }

    private function membershipPenjualanBanyak($x)
    {
        if ($x <= 130) return 0;
        if ($x >= 200) return 1;
        return ($x - 130) / 70;
    }

    // ======================= FUZZIFIKASI STOK =======================
    // Domain stok (misal 0-300)
    private function membershipStokSedikit($x)
    {
        if ($x <= 0) return 1;
        if ($x >= 100) return 0;
        return (100 - $x) / 100;
    }

    private function membershipStokSedang($x)
    {
        if ($x <= 100 || $x >= 200) return 0;
        if ($x <= 150) return ($x - 100) / 50;
        return (200 - $x) / 50;
    }

    private function membershipStokBanyak($x)
    {
        if ($x <= 200) return 0;
        if ($x >= 300) return 1;
        return ($x - 200) / 100;
    }

    // ======================= OUTPUT Z UNTUK MASING-MASING HIMPUNAN =======================
    // Output domain pembelian (0-200)
    private function zSedikit($alpha)
    {
        // Sedikit: 0 -> 50
        return 50 * (1 - $alpha);
    }

    private function zSedangSedikit($alpha)
    {
        // Sedang-Sedikit: 50 -> 90
        return 50 + 40 * $alpha;
    }

    private function zSedangBanyak($alpha)
    {
        // Sedang-Banyak: 90 -> 150
        return 90 + 60 * $alpha;
    }

    private function zBanyak($alpha)
    {
        // Banyak: 150 -> 200
        return 150 + 50 * $alpha;
    }

    /**
     * Hitung rekomendasi pembelian dengan Fuzzy Tsukamoto
     * @param float $penjualan Rata-rata penjualan harian (atau periode tertentu)
     * @param float $stok Stok aktual saat ini
     * @return int Jumlah pembelian yang direkomendasikan (dibulatkan)
     */
    public function hitungRekomendasi($penjualan, $stok)
    {
        // 1. Fuzzyfikasi input
        $muPenjualan = [
            'sedikit' => $this->membershipPenjualanSedikit($penjualan),
            'sedang'  => $this->membershipPenjualanSedang($penjualan),
            'banyak'  => $this->membershipPenjualanBanyak($penjualan),
        ];

        $muStok = [
            'sedikit' => $this->membershipStokSedikit($stok),
            'sedang'  => $this->membershipStokSedang($stok),
            'banyak'  => $this->membershipStokBanyak($stok),
        ];

        // 2. Aturan (R1..R9)
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

        $alpha = [];
        $z = [];

        foreach ($rules as $i => $rule) {
            $alpha[$i] = min($muPenjualan[$rule['p']], $muStok[$rule['s']]);
            if ($alpha[$i] == 0) {
                $z[$i] = 0;
                continue;
            }

            switch ($rule['out']) {
                case 'sedikit':
                    $z[$i] = $this->zSedikit($alpha[$i]);
                    break;
                case 'sedang_sedikit':
                    $z[$i] = $this->zSedangSedikit($alpha[$i]);
                    break;
                case 'sedang_banyak':
                    $z[$i] = $this->zSedangBanyak($alpha[$i]);
                    break;
                case 'banyak':
                    $z[$i] = $this->zBanyak($alpha[$i]);
                    break;
                default:
                    $z[$i] = 0;
            }
        }

        // 3. Defuzzifikasi (Weighted Average)
        $pembilang = 0;
        $penyebut = 0;
        for ($i = 0; $i < count($rules); $i++) {
            $pembilang += $alpha[$i] * $z[$i];
            $penyebut += $alpha[$i];
        }

        if ($penyebut == 0) {
            return 0;
        }

        return (int) round($pembilang / $penyebut);
    }
}