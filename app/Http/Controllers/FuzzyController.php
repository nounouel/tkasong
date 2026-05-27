<?php

namespace App\Http\Controllers;

use App\Models\Fuzzy;
use App\Models\Barang;
use App\Models\Traning;
use Illuminate\Http\Request;

use App\Service\FuzzyTsukamotoService;

class FuzzyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $fuzzies = Fuzzy::with('barang')
            ->when($search, function ($query, $search) {
                return $query->whereHas('barang', function ($q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%");
                })->orWhere('tanggal', 'like', "%{$search}%")
                  ->orWhere('hasil_fuzzy', 'like', "%{$search}%");
            })->paginate(10)->withQueryString();
        return view('dashboard.fuzzy.index', compact('fuzzies'));
    }

    public function create()
    {
        $barang = Barang::all();
        return view('dashboard.fuzzy.create', compact('barang'));
    }

    public function store(Request $request)
    {
        // dd($request);

        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'pembelian' => 'required|numeric',
            'stok_akhir' => 'required|numeric',
            'tanggal' => 'required|date',
            'penjualan' => 'required|numeric',
        ]);

        // HITUNG FUZZY TSUKAMOTO (9 RULES)
        $hasil = $this->hitungFuzzyTsukamoto(
            $request->pembelian,
            $request->stok_akhir,
            $request->tanggal,
            $request->penjualan,
            $request->id_barang
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


    public function getRekomendasi(Request $request)
{
    $penjualan = $request->input('penjualan'); // misal 85
    $stok      = $request->input('stok');      // misal 120

    $fuzzy = new FuzzyTsukamotoService();
    $rekomendasi = $fuzzy->hitungRekomendasi($penjualan, $stok);

    return response()->json([
        'penjualan' => $penjualan,
        'stok'      => $stok,
        'rekomendasi_pembelian' => $rekomendasi
    ]);
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
    private function hitungFuzzyTsukamoto($pembelian, 
    $stok_akhir, 
    $tanggal, 
    $penjualan,
    $id_barang)
    {

        $tgl = \Carbon\Carbon::parse($tanggal);

        $bulan_ini = $tgl->month;
        $tahun_ini = $tgl->year;

        // Mengambil nilai tertinggi dan terendah
        $valstok_akhirdbMAX = Traning::where('id_barang', $id_barang)->max('persediaan_akhir');
        $valstok_akhirdbMIN = Traning::where('id_barang', $id_barang)->min('persediaan_akhir');

        $valpembeliandbMAX = Traning::where('id_barang', $id_barang)->max('pembelian');
        $valpembeliandbMIN = Traning::where('id_barang', $id_barang)->min('pembelian');

        $valpenjualandbMAX = Traning::where('id_barang', $id_barang)->max('penjualan');
        $valpenjualandbMIN = Traning::where('id_barang', $id_barang)->min('penjualan');


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

    public function predict(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'tanggal' => 'required|date',
        ]);

        $tahun = date('Y', strtotime($request->tanggal));
        $bulan = date('n', strtotime($request->tanggal));

        $training = Traning::with('barang')->where('id_barang', $request->id_barang)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        if (!$training) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data training untuk barang dan tanggal tersebut tidak ditemukan.',
            ], 404);
        }

        // Penjualan maps to permintaan
        $permintaan = $training->penjualan;
        // Stok maps to persediaan_awal
        $stok = $training->persediaan_awal;

        // Fetch min/max values for this barang from all training data
        $minMax = Traning::where('id_barang', $request->id_barang)
            ->selectRaw('MIN(pembelian) as min_pembelian, MAX(pembelian) as max_pembelian,
                         MIN(penjualan) as min_penjualan, MAX(penjualan) as max_penjualan,
                         MIN(persediaan_akhir) as min_persediaan_akhir, MAX(persediaan_akhir) as max_persediaan_akhir,
                         MIN(persediaan_awal) as min_persediaan_awal, MAX(persediaan_awal) as max_persediaan_awal')
            ->first();

        // Perform prediction and extract steps
        $steps = $this->getFuzzySteps($permintaan, $stok);

        // Add barang info, training values, and minMax values to steps
        $steps['nama_barang'] = $training->barang->nama_barang;
        $steps['training_data'] = [
            'pembelian' => $training->pembelian,
            'penjualan' => $training->penjualan,
            'persediaan_akhir' => $training->persediaan_akhir,
            'persediaan_awal' => $training->persediaan_awal,
        ];
        $steps['min_max'] = [
            'pembelian' => [
                'min' => $minMax->min_pembelian ?? 0,
                'max' => $minMax->max_pembelian ?? 0,
            ],
            'penjualan' => [
                'min' => $minMax->min_penjualan ?? 0,
                'max' => $minMax->max_penjualan ?? 0,
            ],
            'persediaan_akhir' => [
                'min' => $minMax->min_persediaan_akhir ?? 0,
                'max' => $minMax->max_persediaan_akhir ?? 0,
            ],
            'persediaan_awal' => [
                'min' => $minMax->min_persediaan_awal ?? 0,
                'max' => $minMax->max_persediaan_awal ?? 0,
            ],
        ];

        return response()->json([
            'status' => 'success',
            'permintaan' => $permintaan,
            'stok' => $stok,
            'hasil_fuzzy' => $steps['kategori'],
            'nilai_crisp' => $steps['nilai'],
            'steps' => $steps
        ]);
    }

    private function getFuzzySteps($permintaan, $stok)
    {
        $permintaan_orig = $permintaan;
        $stok_orig = $stok;
        
        // Limit domain 0-100
        $permintaan = max(0, min(100, $permintaan));
        $stok = max(0, min(100, $stok));

        // 1. FUZZIFIKASI
        // Penjualan : Sedikit, Sedang, Banyak
        $penjualanSedikit = $this->segitiga($permintaan, 0, 0, 50);
        $penjualanSedang  = $this->segitiga($permintaan, 25, 50, 75);
        $penjualanBanyak  = $this->segitiga($permintaan, 50, 100, 100);

        // Stok : Sedikit, Sedang, Banyak
        $stokSedikit = $this->segitiga($stok, 0, 0, 50);
        $stokSedang  = $this->segitiga($stok, 25, 50, 75);
        $stokBanyak  = $this->segitiga($stok, 50, 100, 100);

        // Generate formula strings for Penjualan
        $penjualanSedikitFormula = "";
        if ($permintaan <= 0) {
            $penjualanSedikitFormula = "1";
        } elseif ($permintaan >= 50) {
            $penjualanSedikitFormula = "0";
        } else {
            $penjualanSedikitFormula = "(50 - $permintaan) / (50 - 0)";
        }

        $penjualanSedangFormula = "";
        if ($permintaan <= 25 || $permintaan >= 75) {
            $penjualanSedangFormula = "0";
        } elseif ($permintaan == 50) {
            $penjualanSedangFormula = "1";
        } elseif ($permintaan > 25 && $permintaan < 50) {
            $penjualanSedangFormula = "($permintaan - 25) / (50 - 25)";
        } else {
            $penjualanSedangFormula = "(75 - $permintaan) / (75 - 50)";
        }

        $penjualanBanyakFormula = "";
        if ($permintaan <= 50) {
            $penjualanBanyakFormula = "0";
        } elseif ($permintaan >= 100) {
            $penjualanBanyakFormula = "1";
        } else {
            $penjualanBanyakFormula = "($permintaan - 50) / (100 - 50)";
        }

        // Generate formula strings for Stok
        $stokSedikitFormula = "";
        if ($stok <= 0) {
            $stokSedikitFormula = "1";
        } elseif ($stok >= 50) {
            $stokSedikitFormula = "0";
        } else {
            $stokSedikitFormula = "(50 - $stok) / (50 - 0)";
        }

        $stokSedangFormula = "";
        if ($stok <= 25 || $stok >= 75) {
            $stokSedangFormula = "0";
        } elseif ($stok == 50) {
            $stokSedangFormula = "1";
        } elseif ($stok > 25 && $stok < 50) {
            $stokSedangFormula = "($stok - 25) / (50 - 25)";
        } else {
            $stokSedangFormula = "(75 - $stok) / (75 - 50)";
        }

        $stokBanyakFormula = "";
        if ($stok <= 50) {
            $stokBanyakFormula = "0";
        } elseif ($stok >= 100) {
            $stokBanyakFormula = "1";
        } else {
            $stokBanyakFormula = "($stok - 50) / (100 - 50)";
        }

        // 2. ATURAN (9 rule) dengan nilai output crisp (z)
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

        // 3. DEFUZZIFIKASI (Weighted Average)
        $pembilang = ($alpha1 * $z1) + ($alpha2 * $z2) + ($alpha3 * $z3)
                   + ($alpha4 * $z4) + ($alpha5 * $z5) + ($alpha6 * $z6)
                   + ($alpha7 * $z7) + ($alpha8 * $z8) + ($alpha9 * $z9);

        $penyebut = $alpha1 + $alpha2 + $alpha3 + $alpha4 + $alpha5
                  + $alpha6 + $alpha7 + $alpha8 + $alpha9;

        if ($penyebut == 0) {
            $hasilCrisp = 50;
        } else {
            $hasilCrisp = $pembilang / $penyebut;
        }

        // 4. KATEGORI
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
            'permintaan' => $permintaan_orig,
            'stok' => $stok_orig,
            'permintaan_limited' => $permintaan,
            'stok_limited' => $stok,
            'fuzzifikasi' => [
                'penjualan' => [
                    'sedikit' => round($penjualanSedikit, 4),
                    'sedang' => round($penjualanSedang, 4),
                    'banyak' => round($penjualanBanyak, 4),
                ],
                'stok' => [
                    'sedikit' => round($stokSedikit, 4),
                    'sedang' => round($stokSedang, 4),
                    'banyak' => round($stokBanyak, 4),
                ],
                'formulas' => [
                    'penjualan' => [
                        'sedikit' => $penjualanSedikitFormula,
                        'sedang' => $penjualanSedangFormula,
                        'banyak' => $penjualanBanyakFormula,
                    ],
                    'stok' => [
                        'sedikit' => $stokSedikitFormula,
                        'sedang' => $stokSedangFormula,
                        'banyak' => $stokBanyakFormula,
                    ]
                ]
            ],
            'rules' => [
                ['name' => 'R1', 'text' => 'IF Penjualan Sedikit AND Stok Sedikit THEN Pembelian Sedang-Banyak', 'alpha' => round($alpha1, 4), 'z' => $z1],
                ['name' => 'R2', 'text' => 'IF Penjualan Sedikit AND Stok Sedang THEN Pembelian Sedang-Sedikit', 'alpha' => round($alpha2, 4), 'z' => $z2],
                ['name' => 'R3', 'text' => 'IF Penjualan Sedikit AND Stok Banyak THEN Pembelian Sedikit', 'alpha' => round($alpha3, 4), 'z' => $z3],
                ['name' => 'R4', 'text' => 'IF Penjualan Sedang AND Stok Sedikit THEN Pembelian Banyak', 'alpha' => round($alpha4, 4), 'z' => $z4],
                ['name' => 'R5', 'text' => 'IF Penjualan Sedang AND Stok Sedang THEN Pembelian Sedang-Banyak', 'alpha' => round($alpha5, 4), 'z' => $z5],
                ['name' => 'R6', 'text' => 'IF Penjualan Sedang AND Stok Banyak THEN Pembelian Sedang-Sedikit', 'alpha' => round($alpha6, 4), 'z' => $z6],
                ['name' => 'R7', 'text' => 'IF Penjualan Banyak AND Stok Sedikit THEN Pembelian Banyak', 'alpha' => round($alpha7, 4), 'z' => $z7],
                ['name' => 'R8', 'text' => 'IF Penjualan Banyak AND Stok Sedang THEN Pembelian Banyak', 'alpha' => round($alpha8, 4), 'z' => $z8],
                ['name' => 'R9', 'text' => 'IF Penjualan Banyak AND Stok Banyak THEN Pembelian Sedang-Banyak', 'alpha' => round($alpha9, 4), 'z' => $z9],
            ],
            'defuzzifikasi' => [
                'pembilang' => round($pembilang, 4),
                'penyebut' => round($penyebut, 4),
                'hasil_crisp' => round($hasilCrisp, 4)
            ],
            'nilai' => round($hasilCrisp, 2),
            'kategori' => $kategori
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