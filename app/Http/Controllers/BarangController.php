<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $barang = Barang::with('kategoriRelation')->when($search, function ($query, $search) {
            return $query->where('nama_barang', 'like', "%{$search}%");
        })->paginate(10)->withQueryString();

        return view('dashboard.barang.index', compact('barang'));
    }
    public function create()
    {
        $kategori = \App\Models\Kategori::all();
        return view('dashboard.barang.create', compact('kategori'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_barang'   => 'required|string|max:100',
            'id_kategori'   => 'nullable|exists:kategori,id',
            'satuan'        => 'required|string|max:20',
            'stok_minimum'  => 'required|integer|min:0',
        ]);

        Barang::create([
            'nama_barang'   => $request->nama_barang,
            'id_kategori'   => $request->id_kategori,
            'satuan'        => $request->satuan,
            'stok_minimum'  => $request->stok_minimum,
        ]);

        return redirect()->route('barang.index');
    }

    public function edit($id)
    {
        $barang = Barang::findOrFail($id);
        $kategori = \App\Models\Kategori::all();
        return view('dashboard.barang.edit', compact('barang', 'kategori'));
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);

        $request->validate([
            'nama_barang'   => 'required|string|max:100',
            'id_kategori'   => 'nullable|exists:kategori,id',
            'satuan'        => 'required|string|max:20',
            'stok_minimum'  => 'required|integer|min:0',
        ]);

        $barang->update([
            'nama_barang'   => $request->nama_barang,
            'id_kategori'   => $request->id_kategori,
            'satuan'        => $request->satuan,
            'stok_minimum'  => $request->stok_minimum,
        ]);

        return redirect()->route('barang.index');
    }

    public function destroy($id)
    {
        Barang::destroy($id);
        return redirect()->route('barang.index');
    }
}