<?php

namespace App\Http\Controllers;

use App\Models\TransaksiKeluar;
use App\Models\Barang;
use Illuminate\Http\Request;

class TransaksiKeluarController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $transaksiKeluar = TransaksiKeluar::with('barang')
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

        return view('dashboard.transaksi_keluar.index', compact('transaksiKeluar'));
    }

    public function create()
    {
        $barang = Barang::all();
        return view('dashboard.transaksi_keluar.create', compact('barang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'tanggal'   => 'required|date',
            'jumlah'    => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        TransaksiKeluar::create($request->all());

        return redirect()->route('transaksi-keluar.index')->with('success', 'Transaksi keluar berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $transaksiKeluar = TransaksiKeluar::findOrFail($id);
        $barang = Barang::all();
        return view('dashboard.transaksi_keluar.edit', compact('transaksiKeluar', 'barang'));
    }

    public function update(Request $request, $id)
    {
        $transaksiKeluar = TransaksiKeluar::findOrFail($id);

        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'tanggal'   => 'required|date',
            'jumlah'    => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $transaksiKeluar->update($request->all());

        return redirect()->route('transaksi-keluar.index')->with('success', 'Transaksi keluar berhasil diperbarui.');
    }

    public function destroy($id)
    {
        TransaksiKeluar::destroy($id);
        return redirect()->route('transaksi-keluar.index')->with('success', 'Transaksi keluar berhasil dihapus.');
    }
}
