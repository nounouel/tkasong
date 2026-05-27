@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Edit Transaksi Keluar
            </h5>

            <a href="{{ route('transaksi-keluar.index') }}"
               class="btn btn-primary">
                Kembali
            </a>
        </div>

        <form action="{{ route('transaksi-keluar.update', $transaksiKeluar->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                
                <div class="mb-5">
                    <label for="id_barang" class="dark:text-white-light font-semibold">Pilih Barang</label>
                    <select id="id_barang" name="id_barang" class="form-select text-white-dark" required>
                        <option value="">Pilih Barang</option>
                        @foreach ($barang as $item)
                            <option value="{{ $item->id }}" {{ old('id_barang', $transaksiKeluar->id_barang) == $item->id ? 'selected' : '' }}>
                                {{ $item->nama_barang }} @if($item->satuan) ({{ $item->satuan }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('id_barang')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="tanggal" class="dark:text-white-light font-semibold">Tanggal Transaksi</label>
                    <input id="tanggal" type="date" name="tanggal" value="{{ old('tanggal', $transaksiKeluar->tanggal) }}" class="form-input" required />
                    @error('tanggal')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="jumlah" class="dark:text-white-light font-semibold">Jumlah Keluar</label>
                    <input id="jumlah" type="number" name="jumlah" value="{{ old('jumlah', $transaksiKeluar->jumlah) }}" min="1" class="form-input" placeholder="Masukkan jumlah barang keluar" required />
                    @error('jumlah')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="keterangan" class="dark:text-white-light font-semibold">Keterangan</label>
                    <textarea id="keterangan" name="keterangan" rows="3" class="form-input" placeholder="Masukkan keterangan (opsional)">{{ old('keterangan', $transaksiKeluar->keterangan) }}</textarea>
                    @error('keterangan')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

            </div>

            <button type="submit" class="btn btn-primary !mt-6">Update Transaksi</button>
        </form>

    </div>

</div>
@endsection
