<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\TransaksiMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Subqueries to sum incoming and outgoing transactions per goods
        $stokMasuk = DB::table('transaksi_masuk')
            ->select('id_barang', DB::raw('SUM(jumlah) as total_masuk'))
            ->groupBy('id_barang');

        $stokKeluar = DB::table('transaksi_keluar')
            ->select('id_barang', DB::raw('SUM(jumlah) as total_keluar'))
            ->groupBy('id_barang');

        // --- Indikator 1: Stok barang di bawah batas minimum ---
        $barangBawahMinimumQuery = Barang::leftJoinSub($stokMasuk, 'masuk', 'barang.id', '=', 'masuk.id_barang')
            ->leftJoinSub($stokKeluar, 'keluar', 'barang.id', '=', 'keluar.id_barang')
            ->select('barang.*')
            ->selectRaw('CAST(COALESCE(masuk.total_masuk, 0) - COALESCE(keluar.total_keluar, 0) AS SIGNED) as persediaan_akhir')
            ->whereRaw('CAST(COALESCE(masuk.total_masuk, 0) - COALESCE(keluar.total_keluar, 0) AS SIGNED) < barang.stok_minimum');

        $jumlahBawahMinimum = $barangBawahMinimumQuery->count();
        $barangBawahMinimum = $barangBawahMinimumQuery->paginate(5);

        // --- Indikator 2: Total stok masuk (pembelian) dan keluar (penjualan) ---
        $totalMasuk  = DB::table('transaksi_masuk')->sum('jumlah');
        $totalKeluar = DB::table('transaksi_keluar')->sum('jumlah');

        // --- Grafik: Top 5 barang yang paling menipis (persediaan_akhir terkecil) ---
        $top5Menipis = Barang::with('kategoriRelation')
            ->leftJoinSub($stokMasuk, 'masuk', 'barang.id', '=', 'masuk.id_barang')
            ->leftJoinSub($stokKeluar, 'keluar', 'barang.id', '=', 'keluar.id_barang')
            ->select('barang.*')
            ->selectRaw('CAST(COALESCE(masuk.total_masuk, 0) - COALESCE(keluar.total_keluar, 0) AS SIGNED) as persediaan_akhir')
            ->orderBy('persediaan_akhir', 'asc')
            ->limit(5)
            ->get();

        // --- Grafik: Top 5 barang paling banyak terjual ---
        $top5Terjual = Barang::with('kategoriRelation')
            ->joinSub($stokKeluar, 'keluar', 'barang.id', '=', 'keluar.id_barang')
            ->select('barang.*')
            ->selectRaw('CAST(keluar.total_keluar AS SIGNED) as total_terjual')
            ->orderBy('total_terjual', 'desc')
            ->limit(5)
            ->get();

        // --- Indikator 3: Barang dengan Expired Date (Transaksi Masuk) ---
        $transaksiExpiredQuery = TransaksiMasuk::with('barang.kategoriRelation')
            ->whereNotNull('expired_date')
            ->orderBy('expired_date', 'asc');

        $jumlahKadaluarsa = TransaksiMasuk::whereNotNull('expired_date')
            ->where('expired_date', '<', now()->toDateString())
            ->count();

        $jumlahHampirKadaluarsa = TransaksiMasuk::whereNotNull('expired_date')
            ->whereBetween('expired_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->count();

        $transaksiExpired = $transaksiExpiredQuery->paginate(5, ['*'], 'expired_page');

        return view('dashboard.home', compact(
            'barangBawahMinimum',
            'jumlahBawahMinimum',
            'totalMasuk',
            'totalKeluar',
            'top5Menipis',
            'top5Terjual',
            'transaksiExpired',
            'jumlahKadaluarsa',
            'jumlahHampirKadaluarsa'
        ));
    }
}

