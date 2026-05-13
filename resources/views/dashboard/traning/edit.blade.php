@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Edit Traning
            </h5>

            <a href="{{ route('traning.index') }}" class="btn btn-primary">
                Kembali
            </a>
        </div>

        <form action="{{ route('traning.update', $traning->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="mb-5">
                    <label for="id_barang" class="dark:text-white-light">Pilih Barang</label>
                    <select id="id_barang" name="id_barang" class="form-select text-white-dark" required>
                        <option value="">Pilih Barang</option>
                        @foreach ($barang as $item)
                            <option value="{{ $item->id }}" {{ $traning->id_barang == $item->id ? 'selected' : '' }}>
                                {{ $item->nama_barang }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-5">
                    <label for="persediaan_awal" class="dark:text-white-light">Persediaan Awal</label>
                    <input id="persediaan_awal" type="number" name="persediaan_awal" value="{{ $traning->persediaan_awal }}" class="form-input" placeholder="0" required />
                </div>
                <div class="mb-5">
                    <label for="pembelian" class="dark:text-white-light">Pembelian</label>
                    <input id="pembelian" type="number" name="pembelian" value="{{ $traning->pembelian }}" class="form-input" placeholder="0" required />
                </div>
                <div class="mb-5">
                    <label for="penjualan" class="dark:text-white-light">Penjualan</label>
                    <input id="penjualan" type="number" name="penjualan" value="{{ $traning->penjualan }}" class="form-input" placeholder="0" required />
                </div>
            </div>

            <button type="submit" class="btn btn-primary !mt-6">Update</button>
        </form>

    </div>

</div>
@endsection
