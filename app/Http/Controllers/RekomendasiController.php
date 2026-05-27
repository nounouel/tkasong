<?php

namespace App\Http\Controllers;

use App\Service\StockControlService;
use App\Models\Rekomendasi;
use Illuminate\Http\Request;

class RekomendasiController extends Controller
{
    protected $stockService;

    public function __construct(StockControlService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Tampilkan semua rekomendasi yang pending
     */
    public function index()
    {
        $rekomendasi = Rekomendasi::with('barang')
            ->where('status', 'pending')
            ->orderBy('dihasilkan_pada', 'desc')
            ->get();

        return view('rekomendasi.index', compact('rekomendasi'));
    }

    /**
     * API: cek semua barang dan hasil rekomendasi terbaru
     */
    public function cekSemua(Request $request)
    {
        $rekomendasi = $this->stockService->cekSemuaBarang();

        return response()->json([
            'success' => true,
            'data' => $rekomendasi
        ]);
    }

    /**
     * Cek satu barang saja (misal via AJAX)
     */
    public function cekBarang($id)
    {
        $barang = Barang::findOrFail($id);
        $hasil = $this->stockService->cekDanRekomendasi($barang);

        return response()->json($hasil);
    }

    /**
     * Konfirmasi rekomendasi (misal sudah dipesan)
     */
    public function konfirmasi($id)
    {
        $rekom = Rekomendasi::findOrFail($id);
        $rekom->status = 'diproses';
        $rekom->save();

        return redirect()->back()->with('success', 'Rekomendasi ditandai diproses.');
    }

    /**
     * Batalkan rekomendasi
     */
    public function batalkan($id)
    {
        $rekom = Rekomendasi::findOrFail($id);
        $rekom->status = 'dibatalkan';
        $rekom->save();

        return redirect()->back()->with('success', 'Rekomendasi dibatalkan.');
    }
}