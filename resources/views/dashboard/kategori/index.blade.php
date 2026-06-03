@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Kategori Barang
            </h5>
            <div class="flex items-center gap-2">
                <form action="{{ route('kategori.index') }}" method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..." class="form-input w-48 sm:w-auto" />
                    <button type="submit" class="btn btn-secondary">Cari</button>
                </form>
                <a href="{{ route('kategori.create') }}"
                   class="btn btn-primary">
                    Tambah Kategori
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
                            Nama Kategori
                        </th>
                        <th class="text-center ltr:rounded-r-md rtl:rounded-l-md">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($kategori as $item)
                    <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                        <td>
                            {{ $kategori->firstItem() ? $kategori->firstItem() + $loop->index : $loop->iteration }}
                        </td>
                        <td class="text-black dark:text-white font-semibold">
                            {{ $item->nama_kategori }}
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('kategori.edit', $item->id) }}"
                                   class="btn btn-sm btn-warning">
                                    Edit
                                </a>
                                <form action="{{ route('kategori.destroy', $item->id) }}"
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
                        <td colspan="3" class="text-center">
                            Data kategori kosong
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $kategori->links() }}
        </div>

    </div>

</div>
@endsection
