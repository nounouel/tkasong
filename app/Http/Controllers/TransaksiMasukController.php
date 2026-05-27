<?php

namespace App\Http\Controllers;

use App\Models\TransaksiMasuk;
use App\Models\Barang;
use Illuminate\Http\Request;

class TransaksiMasukController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $transaksiMasuk = TransaksiMasuk::with('barang')
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

        return view('dashboard.transaksi_masuk.index', compact('transaksiMasuk'));
    }

    public function create()
    {
        $barang = Barang::all();
        return view('dashboard.transaksi_masuk.create', compact('barang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'tanggal'   => 'required|date',
            'jumlah'    => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        TransaksiMasuk::create($request->all());

        return redirect()->route('transaksi-masuk.index')->with('success', 'Transaksi masuk berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $transaksiMasuk = TransaksiMasuk::findOrFail($id);
        $barang = Barang::all();
        return view('dashboard.transaksi_masuk.edit', compact('transaksiMasuk', 'barang'));
    }

    public function update(Request $request, $id)
    {
        $transaksiMasuk = TransaksiMasuk::findOrFail($id);

        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'tanggal'   => 'required|date',
            'jumlah'    => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $transaksiMasuk->update($request->all());

        return redirect()->route('transaksi-masuk.index')->with('success', 'Transaksi masuk berhasil diperbarui.');
    }

    public function destroy($id)
    {
        TransaksiMasuk::destroy($id);
        return redirect()->route('transaksi-masuk.index')->with('success', 'Transaksi masuk berhasil dihapus.');
    }
}
