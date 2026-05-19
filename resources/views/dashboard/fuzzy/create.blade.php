@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Tambah Data Fuzzy
            </h5>

            <a href="{{ route('fuzzy.index') }}" class="btn btn-primary">
                Kembali
            </a>
        </div>

        <form action="{{ route('fuzzy.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="mb-5">
                    <label for="id_barang" class="dark:text-white-light">Pilih Barang</label>
                    <select id="id_barang" name="id_barang" class="form-select text-white-dark" required>
                        <option value="">Pilih Barang</option>
                        @foreach ($barang as $item)
                            <option value="{{ $item->id }}">{{ $item->nama_barang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-5">
                    <label for="tanggal" class="dark:text-white-light">Tanggal</label>
                    <input id="tanggal" type="date" name="tanggal" class="form-input" required />
                </div>
                <div class="mb-5">
                    <label for="permintaan" class="dark:text-white-light">Permintaan</label>
                    <input id="permintaan" type="number" name="permintaan" class="form-input" placeholder="0" required />
                </div>
                <div class="mb-5">
                    <label for="stok" class="dark:text-white-light">Stok</label>
                    <input id="stok" type="number" name="stok" class="form-input" placeholder="0" required />
                </div>
                <div class="mb-5 md:col-span-2">
                    <label for="hasil_fuzzy" class="dark:text-white-light">Hasil Fuzzy</label>
                    <input id="hasil_fuzzy" type="text" name="hasil_fuzzy" class="form-input" placeholder="Hasil perhitungan" required />
                </div>
            </div>

            <button type="submit" class="btn btn-primary !mt-6">Simpan</button>
        </form>

    </div>

</div>
@endsection
