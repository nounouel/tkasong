@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Tambah Barang
            </h5>

            <a href="{{ route('barang.index') }}"
               class="btn btn-primary">
                Kembali
            </a>
        </div>

        <form action="{{ route('barang.store') }}" method="POST">
            @csrf
            <div class="mb-5">
                <label for="nama_barang" class="dark:text-white-light">Nama Barang</label>
                <input id="nama_barang" type="text" name="nama_barang" class="form-input" placeholder="Masukkan Nama Barang" required />
            </div>

            <div class="mb-5">
                <label for="satuan" class="dark:text-white-light">Satuan</label>
                <select id="satuan" name="satuan" class="form-select text-white-dark" required>
                    <option value="">Pilih Satuan</option>
                    <option value="kg">kg</option>
                    <option value="liter">liter</option>
                    <option value="pcs">pcs</option>
                </select>
            </div>

            <div class="mb-5">
                <label for="stok_minimum" class="dark:text-white-light">Batas Stok Minimum</label>
                <input id="stok_minimum" type="number" name="stok_minimum" class="form-input" placeholder="Contoh: 10" min="0" value="10" required />
            </div>

            <button type="submit" class="btn btn-primary !mt-6">Simpan</button>
        </form>

    </div>

</div>
@endsection
