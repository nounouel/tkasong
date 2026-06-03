@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Penjualan Agregat
            </h5>
        </div>

        <!-- Filter Form -->
        <div class="mb-5 rounded border border-gray-200 dark:border-gray-800 p-4">
            <form action="{{ route('penjualan-agregat.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
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
                    <a href="{{ route('penjualan-agregat.index') }}" class="btn btn-outline-danger">Reset</a>
                </div>
            </form>
        </div>

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
                            Kategori
                        </th>

                        <th>
                            Tanggal
                        </th>
                        <th>
                            Reorder Point (ROP)
                        </th>
                        <th>
                            Stok Terakhir
                        </th>
                        <th class="ltr:rounded-r-md rtl:rounded-l-md">
                            Rata-rata Penjualan Perhari
                        </th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($penjualanAgregat as $item)

                    <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">

                        <td>
                            {{ $penjualanAgregat->firstItem() ? $penjualanAgregat->firstItem() + $loop->index : $loop->iteration }}
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

                        <td class="font-semibold text-warning">
                            {{ number_format($item->reorder_point) }}
                        </td>

                        <td class="font-semibold {{ $item->stok_terakhir <= $item->reorder_point ? 'text-danger font-bold' : 'text-success' }}">
                            {{ number_format($item->stok_terakhir) }}
                        </td>

                        <td class="text-primary font-bold">
                            {{ number_format($item->rata_rata_penjualan_perhari, 2) }}
                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td colspan="7" class="text-center">
                            Tidak ada data penjualan agregat
                        </td>
                    </tr>

                    @endforelse

                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $penjualanAgregat->links() }}
        </div>

    </div>

</div>
@endsection
