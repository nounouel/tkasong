@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Edit Barang
            </h5>

            <a href="{{ route('barang.index') }}"
               class="btn btn-primary">
                Kembali
            </a>
        </div>

        <form action="{{ route('barang.update', $barang->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-5">
                <label for="nama_barang" class="dark:text-white-light">Nama Barang</label>
                <input id="nama_barang" type="text" name="nama_barang" value="{{ $barang->nama_barang }}" class="form-input" placeholder="Masukkan Nama Barang" required />
            </div>

            <button type="submit" class="btn btn-primary !mt-6">Update</button>
        </form>

    </div>

</div>
@endsection
