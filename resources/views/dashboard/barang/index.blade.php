@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Data Barang
            </h5>
            <div class="flex items-center gap-2">
                <form action="{{ route('barang.index') }}" method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..." class="form-input w-48 sm:w-auto" />
                    <button type="submit" class="btn btn-secondary">Cari</button>
                </form>
                <a href="{{ route('barang.create') }}"
                   class="btn btn-primary">
                    Tambah Barang
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
                        <th class="ltr:rounded-l-md rtl:rounded-r-md">
                            No
                        </th>

                        <th>
                            Nama Barang
                        </th>

                        <th>
                            Satuan
                        </th>
                        <th>
                            Kategori Barang
                        </th>

                        <th>
                            Stok Minimum
                        </th>

                        <th>
                            Reorder Point (ROP)
                        </th>

                        <th class="text-center ltr:rounded-r-md rtl:rounded-l-md">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($barang as $item)

                    <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">

                        <td>
                            {{ $barang->firstItem() ? $barang->firstItem() + $loop->index : $loop->iteration }}
                        </td>

                        <td class="text-black dark:text-white font-semibold">
                            {{ $item->nama_barang }}
                        </td>

                        <td>
                            {{ $item->satuan }}
                        </td>
                        <td>
                            {{ $item->kategori }}
                        </td>

                        <td>
                            {{ $item->stok_minimum }}
                        </td>

                        <td>
                            {{ $item->reorder_point }}
                        </td>

                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">

                                <a href="{{ route('barang.edit', $item->id) }}"
                                   class="btn btn-sm btn-warning">
                                    Edit
                                </a>

                                <form action="{{ route('barang.destroy', $item->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin hapus data?')">

                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="btn btn-sm btn-danger">
                                        Hapus
                                    </button>

                                </form>

                             </div>

                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td colspan="6" class="text-center">
                            Data barang kosong
                        </td>
                    </tr>

                    @endforelse

                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $barang->links() }}
        </div>

    </div>

</div>
@endsection