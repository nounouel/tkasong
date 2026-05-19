<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Traning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // --- Indikator 1: Stok barang di bawah batas minimum ---
        // Menggunakan persediaan_akhir terbaru per barang dari tabel traning
        $stokTerkini = Traning::select('id_barang', DB::raw('MAX(id) as latest_id'))
            ->groupBy('id_barang');

        $barangBawahMinimum = Barang::select('barang.*', 'traning.persediaan_akhir')
            ->joinSub($stokTerkini, 'latest', function ($join) {
                $join->on('barang.id', '=', 'latest.id_barang');
            })
            ->join('traning', 'traning.id', '=', 'latest.latest_id')
            ->whereColumn('traning.persediaan_akhir', '<', 'barang.stok_minimum')
            ->get();

        $jumlahBawahMinimum = $barangBawahMinimum->count();

        // --- Indikator 2: Total stok masuk (pembelian) dan keluar (penjualan) ---
        $totalMasuk  = Traning::sum('pembelian');
        $totalKeluar = Traning::sum('penjualan');

        // --- Grafik: Top 5 barang yang paling menipis (persediaan_akhir terkecil) ---
        $top5Menipis = Barang::select('barang.id', 'barang.nama_barang', 'barang.stok_minimum', 'traning.persediaan_akhir')
            ->joinSub($stokTerkini, 'latest', function ($join) {
                $join->on('barang.id', '=', 'latest.id_barang');
            })
            ->join('traning', 'traning.id', '=', 'latest.latest_id')
            ->orderBy('traning.persediaan_akhir', 'asc')
            ->limit(5)
            ->get();

        return view('dashboard.home', compact(
            'barangBawahMinimum',
            'jumlahBawahMinimum',
            'totalMasuk',
            'totalKeluar',
            'top5Menipis'
        ));
    }
}
