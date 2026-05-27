@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Data Fuzzy
            </h5>
            <div class="flex items-center gap-2">
                <form action="{{ route('fuzzy.index') }}" method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..." class="form-input w-48 sm:w-auto" />
                    <button type="submit" class="btn btn-secondary">Cari</button>
                </form>
                <a href="{{ route('fuzzy.create') }}" class="btn btn-primary">
                    Tambah Data Fuzzy
                </a>
            </div>
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
                        <th>Tanggal</th>
                        <th>Permintaan</th>
                        <th>Stok</th>
                        <th>Hasil Fuzzy</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fuzzies as $item)
                    <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                        <td>{{ $fuzzies->firstItem() ? $fuzzies->firstItem() + $loop->index : $loop->iteration }}</td>
                        <td class="text-black dark:text-white">{{ $item->barang->nama_barang }}</td>
                        <td>{{ $item->tanggal }}</td>
                        <td>{{ $item->permintaan }}</td>
                        <td>{{ $item->stok }}</td>
                        <td>{{ $item->hasil_fuzzy }}</td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('fuzzy.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('fuzzy.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Data fuzzy kosong</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $fuzzies->links() }}
        </div>

    </div>

</div>
@endsection
