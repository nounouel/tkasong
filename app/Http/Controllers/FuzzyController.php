<?php

namespace App\Http\Controllers;

use App\Models\Fuzzy;
use App\Models\Barang;
use App\Models\PenjualanAgregat;
use App\Models\TransaksiMasuk;
use App\Models\TransaksiKeluar;
use Illuminate\Http\Request;

use App\Service\FuzzyTsukamotoService;

class FuzzyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        // Mengambil data dari tabel fuzzies
        $fuzzies = \App\Models\Fuzzy::with('barang')
            ->when($search, function ($query, $search) {
                return $query->whereHas('barang', function ($q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%");
                });
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.fuzzy.index', compact('fuzzies'));
    }

    public function create()
    {
        // Ambil data barang yang stok_terakhirnya <= ROP
        $barang = Barang::all()->filter(function ($item) {
            // Hitung stok terakhir berdasarkan transaksi masuk dikurangi transaksi keluar
            $totalMasuk = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
                ->where('id_barang', $item->id)
                ->sum('jumlah');

            $totalKeluar = \Illuminate\Support\Facades\DB::table('transaksi_keluar')
                ->where('id_barang', $item->id)
                ->sum('jumlah');

            $stokTerakhir = $totalMasuk - $totalKeluar;

            // Ambil ROP terakhir dari penjualan agregat
            $latestAgregat = \App\Models\PenjualanAgregat::where('id_barang', $item->id)
                ->orderBy('tanggal', 'desc')
                ->first();

            $reorderPoint = $latestAgregat ? $latestAgregat->reorder_point : ($item->stok_minimum ?? 10);

            return $stokTerakhir <= $reorderPoint;
        })->values();

        $barang_ROP = $barang;
  
        return view('dashboard.fuzzy.create', compact('barang', 'barang_ROP'));
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
            $request->stok,
            $request->permintaan,
            $request->id_barang
        );

        $permintaanBulat = (int) round($hasil['nilai']);

        $data = $request->all();
        $data['hasil_fuzzy'] = $hasil['kategori'];
        $data['nilai_crisp'] = $permintaanBulat;

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

    public function predict(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'tanggal' => 'required|date',
        ]);

        $barang = Barang::findOrFail($request->id_barang);
        $tahun = date('Y', strtotime($request->tanggal));
        $bulan = date('n', strtotime($request->tanggal));

        $stockService = app(\App\Service\StockControlService::class);
        $stokAktual = $stockService->getStokAktual($barang->id);
        $rataPenjualan = $stockService->hitungRataPenjualanHarian($barang->id, 30);

        $dynData = $this->getDynamicTrainingData($barang->id, $tahun, $bulan, $rataPenjualan, $stokAktual);
        $training_data = $dynData['training_data'];

        $permintaan = $training_data['penjualan'];
        $stok = $training_data['persediaan_awal'];

        $minMax = $this->getDynamicMinMax($barang->id);

        $steps = $this->getFuzzySteps($permintaan, $stok);

        $steps['nama_barang'] = $barang->nama_barang;
        $steps['training_data'] = $training_data;
        $steps['min_max'] = $minMax;

        return response()->json([
            'status' => 'success',
            'permintaan' => $permintaan,
            'stok' => $stok,
            'hasil_fuzzy' => $steps['kategori'],
            'nilai_crisp' => (int) round($steps['nilai']),
            'steps' => $steps
        ]);
    }   

public function detail(string $id)
{
    $fuzzy = Fuzzy::with('barang')->findOrFail($id);

    $hasil = $this->hitungFuzzyTsukamoto(
        $fuzzy->stok,
        $fuzzy->rata_rata_penjualan_perhari,
        $fuzzy->id_barang
    );
    
    $domains = $this->getDynamicDomains($fuzzy->id_barang);

    return response()->json([
        'status' => 'success',
        'id' => $fuzzy->id,
        'status_fuzzy' => $fuzzy->status,
        'nama_barang' => $fuzzy->barang->nama_barang,
        'dihasilkan_pada' => $fuzzy->tanggal,
        'permintaan' => $fuzzy->rata_rata_penjualan_perhari,
        'stok' => $fuzzy->stok,
        'hasil_fuzzy' => $hasil['kategori'],
        'nilai_crisp' => $hasil['nilai'],
        'steps' => $hasil,
        'domains' => $domains,
    ]);
}

public function updateStatus(Request $request, string $id)
{
    $request->validate([
        'status' => 'required|in:pending,diproses,dibatalkan',
        'jumlah_masuk' => 'required_if:status,diproses|nullable|integer|min:1',
        'tanggal_masuk' => 'required_if:status,diproses|nullable|date',
        'keterangan_masuk' => 'nullable|string',
    ]);

    $fuzzy = Fuzzy::findOrFail($id);
    $oldStatus = $fuzzy->status;
    $fuzzy->status = $request->status;
    $fuzzy->save();

    if ($request->status === 'diproses' && $oldStatus !== 'diproses') {
        \App\Models\TransaksiMasuk::create([
            'id_barang' => $fuzzy->id_barang,
            'tanggal'   => $request->tanggal_masuk ?? now()->toDateString(),
            'jumlah'    => $request->jumlah_masuk,
            'keterangan' => $request->keterangan_masuk ?: 'Pembelian dari rekomendasi Fuzzy (' . $fuzzy->barang->nama_barang . ')',
        ]);
        
        return redirect()->route('fuzzy.index')->with('success', 'Rekomendasi berhasil diproses dan transaksi masuk ditambahkan.');
    }

    return redirect()->route('fuzzy.index')->with('success', 'Status rekomendasi berhasil diperbarui.');
}
    public function getBarangDetail($id)
    {
        $barang = Barang::find($id);
        if (!$barang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Barang tidak ditemukan'
            ], 404);
        }

        $stockService = app(\App\Service\StockControlService::class);
        $stokAktual = $stockService->getStokAktual($id);
        $rataPenjualan = $stockService->hitungRataPenjualanHarian($id, 30);

        return response()->json([
            'status' => 'success',
            'stok_aktual' => $stokAktual,
            'rata_penjualan' => $rataPenjualan,
            'kategori' => $barang->kategori ?? '-',
            'satuan' => $barang->satuan ?? '-'
        ]);
    }


        private function getDynamicDomains($idBarang)
    {
        $maxPenjualan = \Illuminate\Support\Facades\DB::table('penjualan_agregat')
            ->where('id_barang', $idBarang)
            ->max('total_terjual') ?: 50;

        $maxStok = \Illuminate\Support\Facades\DB::table('penjualan_agregat')
            ->where('id_barang', $idBarang)
            ->max('stok_terakhir') ?: 100;

        $maxPembelian = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
            ->where('id_barang', $idBarang)
            ->max('jumlah') ?: 100;

        return [
            'penjualan' => ['min' => 0, 'max' => $maxPenjualan],
            'stok'      => ['min' => 0, 'max' => $maxStok],
            'pembelian' => ['min' => 0, 'max' => $maxPembelian]
        ];
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

      private function getDynamicMinMax($id_barang)
    {
        $masuk = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
            ->where('id_barang', $id_barang)
            ->get();
        $monthlyMasuk = $masuk->groupBy(function($item) {
            return date('Y-m', strtotime($item->tanggal));
        })->map(function($group) {
            return $group->sum('jumlah');
        });
        $minPembelian = $monthlyMasuk->isNotEmpty() ? $monthlyMasuk->min() : 0;
        $maxPembelian = $monthlyMasuk->isNotEmpty() ? $monthlyMasuk->max() : 100;

        $keluar = \Illuminate\Support\Facades\DB::table('transaksi_keluar')
            ->where('id_barang', $id_barang)
            ->get();
        $monthlyKeluar = $keluar->groupBy(function($item) {
            return date('Y-m', strtotime($item->tanggal));
        })->map(function($group) {
            return $group->sum('jumlah');
        });
        $minPenjualan = $monthlyKeluar->isNotEmpty() ? $monthlyKeluar->min() : 0;
        $maxPenjualan = $monthlyKeluar->isNotEmpty() ? $monthlyKeluar->max() : 100;

        $minStokAgregat = \Illuminate\Support\Facades\DB::table('penjualan_agregat')
            ->where('id_barang', $id_barang)
            ->min('stok_terakhir');
        $maxStokAgregat = \Illuminate\Support\Facades\DB::table('penjualan_agregat')
            ->where('id_barang', $id_barang)
            ->max('stok_terakhir');

        $minStokVal = $minStokAgregat !== null ? (int)$minStokAgregat : 0;
        $maxStokVal = $maxStokAgregat !== null ? (int)$maxStokAgregat : 100;

        return [
            'pembelian' => [
                'min' => (int) $minPembelian,
                'max' => (int) $maxPembelian,
            ],
            'penjualan' => [
                'min' => (int) $minPenjualan,
                'max' => (int) $maxPenjualan,
            ],
            'persediaan_akhir' => [
                'min' => $minStokVal,
                'max' => $maxStokVal,
            ],
            'persediaan_awal' => [
                'min' => $minStokVal,
                'max' => $maxStokVal,
            ],
        ];
    }

    private function getDynamicTrainingData($id_barang, $tahun, $bulan, $fallbackRataPenjualan, $fallbackStok)
    {
        $hasDataBulan = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
            ->where('id_barang', $id_barang)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->exists() || 
            \Illuminate\Support\Facades\DB::table('transaksi_keluar')
            ->where('id_barang', $id_barang)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->exists();

        if ($hasDataBulan) {
            $totalPembelianBulan = (int) \Illuminate\Support\Facades\DB::table('transaksi_masuk')
                ->where('id_barang', $id_barang)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->sum('jumlah');

            $totalPenjualanBulan = (int) \Illuminate\Support\Facades\DB::table('transaksi_keluar')
                ->where('id_barang', $id_barang)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->sum('jumlah');

            $startOfMonth = \Carbon\Carbon::create($tahun, $bulan, 1)->startOfMonth()->format('Y-m-d');
            $totalMasukSebelum = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
                ->where('id_barang', $id_barang)
                ->where('tanggal', '<', $startOfMonth)
                ->sum('jumlah') ?: 0;
            $totalKeluarSebelum = \Illuminate\Support\Facades\DB::table('transaksi_keluar')
                ->where('id_barang', $id_barang)
                ->where('tanggal', '<', $startOfMonth)
                ->sum('jumlah') ?: 0;

            $persediaanAwalBulan = $totalMasukSebelum - $totalKeluarSebelum;
            $persediaanAkhirBulan = $persediaanAwalBulan + $totalPembelianBulan - $totalPenjualanBulan;

            return [
                'has_data' => true,
                'permintaan' => $totalPenjualanBulan,
                'stok' => $persediaanAwalBulan,
                'training_data' => [
                    'pembelian' => $totalPembelianBulan,
                    'penjualan' => $totalPenjualanBulan,
                    'persediaan_akhir' => $persediaanAkhirBulan,
                    'persediaan_awal' => $persediaanAwalBulan,
                ]
            ];
        }

        // Fallback: get latest month that has transactions to serve as training data
        $latestMasuk = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
            ->where('id_barang', $id_barang)
            ->orderBy('tanggal', 'desc')
            ->first();
        $latestKeluar = \Illuminate\Support\Facades\DB::table('transaksi_keluar')
            ->where('id_barang', $id_barang)
            ->orderBy('tanggal', 'desc')
            ->first();

        $latestDate = null;
        if ($latestMasuk && $latestKeluar) {
            $latestDate = $latestMasuk->tanggal > $latestKeluar->tanggal ? $latestMasuk->tanggal : $latestKeluar->tanggal;
        } elseif ($latestMasuk) {
            $latestDate = $latestMasuk->tanggal;
        } elseif ($latestKeluar) {
            $latestDate = $latestKeluar->tanggal;
        }

        if ($latestDate) {
            $lTahun = date('Y', strtotime($latestDate));
            $lBulan = date('n', strtotime($latestDate));

            $lPembelian = (int) \Illuminate\Support\Facades\DB::table('transaksi_masuk')
                ->where('id_barang', $id_barang)
                ->whereYear('tanggal', $lTahun)
                ->whereMonth('tanggal', $lBulan)
                ->sum('jumlah');

            $lPenjualan = (int) \Illuminate\Support\Facades\DB::table('transaksi_keluar')
                ->where('id_barang', $id_barang)
                ->whereYear('tanggal', $lTahun)
                ->whereMonth('tanggal', $lBulan)
                ->sum('jumlah');

            $lStartOfMonth = \Carbon\Carbon::create($lTahun, $lBulan, 1)->startOfMonth()->format('Y-m-d');
            $lMasukSebelum = \Illuminate\Support\Facades\DB::table('transaksi_masuk')
                ->where('id_barang', $id_barang)
                ->where('tanggal', '<', $lStartOfMonth)
                ->sum('jumlah') ?: 0;
            $lKeluarSebelum = \Illuminate\Support\Facades\DB::table('transaksi_keluar')
                ->where('id_barang', $id_barang)
                ->where('tanggal', '<', $lStartOfMonth)
                ->sum('jumlah') ?: 0;

            $lPersediaanAwal = $lMasukSebelum - $lKeluarSebelum;
            $lPersediaanAkhir = $lPersediaanAwal + $lPembelian - $lPenjualan;

            return [
                'has_data' => false,
                'permintaan' => $fallbackRataPenjualan,
                'stok' => $fallbackStok,
                'training_data' => [
                    'pembelian' => $lPembelian,
                    'penjualan' => $lPenjualan,
                    'persediaan_akhir' => $lPersediaanAkhir,
                    'persediaan_awal' => $lPersediaanAwal,
                ]
            ];
        }

        return [
            'has_data' => false,
            'permintaan' => $fallbackRataPenjualan,
            'stok' => $fallbackStok,
            'training_data' => [
                'pembelian' => 0,
                'penjualan' => (int) $fallbackRataPenjualan,
                'persediaan_akhir' => $fallbackStok,
                'persediaan_awal' => $fallbackStok,
            ]
        ];
    }


    public function store(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'stok_sekarang' => 'nullable|numeric',
            'tanggal' => 'required|date',
            'rata2_penjualan' => 'required|numeric',
        ]);
// dd($request->rata2_penjualan);


        // HITUNG FUZZY TSUKAMOTO (9 RULES)
        $hasil = $this->hitungFuzzyTsukamoto(
            $request->stok_sekarang,
            $request->rata2_penjualan,
            $request->id_barang
        );


        $permintaanBulat = (int) round($hasil['nilai']);

        $data = [
            'id_barang' => $request->id_barang,
            'permintaan' => $permintaanBulat, // Rata-rata Penjualan
            'stok' => $request->stok_sekarang,             // Stok Sekarang
            'hasil_fuzzy' => $hasil['kategori'],
            'nilai_crisp' => $hasil['nilai'],
            'tanggal' => $request->tanggal,
            'rata_rata_penjualan_perhari'=>$request->rata2_penjualan
        ];

        Fuzzy::create($data);

        return redirect()
            ->route('fuzzy.index')
            ->with('success', 'Data fuzzy berhasil ditambahkan');
    }
private function hitungFuzzyTsukamoto(
    $stok_sekarang,
    $rata2_penjualan,
    $id_barang
)
{
    // =====================================================
    // INPUT
    // =====================================================

    $stok = $stok_sekarang;


    // =====================================================
    // AMBIL DATA HISTORIS
    // =====================================================

    $dataKeluar = TransaksiKeluar::where('id_barang', $id_barang);
    $dataMasuk = TransaksiMasuk::where('id_barang', $id_barang);
    $dataStok = PenjualanAgregat::where('id_barang', $id_barang);

    $minPenjualan = $dataKeluar->min('jumlah');
    $maxPenjualan = $dataKeluar->max('jumlah');

    $minStok = $dataStok->min('stok_terakhir');
    $maxStok = $dataStok->max('stok_terakhir');

    $minPembelian = $dataMasuk->min('jumlah');
    $maxPembelian = $dataMasuk->max('jumlah');

    // Antisipasi jika data kosong

    $minPenjualan = $minPenjualan ?? 0;
    $maxPenjualan = $maxPenjualan ?? 1;

    $minStok = $minStok ?? 0;
    $maxStok = $maxStok ?? 1;

    $minPembelian = $minPembelian ?? 0;
    $maxPembelian = $maxPembelian ?? 1;


// dd([
//     'minPembelian' => $minPembelian,
//     'maxPembelian' => $maxPembelian,
//     'minStok' => $minStok,
//     'maxStok' => $maxStok,
//     'minPenjualan' => $minPenjualan,
//     'maxPenjualan' => $maxPenjualan,
// ]);
    // =====================================================
    // TITIK TENGAH
    // =====================================================

    $tengahPenjualan =
        ($minPenjualan + $maxPenjualan) / 2;

    $tengahStok =
        ($minStok + $maxStok) / 2;

    $tengahPembelian =
        ($minPembelian + $maxPembelian) / 2;

    // =====================================================
    // FUZZIFIKASI PENJUALAN
    // =====================================================

    $penjualanSedikit =
        $this->turun(
            $rata2_penjualan,
            $minPenjualan,
            $tengahPenjualan
        );

    $penjualanSedang =
        $this->segitiga(
            $rata2_penjualan,
            $minPenjualan,
            $tengahPenjualan,
            $maxPenjualan
        );

    $penjualanBanyak =
        $this->naik(
            $rata2_penjualan,
            $tengahPenjualan,
            $maxPenjualan
        );

    // =====================================================
    // FUZZIFIKASI STOK
    // =====================================================

    $stokSedikit =
        $this->turun(
            $stok,
            $minStok,
            $tengahStok
        );

    $stokSedang =
        $this->segitiga(
            $stok,
            $minStok,
            $tengahStok,
            $maxStok
        );

    $stokBanyak =
        $this->naik(
            $stok,
            $tengahStok,
            $maxStok
        );

    // =====================================================
    // RULE
    // =====================================================

    // R1
    $alpha1 =
        min($penjualanSedikit, $stokSedikit);

    // R2
    $alpha2 =
        min($penjualanSedikit, $stokSedang);

    // R3
    $alpha3 =
        min($penjualanSedikit, $stokBanyak);

    // R4
    $alpha4 =
        min($penjualanSedang, $stokSedikit);

    // R5
    $alpha5 =
        min($penjualanSedang, $stokSedang);

    // R6
    $alpha6 =
        min($penjualanSedang, $stokBanyak);

    // R7
    $alpha7 =
        min($penjualanBanyak, $stokSedikit);

    // R8
    $alpha8 =
        min($penjualanBanyak, $stokSedang);

    // R9
    $alpha9 =
        min($penjualanBanyak, $stokBanyak);

    // =====================================================
    // Z TSUKAMOTO
    // =====================================================

// =====================================================
// DOMAIN OUTPUT PEMBELIAN
// =====================================================

$rangePembelian =
    $maxPembelian - $minPembelian;

$b1 =
    $minPembelian + ($rangePembelian * 0.25);

$b2 =
    $minPembelian + ($rangePembelian * 0.50);

$b3 =
    $minPembelian + ($rangePembelian * 0.75);

/*

Contoh:

min = 9
max = 34

b1 = 15.25
b2 = 21.50
b3 = 27.75

9 -----15.25-----21.50-----27.75-----34

Sedikit
Sedang-Sedikit
Sedang-Banyak
Banyak

*/

// =====================================================
// Z TSUKAMOTO
// =====================================================

    // R1
    $z1 =
        $this->zSedangBanyak(
            $alpha1,
            $b2,
            $b3
        );

    // R2
    $z2 =
        $this->zSedangSedikit(
            $alpha2,
            $b1,
            $b2
        );

    // R3
    $z3 =
        $this->zSedikit(
            $alpha3,
            $minPembelian,
            $b1
        );

    // R4
    $z4 =
        $this->zBanyak(
            $alpha4,
            $b3,
            $maxPembelian
        );

    // R5
    $z5 =
        $this->zSedangBanyak(
            $alpha5,
            $b2,
            $b3
        );

    // R6
    $z6 =
        $this->zSedangSedikit(
            $alpha6,
            $b1,
            $b2
        );

    // R7
    $z7 =
        $this->zBanyak(
            $alpha7,
            $b3,
            $maxPembelian
        );

    // R8
    $z8 =
        $this->zBanyak(
            $alpha8,
            $b3,
            $maxPembelian
        );

    // R9
    $z9 =
        $this->zSedangBanyak(
            $alpha9,
            $b2,
            $b3
        );

    // =====================================================
    // DEFUZZIFIKASI
    // =====================================================

    $pembilang =
        ($alpha1 * $z1) +
        ($alpha2 * $z2) +
        ($alpha3 * $z3) +
        ($alpha4 * $z4) +
        ($alpha5 * $z5) +
        ($alpha6 * $z6) +
        ($alpha7 * $z7) +
        ($alpha8 * $z8) +
        ($alpha9 * $z9);

    $penyebut =
        $alpha1 +
        $alpha2 +
        $alpha3 +
        $alpha4 +
        $alpha5 +
        $alpha6 +
        $alpha7 +
        $alpha8 +
        $alpha9;

    $hasilCrisp =
        $penyebut > 0
            ? $pembilang / $penyebut
            : 0;

    // =====================================================
    // KATEGORI
    // =====================================================

 if ($hasilCrisp <= $b1) {

    $kategori = 'Sedikit';

} elseif ($hasilCrisp <= $b2) {

    $kategori = 'Sedang-Sedikit';

} elseif ($hasilCrisp <= $b3) {

    $kategori = 'Sedang-Banyak';

} else {

    $kategori = 'Banyak';
}
    // Formula Penjualan Sedikit
    if ($rata2_penjualan <= $minPenjualan) {
        $penjualanSedikitFormula = "1";
    } elseif ($rata2_penjualan >= $tengahPenjualan) {
        $penjualanSedikitFormula = "0";
    } else {
        $penjualanSedikitFormula = sprintf("(%.2f - %.2f) / (%.2f - %.2f)", $tengahPenjualan, $rata2_penjualan, $tengahPenjualan, $minPenjualan);
    }

    // Formula Penjualan Sedang
    if ($rata2_penjualan <= $minPenjualan || $rata2_penjualan >= $maxPenjualan) {
        $penjualanSedangFormula = "0";
    } elseif ($rata2_penjualan == $tengahPenjualan) {
        $penjualanSedangFormula = "1";
    } elseif ($rata2_penjualan < $tengahPenjualan) {
        $penjualanSedangFormula = sprintf(
            "(%.2f - %.2f) / (%.2f - %.2f)",
            $rata2_penjualan,
            $minPenjualan,
            $tengahPenjualan,
            $minPenjualan
        );  
    } else {
        $penjualanSedangFormula = sprintf("(%.2f - %.2f) / (%.2f - %.2f)", $maxPenjualan, $rata2_penjualan, $maxPenjualan, $tengahPenjualan);
    }

    // Formula Penjualan Banyak
    if ($rata2_penjualan <= $tengahPenjualan) {
        $penjualanBanyakFormula = "0";
    } elseif ($rata2_penjualan >= $maxPenjualan) {
        $penjualanBanyakFormula = "1";
    } else {
        $penjualanBanyakFormula = sprintf("(%.2f - %.2f) / (%.2f - %.2f)", $rata2_penjualan, $tengahPenjualan, $maxPenjualan, $tengahPenjualan);
    }

    // Formula Stok Sedikit
    if ($stok <= $minStok) {
        $stokSedikitFormula = "1";
    } elseif ($stok >= $tengahStok) {
        $stokSedikitFormula = "0";
    } else {
        $stokSedikitFormula = sprintf("(%.2f - %.2f) / (%.2f - %.2f)", $tengahStok, $stok, $tengahStok, $minStok);
    }

    // Formula Stok Sedang
    if ($stok <= $minStok || $stok >= $maxStok) {
        $stokSedangFormula = "0";
    } elseif ($stok == $tengahStok) {
        $stokSedangFormula = "1";
    } elseif ($stok < $tengahStok) {
        $stokSedangFormula = sprintf("(%.2f - %.2f) / (%.2f - %.2f)", $stok, $minStok, $tengahStok, $minStok);
    } else {
        $stokSedangFormula = sprintf("(%.2f - %.2f) / (%.2f - %.2f)", $maxStok, $stok, $maxStok, $tengahStok);
    }

    // Formula Stok Banyak
    if ($stok <= $tengahStok) {
        $stokBanyakFormula = "0";
    } elseif ($stok >= $maxStok) {
        $stokBanyakFormula = "1";
    } else {
        $stokBanyakFormula = sprintf("(%.2f - %.2f) / (%.2f - %.2f)", $stok, $tengahStok, $maxStok, $tengahStok);
    }

    return [
        'nilai' => round($hasilCrisp, 2),
        'kategori' => $kategori,
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
            ['name' => 'R1', 'text' => 'IF Penjualan Sedikit AND Stok Sedikit THEN Pembelian Sedang-Banyak', 'alpha' => round($alpha1, 4), 'z' => round($z1, 2)],
            ['name' => 'R2', 'text' => 'IF Penjualan Sedikit AND Stok Sedang THEN Pembelian Sedang-Sedikit', 'alpha' => round($alpha2, 4), 'z' => round($z2, 2)],
            ['name' => 'R3', 'text' => 'IF Penjualan Sedikit AND Stok Banyak THEN Pembelian Sedikit', 'alpha' => round($alpha3, 4), 'z' => round($z3, 2)],
            ['name' => 'R4', 'text' => 'IF Penjualan Sedang AND Stok Sedikit THEN Pembelian Banyak', 'alpha' => round($alpha4, 4), 'z' => round($z4, 2)],
            ['name' => 'R5', 'text' => 'IF Penjualan Sedang AND Stok Sedang THEN Pembelian Sedang-Banyak', 'alpha' => round($alpha5, 4), 'z' => round($z5, 2)],
            ['name' => 'R6', 'text' => 'IF Penjualan Sedang AND Stok Banyak THEN Pembelian Sedang-Sedikit', 'alpha' => round($alpha6, 4), 'z' => round($z6, 2)],
            ['name' => 'R7', 'text' => 'IF Penjualan Banyak AND Stok Sedikit THEN Pembelian Banyak', 'alpha' => round($alpha7, 4), 'z' => round($z7, 2)],
            ['name' => 'R8', 'text' => 'IF Penjualan Banyak AND Stok Sedang THEN Pembelian Banyak', 'alpha' => round($alpha8, 4), 'z' => round($z8, 2)],
            ['name' => 'R9', 'text' => 'IF Penjualan Banyak AND Stok Banyak THEN Pembelian Sedang-Banyak', 'alpha' => round($alpha9, 4), 'z' => round($z9, 2)],
        ],
        'defuzzifikasi' => [
            'pembilang' => round($pembilang, 4),
            'penyebut' => round($penyebut, 4),
            'hasil_crisp' => round($hasilCrisp, 4)
        ]
    ];
}

// =====================================================
// FUNGSI KEANGGOTAAN
// =====================================================

private function turun($x, $min, $max)
{
    if ($x <= $min) {
        return 1;
    }

    if ($x >= $max) {
        return 0;
    }

    return ($max - $x) / ($max - $min);
}

private function naik($x, $min, $max)
{
    if ($x <= $min) {
        return 0;
    }

    if ($x >= $max) {
        return 1;
    }

    return ($x - $min) / ($max - $min);
}

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
  

private function zSedikit($alpha, $min, $b1)
{
    return $b1 - ($alpha * ($b1 - $min));
}
private function zSedangSedikit($alpha, $b1, $b2)
{
    return $b1 + ($alpha * ($b2 - $b1));
}
private function zSedangBanyak($alpha, $b2, $b3)
{
    return $b2 + ($alpha * ($b3 - $b2));
}
private function zBanyak($alpha, $b3, $max)
{
    return $b3 + ($alpha * ($max - $b3));
}
}