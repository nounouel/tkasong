<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
public function index()
{
    $barang = Barang::all();

    return view('dashboard.barang.index', compact('barang'));
}
    public function create()
    {
        return view('dashboard.barang.create');
    }

    public function store(Request $request)
    {
        Barang::create([
            'nama_barang' => $request->nama_barang
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

        $barang->update([
            'nama_barang' => $request->nama_barang
        ]);

        return redirect()->route('barang.index');
    }

    public function destroy($id)
    {
        Barang::destroy($id);
        return redirect()->route('barang.index');
    }
}