@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Transaksi Masuk (Pembelian)
            </h5>
            <div class="flex items-center gap-2">
                <a href="{{ route('transaksi-masuk.create') }}"
                   class="btn btn-primary">
                    Tambah Transaksi Masuk
                </a>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="mb-5 rounded border border-gray-200 dark:border-gray-800 p-4">
            <form action="{{ route('transaksi-masuk.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="search" class="text-xs font-semibold text-gray-600 dark:text-gray-400">Cari Barang</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Nama barang..." class="form-input" />
                </div>
                <div>
                    <label for="start_date" class="text-xs font-semibold text-gray-600 dark:text-gray-400">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="form-input" />
                </div>
                <div>
                    <label for="end_date" class="text-xs font-semibold text-gray-600 dark:text-gray-400">Tanggal Akhir</label>
                    <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="form-input" />
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-secondary flex-1">Filter</button>
                    <a href="{{ route('transaksi-masuk.index') }}" class="btn btn-outline-danger">Reset</a>
                </div>
            </form>
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
                            Kategori Barang
                        </th>
                        <th>
                            Tanggal
                        </th>
                        <th>
                            Jumlah
                        </th>
                        <th>
                            Keterangan
                        </th>
                        <th>
                            Expired Date
                        </th>
                        <th class="text-center ltr:rounded-r-md rtl:rounded-l-md">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($transaksiMasuk as $item)

                    <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">

                        <td>
                            {{ $transaksiMasuk->firstItem() ? $transaksiMasuk->firstItem() + $loop->index : $loop->iteration }}
                        </td>

                        <td class="text-black dark:text-white font-semibold">
                            {{ $item->barang->nama_barang ?? 'Barang tidak ditemukan' }} 
                            @if($item->barang && $item->barang->satuan)
                                <span class="text-xs text-gray-400 font-normal">({{ $item->barang->satuan }})</span>
                            @endif
                        </td>

                        <td>
                            {{ $item->barang->kategori ?? 'Kategori tidak ditemukan' }}
                        </td>

                        <td>
                            {{ \Carbon\Carbon::parse($item->tanggal)->format('d F Y') }}
                        </td>

                        <td class="text-success font-bold">
                            +{{ number_format($item->jumlah) }}
                        </td>

                        <td>
                            {{ $item->keterangan ?? '-' }}
                        </td>

                        <td>
                            {{ $item->expired_date ? \Carbon\Carbon::parse($item->expired_date)->format('d F Y') : '-' }}
                        </td>

                        <td class="text-center">

                            <div class="flex items-center justify-center gap-2">

                                <a href="{{ route('transaksi-masuk.edit', $item->id) }}"
                                   class="btn btn-sm btn-warning">
                                    Edit
                                </a>

                                <form action="{{ route('transaksi-masuk.destroy', $item->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin hapus data transaksi masuk ini?')">

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
                        <td colspan="8" class="text-center">
                            Tidak ada data transaksi masuk
                        </td>
                    </tr>

                    @endforelse

                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $transaksiMasuk->links() }}
        </div>

    </div>

</div>
@endsection
