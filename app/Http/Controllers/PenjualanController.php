<?php

namespace App\Http\Controllers;

use App\Models\TransaksiKeluar;
use App\Models\Barang;
use App\Service\StockControlService;
use Illuminate\Http\Request;

class PenjualanController extends Controller
{
    protected $stockService;

    public function __construct(StockControlService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Mencatat transaksi penjualan (detail)
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'jumlah' => 'required|integer|min:1',
            'tanggal' => 'required|date',
        ]);

        $penjualan = TransaksiKeluar::create([
            'id_barang' => $request->id_barang,
            'jumlah' => $request->jumlah,
            'tanggal' => $request->tanggal,
        ]);

        // Opsional: langsung panggil agregasi ulang untuk hari ini
        // $this->stockService->agregasiPenjualanHarian($request->tanggal);

        return response()->json(['success' => true, 'data' => $penjualan]);
    }
}