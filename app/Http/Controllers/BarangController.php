<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $barang = Barang::when($search, function ($query, $search) {
            return $query->where('nama_barang', 'like', "%{$search}%");
        })->paginate(10)->withQueryString();

        return view('dashboard.barang.index', compact('barang'));
    }
    public function create()
    {
        return view('dashboard.barang.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_barang'   => 'required|string|max:100',
            'satuan'        => 'required|string|max:20',
            'stok_minimum'  => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
        ]);

        Barang::create([
            'nama_barang'   => $request->nama_barang,
            'satuan'        => $request->satuan,
            'stok_minimum'  => $request->stok_minimum,
            'reorder_point' => $request->reorder_point,
        ]);

        return redirect()->route('barang.index');
    }

    public function edit($id)
    {
        $barang = Barang::findOrFail($id);
        return view('dashboard.barang.edit', compact('barang'));
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);

        $request->validate([
            'nama_barang'   => 'required|string|max:100',
            'satuan'        => 'required|string|max:20',
            'stok_minimum'  => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
        ]);

        $barang->update([
            'nama_barang'   => $request->nama_barang,
            'satuan'        => $request->satuan,
            'stok_minimum'  => $request->stok_minimum,
            'reorder_point' => $request->reorder_point,
        ]);

        return redirect()->route('barang.index');
    }

    public function destroy($id)
    {
        Barang::destroy($id);
        return redirect()->route('barang.index');
    }
}