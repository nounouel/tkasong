@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]" x-data="{
    idBarang: '{{ old('id_barang') }}',
    tanggal: '{{ old('tanggal') }}',
    permintaan: '{{ old('permintaan') }}',
    stok: '{{ old('stok') }}',
    kategori: '',
    satuan: '',
    init() {
        this.$watch('idBarang', async (value) => {
            if (!value) {
                this.permintaan = '';
                this.stok = '';
                this.kategori = '';
                this.satuan = '';
                return;
            }
            try {
                let response = await fetch(`/fuzzy/barang-detail/${value}`);
                let data = await response.json();
                if (data.status === 'success') {
                    this.permintaan = data.stok_aktual;
                    this.stok = data.rata_penjualan;
                    this.kategori = data.kategori;
                    this.satuan = data.satuan;
                }
            } catch (error) {
                console.error('Gagal mengambil detail barang:', error);
            }
        });
    }
}">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Tambah Data Fuzzy
            </h5>

            <a href="{{ route('fuzzy.index') }}" class="btn btn-primary">
                Kembali
            </a>
        </div>

        <form action="{{ route('fuzzy.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="mb-5">
                    <label for="id_barang" class="dark:text-white-light">Pilih Barang</label>
                    <select id="id_barang" name="id_barang" class="form-select text-white-dark" required x-model="idBarang">
                        <option value="">Pilih Barang</option>
                        @foreach ($barang as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->nama_barang }} - {{ $item->kategori ?? 'Kategori tidak ada' }} ({{ $item->satuan ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-5">
                    <label for="tanggal" class="dark:text-white-light">Tanggal</label>
                    <input id="tanggal" type="date" name="tanggal" class="form-input" required x-model="tanggal" />
                </div>
                <div class="mb-5">
                    <label for="kategori" class="dark:text-white-light">Kategori</label>
                    <input id="kategori" type="text" class="form-input bg-gray-100 dark:bg-gray-800" readonly x-model="kategori" placeholder="-" />
                </div>
                <div class="mb-5">
                    <label for="satuan" class="dark:text-white-light">Satuan</label>
                    <input id="satuan" type="text" class="form-input bg-gray-100 dark:bg-gray-800" readonly x-model="satuan" placeholder="-" />
                </div>
                <div class="mb-5">
                    <label for="stok_sekarang" class="dark:text-white-light">Stok Sekarang</label>
                    <input id="stok_sekarang" type="number" name="stok_sekarang" class="form-input" placeholder="0" required x-model="permintaan" />
                </div>
                <div class="mb-5">
                    <label for="rata2_penjualan" class="dark:text-white-light">Rata-rata Penjualan Perhari</label>
                    <input id="rata2_penjualan" type="float" name="rata2_penjualan" class="form-input" placeholder="0" required x-model="stok" />
                </div>

            </div>

            <div class="flex items-center gap-3 !mt-6">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>

    </div>



    </div>

</div>
@endsection
