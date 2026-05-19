<?php

namespace App\Http\Controllers;

use App\Models\Traning;
use App\Models\Barang;
use Illuminate\Http\Request;

class TraningController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $traning = Traning::with('barang')->get();
        return view('dashboard.traning.index', compact('traning'));
    }

    public function create()
    {
        $barang = Barang::all();
        return view('dashboard.traning.create', compact('barang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'tanggal' => 'required|date',
            'persediaan_awal' => 'required|integer',
            'pembelian' => 'required|integer',
            'penjualan' => 'required|integer',
        ]);

        $persediaan_akhir = $request->persediaan_awal + $request->pembelian - $request->penjualan;

        Traning::create([
            'id_barang' => $request->id_barang,
            'tanggal' => $request->tanggal,
            'persediaan_awal' => $request->persediaan_awal,
            'pembelian' => $request->pembelian,
            'penjualan' => $request->penjualan,
            'persediaan_akhir' => $persediaan_akhir,
        ]);

        return redirect()->route('traning.index')->with('success', 'Data traning berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $traning = Traning::findOrFail($id);
        $barang = Barang::all();
        return view('dashboard.traning.edit', compact('traning', 'barang'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'id_barang' => 'required|exists:barang,id',
            'tanggal' => 'required|date',
            'persediaan_awal' => 'required|integer',
            'pembelian' => 'required|integer',
            'penjualan' => 'required|integer',
        ]);

        $traning = Traning::findOrFail($id);
        $persediaan_akhir = $request->persediaan_awal + $request->pembelian - $request->penjualan;

        $traning->update([
            'id_barang' => $request->id_barang,
            'tanggal' => $request->tanggal,
            'persediaan_awal' => $request->persediaan_awal,
            'pembelian' => $request->pembelian,
            'penjualan' => $request->penjualan,
            'persediaan_akhir' => $persediaan_akhir,
        ]);

        return redirect()->route('traning.index')->with('success', 'Data traning berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        Traning::destroy($id);
        return redirect()->route('traning.index')->with('success', 'Data traning berhasil dihapus.');
    }
}
