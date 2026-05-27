<?php

namespace App\Http\Controllers;

use App\Models\PenjualanAgregat;
use Illuminate\Http\Request;

class PenjualanAgregatController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $penjualanAgregat = PenjualanAgregat::with('barang')
            ->when($search, function ($query, $search) {
                return $query->whereHas('barang', function ($q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%");
                });
            })
            ->when($start_date, function ($query, $start_date) {
                return $query->whereDate('tanggal', '>=', $start_date);
            })
            ->when($end_date, function ($query, $end_date) {
                return $query->whereDate('tanggal', '<=', $end_date);
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('dashboard.penjualan_agregat.index', compact('penjualanAgregat'));
    }
}
