@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Data Barang
            </h5>

            <a href="{{ route('barang.create') }}"
               class="btn btn-primary">
                Tambah Barang
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
                        <th class="ltr:rounded-l-md rtl:rounded-r-md">
                            No
                        </th>

                        <th>
                            Nama Barang
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
                            {{ $loop->iteration }}
                        </td>

                        <td class="text-black dark:text-white">
                            {{ $item->nama_barang }}
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
                        <td colspan="3" class="text-center">
                            Data barang kosong
                        </td>
                    </tr>

                    @endforelse

                </tbody>
            </table>
        </div>

    </div>

</div>
@endsection