@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Data Traning
            </h5>

            <a href="{{ route('traning.create') }}" class="btn btn-primary">
                Tambah Traning
            </a>
        </div>

        @if(session('success'))
            <div class="mb-5 rounded bg-success-light p-3 text-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Barang</th>
                        <th>Persediaan Awal</th>
                        <th>Pembelian</th>
                        <th>Penjualan</th>
                        <th>Persediaan Akhir</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($traning as $item)
                    <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                        <td>{{ $loop->iteration }}</td>
                        <td class="text-black dark:text-white">{{ $item->barang->nama_barang }}</td>
                        <td>{{ $item->persediaan_awal }}</td>
                        <td>{{ $item->pembelian }}</td>
                        <td>{{ $item->penjualan }}</td>
                        <td>{{ $item->persediaan_akhir }}</td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('traning.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('traning.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Data traning kosong</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection
