<?php

namespace App\Http\Controllers;

use App\Models\Fuzzy;
use App\Models\Barang;
use Illuminate\Http\Request;

class FuzzyController extends Controller
{
    public function index()
    {
        $fuzzies = Fuzzy::with('barang')->get();
        return view('dashboard.fuzzy.index', compact('fuzzies'));
    }

    public function create()
    {
        $barang = Barang::all();
        return view('dashboard.fuzzy.create', compact('barang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'permintaan' => 'required|numeric',
            'stok' => 'required|numeric',
            'tanggal' => 'required|date',
        ]);

        // HITUNG FUZZY TSUKAMOTO (9 RULES)
        $hasil = $this->hitungFuzzyTsukamoto(
            $request->permintaan,
            $request->stok
        );

        $data = $request->all();
        $data['hasil_fuzzy'] = $hasil['kategori'];
        $data['nilai_crisp'] = $hasil['nilai']; // pastikan kolom ini ada di migration

        Fuzzy::create($data);

        return redirect()
            ->route('fuzzy.index')
            ->with('success', 'Data fuzzy berhasil ditambahkan');
    }

    public function edit(string $id)
    {
        $fuzzy = Fuzzy::findOrFail($id);
        $barang = Barang::all();

        return view('dashboard.fuzzy.edit', compact('fuzzy', 'barang'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'permintaan' => 'required|numeric',
            'stok' => 'required|numeric',
            'tanggal' => 'required|date',
        ]);

        $fuzzy = Fuzzy::findOrFail($id);

        $hasil = $this->hitungFuzzyTsukamoto(
            $request->permintaan,
            $request->stok
        );

        $data = $request->all();
        $data['hasil_fuzzy'] = $hasil['kategori'];
        $data['nilai_crisp'] = $hasil['nilai'];

        $fuzzy->update($data);

        return redirect()
            ->route('fuzzy.index')
            ->with('success', 'Data fuzzy berhasil diperbarui');
    }

    public function destroy(string $id)
    {
        Fuzzy::destroy($id);

        return redirect()
            ->route('fuzzy.index')
            ->with('success', 'Data fuzzy berhasil dihapus');
    }

    // =========================================================
    // FUZZY TSUKAMOTO SESUAI 9 ATURAN
    // =========================================================

    /**
     * Menghitung fuzzy Tsukamoto berdasarkan permintaan dan stok
     * Menggunakan 9 aturan dari tabel
     *
     * @param float $permintaan
     * @param float $stok
     * @return array ['nilai' => float, 'kategori' => string]
     */
    private function hitungFuzzyTsukamoto($permintaan, $stok)
    {
        // Batasi domain nilai ke 0-100 (asumsi skala 0-100)
        $permintaan = max(0, min(100, $permintaan));
        $stok = max(0, min(100, $stok));

        // -----------------------------------------------------
        // 1. FUZZIFIKASI (Himpunan Input)
        // -----------------------------------------------------
        // Penjualan : Sedikit, Sedang, Banyak
        $penjualanSedikit = $this->segitiga($permintaan, 0, 0, 50);
        $penjualanSedang  = $this->segitiga($permintaan, 25, 50, 75);
        $penjualanBanyak  = $this->segitiga($permintaan, 50, 100, 100);

        // Stok : Sedikit, Sedang, Banyak
        $stokSedikit = $this->segitiga($stok, 0, 0, 50);
        $stokSedang  = $this->segitiga($stok, 25, 50, 75);
        $stokBanyak  = $this->segitiga($stok, 50, 100, 100);

        // -----------------------------------------------------
        // 2. ATURAN (9 rule) dengan nilai output crisp (z)
        // -----------------------------------------------------
        // Nilai representatif:
        // Sedikit = 20
        // Sedang-Sedikit = 45
        // Sedang-Banyak = 70
        // Banyak = 95

        // R1: IF Penjualan Sedikit AND Stok Sedikit THEN Sedang-Banyak (70)
        $alpha1 = min($penjualanSedikit, $stokSedikit);
        $z1 = 70;

        // R2: IF Penjualan Sedikit AND Stok Sedang THEN Sedang-Sedikit (45)
        $alpha2 = min($penjualanSedikit, $stokSedang);
        $z2 = 45;

        // R3: IF Penjualan Sedikit AND Stok Banyak THEN Sedikit (20)
        $alpha3 = min($penjualanSedikit, $stokBanyak);
        $z3 = 20;

        // R4: IF Penjualan Sedang AND Stok Sedikit THEN Banyak (95)
        $alpha4 = min($penjualanSedang, $stokSedikit);
        $z4 = 95;

        // R5: IF Penjualan Sedang AND Stok Sedang THEN Sedang-Banyak (70)
        $alpha5 = min($penjualanSedang, $stokSedang);
        $z5 = 70;

        // R6: IF Penjualan Sedang AND Stok Banyak THEN Sedang-Sedikit (45)
        $alpha6 = min($penjualanSedang, $stokBanyak);
        $z6 = 45;

        // R7: IF Penjualan Banyak AND Stok Sedikit THEN Banyak (95)
        $alpha7 = min($penjualanBanyak, $stokSedikit);
        $z7 = 95;

        // R8: IF Penjualan Banyak AND Stok Sedang THEN Banyak (95)
        $alpha8 = min($penjualanBanyak, $stokSedang);
        $z8 = 95;

        // R9: IF Penjualan Banyak AND Stok Banyak THEN Sedang-Banyak (70)
        $alpha9 = min($penjualanBanyak, $stokBanyak);
        $z9 = 70;

        // -----------------------------------------------------
        // 3. DEFUZZIFIKASI (Weighted Average)
        // -----------------------------------------------------
        $pembilang = ($alpha1 * $z1) + ($alpha2 * $z2) + ($alpha3 * $z3)
                   + ($alpha4 * $z4) + ($alpha5 * $z5) + ($alpha6 * $z6)
                   + ($alpha7 * $z7) + ($alpha8 * $z8) + ($alpha9 * $z9);

        $penyebut = $alpha1 + $alpha2 + $alpha3 + $alpha4 + $alpha5
                  + $alpha6 + $alpha7 + $alpha8 + $alpha9;

        if ($penyebut == 0) {
            $hasilCrisp = 50; // nilai tengah jika tidak ada aturan aktif
        } else {
            $hasilCrisp = $pembilang / $penyebut;
        }

        // -----------------------------------------------------
        // 4. KONVERSI KE KATEGORI OUTPUT (4 kategori)
        // -----------------------------------------------------
        if ($hasilCrisp <= 30) {
            $kategori = 'Sedikit';
        } elseif ($hasilCrisp <= 50) {
            $kategori = 'Sedang-Sedikit';
        } elseif ($hasilCrisp <= 75) {
            $kategori = 'Sedang-Banyak';
        } else {
            $kategori = 'Banyak';
        }

        return [
            'nilai'    => round($hasilCrisp, 2),
            'kategori' => $kategori,
        ];
    }

    /**
     * Fungsi keanggotaan segitiga
     *
     * @param float $x Nilai input
     * @param float $a Batas kiri (nilai 0)
     * @param float $b Puncak (nilai 1)
     * @param float $c Batas kanan (nilai 0)
     * @return float
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
            return ($x - $a) / ($b - $a);
        }
        return ($c - $x) / ($c - $b);
    }
}