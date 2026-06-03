@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]" x-data="{
    isOpen: false,
    loading: false,
    namaBarang: '',
    permintaan: 0,
    stok: 0,
    nilaiCrisp: 0,
    domains: null,
    steps: null,
    
    minPenjualan() { return this.domains ? this.domains.penjualan.min : 0; },
    maxPenjualan() { return this.domains ? this.domains.penjualan.max : 0; },
    midPenjualan() { return (this.minPenjualan() + this.maxPenjualan()) / 2; },
    q1Penjualan() { return this.minPenjualan() + (this.midPenjualan() - this.minPenjualan()) / 2; },
    q3Penjualan() { return this.midPenjualan() + (this.maxPenjualan() - this.midPenjualan()) / 2; },

    minStok() { return this.domains ? this.domains.stok.min : 0; },
    maxStok() { return this.domains ? this.domains.stok.max : 0; },
    midStok() { return (this.minStok() + this.maxStok()) / 2; },
    q1Stok() { return this.minStok() + (this.midStok() - this.minStok()) / 2; },
    q3Stok() { return this.midStok() + (this.maxStok() - this.midStok()) / 2; },

    minPembelian() { return this.domains ? this.domains.pembelian.min : 0; },
    maxPembelian() { return this.domains ? this.domains.pembelian.max : 0; },
    midPembelian() { return (this.minPembelian() + this.maxPembelian()) / 2; },

    pembilangExpr() {
        if (!this.steps || !this.steps.rules) return '';
        return this.steps.rules.map(r => `${r.alpha} x ${r.z.toFixed(2)}`).join(' + ');
    },
    penyebutExpr() {
        if (!this.steps || !this.steps.rules) return '';
        return this.steps.rules.map(r => r.alpha).join(' + ');
    },
    async showDetail(id) {
        this.loading = true;
        try {
            let response = await fetch('/fuzzy/detail/' + id);
            let data = await response.json();
            if (data.status === 'success') {
                this.namaBarang = data.nama_barang;
                this.permintaan = data.permintaan;
                this.stok = data.stok;
                this.nilaiCrisp = data.nilai_crisp;
                this.domains = data.domains;
                this.steps = data.steps;
                this.isOpen = true;
            } else {
                alert(data.message || 'Gagal mengambil detail perhitungan.');
            }
        } catch (error) {
            alert('Terjadi kesalahan saat memproses data.');
            console.error(error);
        } finally {
            this.loading = false;
        }
    }
}">

    <div class="panel h-full w-full">

        <div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Rekomendasi Pembelian (Fuzzy Tsukamoto)
            </h5>
            <div class="flex items-center gap-2">
                <form action="{{ route('fuzzy.index') }}" method="GET" class="flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari barang..." class="form-input w-48 sm:w-auto" />
                    <button type="submit" class="btn btn-secondary">Cari</button>
                </form>
            <div class="flex items-center gap-2">
                <a href="{{ route('fuzzy.create') }}"
                   class="btn btn-primary">
                    Tambah Data
                </a>
            </div>
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
                        <th>Stok Terakhir</th>
                        <th>Reorder Point (ROP)</th>
                        <th>Penjualan Rata-rata</th>
                        <th>Kategori Fuzzy</th>
                        <th class="text-center">Rekomendasi Pembelian</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fuzzies as $item)
                    <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                        <td>{{ $fuzzies->firstItem() ? $fuzzies->firstItem() + $loop->index : $loop->iteration }}</td>
                        <td class="text-black dark:text-white font-semibold">
                            <button type="button" @click="showDetail({{ $item->id }})" class="text-primary hover:underline font-semibold text-left">
                                {{ $item->barang->nama_barang }}
                            </button>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($item->dihasilkan_pada)->format('d F Y') }}</td>
                        <td class="font-bold text-danger">{{ number_format($item->stok_saat_ini) }}</td>
                        <td class="font-semibold text-warning">{{ number_format($item->reorder_point) }}</td>
                        <td class="font-semibold text-primary">{{ number_format($item->rata_rata_penjualan_perhari, 2) }}</td>
                        <td>
                            <span class="badge {{ $item->kategori_fuzzy === 'Banyak' || $item->kategori_fuzzy === 'Sedang-Banyak' ? 'bg-primary' : 'bg-secondary' }}">
                                {{ $item->kategori_fuzzy }}
                            </span>
                        </td>
                        <td class="text-center font-extrabold text-success text-base">
                            {{ number_format($item->jumlah_direkomendasikan) }}
                        </td>
                        <td class="text-center">
                            <div class="flex items-center justify-center">
                                <form action="{{ route('fuzzy.destroy', $item->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin hapus data?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada barang yang berada di bawah Reorder Point (ROP) saat ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $fuzzies->links() }}
        </div>

    </div>

    <!-- Loading Overlay -->
    <div class="fixed inset-0 z-[1000] hidden items-center justify-center bg-black/30" :class="loading && '!flex'">
        <div class="animate-spin border-4 border-primary border-t-transparent rounded-full w-12 h-12"></div>
    </div>

    <!-- Modal Detail Perhitungan Fuzzy Tsukamoto -->
    <div class="fixed inset-0 z-[999] hidden overflow-y-auto bg-[black]/60 px-4" :class="isOpen && '!block'">
        <div class="flex min-h-screen items-center justify-center px-4" @click.self="isOpen = false">
            <div x-show="isOpen" x-transition x-transition.duration.300 class="panel my-8 w-full max-w-4xl overflow-hidden rounded-lg border-0 p-0 text-black dark:text-white-dark bg-white dark:bg-[#0e1726]">
                <!-- Header -->
                <div class="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c] border-b dark:border-gray-800">
                    <h5 class="text-lg font-bold text-black dark:text-white">Proses Perhitungan Fuzzy Tsukamoto</h5>
                    <button type="button" class="text-white-dark hover:text-dark dark:hover:text-white" @click="isOpen = false">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="p-6 overflow-y-auto max-h-[80vh] space-y-6">
                    <template x-if="steps">
                        <div class="space-y-6">
                            
                            <!-- Section 1: Data Input & Hasil -->
                            <div class="space-y-3">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">Data Input & Hasil Perhitungan</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-850">
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white">Barang</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-48">Rata-rata Penjualan (Permintaan)</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-32">Stok Saat Ini</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-44">Rekomendasi Pembelian</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                <td class="py-3 px-4 text-gray-900 dark:text-white font-medium" x-text="namaBarang"></td>
                                                <td class="py-3 px-4 font-semibold" x-text="parseFloat(permintaan).toFixed(2)"></td>
                                                <td class="py-3 px-4 font-semibold" x-text="stok"></td>
                                                <td class="py-3 px-4 font-bold text-success" x-text="Math.round(nilaiCrisp)"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Section 2: Rentang Nilai (Domain Fuzzy) -->
                            <div class="space-y-3">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">Rentang Nilai (Domain Fuzzy)</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-850">
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white">Batas</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-48">Variabel Penjualan (Min - Max)</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-48">Variabel Stok (Min - Max)</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-48">Variabel Pembelian (Min - Max)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Batas Minimum (Min)</td>
                                                <td class="py-3 px-4" x-text="minPenjualan()"></td>
                                                <td class="py-3 px-4" x-text="minStok()"></td>
                                                <td class="py-3 px-4" x-text="minPembelian()"></td>
                                            </tr>
                                            <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Batas Maksimum (Max)</td>
                                                <td class="py-3 px-4" x-text="maxPenjualan()"></td>
                                                <td class="py-3 px-4" x-text="maxStok()"></td>
                                                <td class="py-3 px-4" x-text="maxPembelian()"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Section 3: Fuzzifikasi -->
                            <div class="space-y-6">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">Fuzzifikasi</h4>
                                
                                <!-- Variabel Penjualan -->
                                <div class="space-y-3">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                                            <thead>
                                                <tr class="border-b border-gray-200 dark:border-gray-850">
                                                    <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-1/3">Himpunan</th>
                                                    <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white">Fungsi Keanggotaan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Penjualan Sedikit</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>1</div>
                                                            <div x-text="'x ≤ ' + minPenjualan().toFixed(1)"></div>
                                                            <div x-text="'(' + midPenjualan().toFixed(1) + ' - x) / ' + (midPenjualan() - minPenjualan()).toFixed(1)"></div>
                                                            <div x-text="minPenjualan().toFixed(1) + ' < x < ' + midPenjualan().toFixed(1)"></div>
                                                            <div>0</div>
                                                            <div x-text="'x ≥ ' + midPenjualan().toFixed(1)"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Penjualan Sedang</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>0</div>
                                                            <div x-text="'x ≤ ' + minPenjualan().toFixed(1) + ' atau x ≥ ' + maxPenjualan().toFixed(1)"></div>
                                                            <div x-text="'(x - ' + minPenjualan().toFixed(1) + ') / ' + (midPenjualan() - minPenjualan()).toFixed(1)"></div>
                                                            <div x-text="minPenjualan().toFixed(1) + ' < x < ' + midPenjualan().toFixed(1)"></div>
                                                            <div>1</div>
                                                            <div x-text="'x = ' + midPenjualan().toFixed(1)"></div>
                                                            <div x-text="'(' + maxPenjualan().toFixed(1) + ' - x) / ' + (maxPenjualan() - midPenjualan()).toFixed(1)"></div>
                                                            <div x-text="midPenjualan().toFixed(1) + ' < x < ' + maxPenjualan().toFixed(1)"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Penjualan Banyak</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>0</div>
                                                            <div x-text="'x ≤ ' + midPenjualan().toFixed(1)"></div>
                                                            <div x-text="'(x - ' + midPenjualan().toFixed(1) + ') / ' + (maxPenjualan() - midPenjualan()).toFixed(1)"></div>
                                                            <div x-text="midPenjualan().toFixed(1) + ' < x < ' + maxPenjualan().toFixed(1)"></div>
                                                            <div>1</div>
                                                            <div x-text="'x ≥ ' + maxPenjualan().toFixed(1)"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="pt-2">
                                        <h5 class="font-bold text-gray-900 dark:text-white mb-2 text-sm">Derajat Keanggotaan</h5>
                                        <div class="max-w-xl text-sm space-y-2 text-gray-700 dark:text-gray-300">
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">μ Penjualan Sedikit [<span class="font-semibold text-gray-900 dark:text-white" x-text="parseFloat(permintaan).toFixed(2)"></span>]</div>
                                                <div class="text-left flex-1 font-mono text-xs" x-text="steps.fuzzifikasi.formulas.penjualan.sedikit"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.penjualan.sedikit"></div>
                                            </div>
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">μ Penjualan Sedang [<span class="font-semibold text-gray-900 dark:text-white" x-text="parseFloat(permintaan).toFixed(2)"></span>]</div>
                                                <div class="text-left flex-1 font-mono text-xs" x-text="steps.fuzzifikasi.formulas.penjualan.sedang"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.penjualan.sedang"></div>
                                            </div>
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">μ Penjualan Banyak [<span class="font-semibold text-gray-900 dark:text-white" x-text="parseFloat(permintaan).toFixed(2)"></span>]</div>
                                                <div class="text-left flex-1 font-mono text-xs" x-text="steps.fuzzifikasi.formulas.penjualan.banyak"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.penjualan.banyak"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Variabel Stok -->
                                <div class="space-y-3 pt-4">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                                            <thead>
                                                <tr class="border-b border-gray-200 dark:border-gray-850">
                                                    <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-1/3">Himpunan</th>
                                                    <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white">Fungsi Keanggotaan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Stok Sedikit</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>1</div>
                                                            <div x-text="'y ≤ ' + minStok().toFixed(1)"></div>
                                                            <div x-text="'(' + midStok().toFixed(1) + ' - y) / ' + (midStok() - minStok()).toFixed(1)"></div>
                                                            <div x-text="minStok().toFixed(1) + ' < y < ' + midStok().toFixed(1)"></div>
                                                            <div>0</div>
                                                            <div x-text="'y ≥ ' + midStok().toFixed(1)"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Stok Sedang</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>0</div>
                                                            <div x-text="'y ≤ ' + minStok().toFixed(1) + ' atau y ≥ ' + maxStok().toFixed(1)"></div>
                                                            <div x-text="'(y - ' + minStok().toFixed(1) + ') / ' + (midStok() - minStok()).toFixed(1)"></div>
                                                            <div x-text="minStok().toFixed(1) + ' < y < ' + midStok().toFixed(1)"></div>
                                                            <div>1</div>
                                                            <div x-text="'y = ' + midStok().toFixed(1)"></div>
                                                            <div x-text="'(' + maxStok().toFixed(1) + ' - y) / ' + (maxStok() - midStok()).toFixed(1)"></div>
                                                            <div x-text="midStok().toFixed(1) + ' < y < ' + maxStok().toFixed(1)"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Stok Banyak</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>0</div>
                                                            <div x-text="'y ≤ ' + midStok().toFixed(1)"></div>
                                                            <div x-text="'(y - ' + midStok().toFixed(1) + ') / ' + (maxStok() - midStok()).toFixed(1)"></div>
                                                            <div x-text="midStok().toFixed(1) + ' < y < ' + maxStok().toFixed(1)"></div>
                                                            <div>1</div>
                                                            <div x-text="'y ≥ ' + maxStok().toFixed(1)"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="pt-2">
                                        <h5 class="font-bold text-gray-900 dark:text-white mb-2 text-sm">Derajat Keanggotaan</h5>
                                        <div class="max-w-xl text-sm space-y-2 text-gray-700 dark:text-gray-300">
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">μ Stok Sedikit [<span class="font-semibold text-gray-900 dark:text-white" x-text="stok"></span>]</div>
                                                <div class="text-left flex-1 font-mono text-xs" x-text="steps.fuzzifikasi.formulas.stok.sedikit"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.stok.sedikit"></div>
                                            </div>
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">μ Stok Sedang [<span class="font-semibold text-gray-900 dark:text-white" x-text="stok"></span>]</div>
                                                <div class="text-left flex-1 font-mono text-xs" x-text="steps.fuzzifikasi.formulas.stok.sedang"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.stok.sedang"></div>
                                            </div>
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">μ Stok Banyak [<span class="font-semibold text-gray-900 dark:text-white" x-text="stok"></span>]</div>
                                                <div class="text-left flex-1 font-mono text-xs" x-text="steps.fuzzifikasi.formulas.stok.banyak"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.stok.banyak"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Variabel Output Pembelian -->
                                <div class="space-y-3 pt-4">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                                            <thead>
                                                <tr class="border-b border-gray-200 dark:border-gray-850">
                                                    <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-1/3">Himpunan</th>
                                                    <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white">Fungsi Keanggotaan (Nilai Representatif)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Pembelian Sedikit</td>
                                                    <td class="py-3 px-4 font-mono text-xs" x-text="'z = maxPembelian - α * (maxPembelian - minPembelian) = ' + maxPembelian() + ' - α * ' + (maxPembelian() - minPembelian()).toFixed(1)"></td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Pembelian Sedang-Sedikit</td>
                                                    <td class="py-3 px-4 font-mono text-xs" x-text="'z = minPembelian + α * (midPembelian - minPembelian) = ' + minPembelian() + ' + α * ' + (midPembelian() - minPembelian()).toFixed(1)"></td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Pembelian Sedang-Banyak</td>
                                                    <td class="py-3 px-4 font-mono text-xs" x-text="'z = midPembelian + α * (maxPembelian - midPembelian) = ' + midPembelian().toFixed(1) + ' + α * ' + (maxPembelian() - midPembelian()).toFixed(1)"></td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Pembelian Banyak</td>
                                                    <td class="py-3 px-4 font-mono text-xs" x-text="'z = midPembelian + α * (maxPembelian - midPembelian) = ' + midPembelian().toFixed(1) + ' + α * ' + (maxPembelian() - midPembelian()).toFixed(1)"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 4: Pembentukan Basis Pengetahuan Fuzzy -->
                            <div class="space-y-3">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">Pembentukan Basis Pengetahuan Fuzzy</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-850">
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-16">Kode</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white">Rule</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-28">α-predikat</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-20">z</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-28">α × z</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(rule, index) in steps.rules" :key="index">
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50 hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                                    <td class="py-3 px-4 font-bold text-gray-900 dark:text-white" x-text="rule.name"></td>
                                                    <td class="py-3 px-4 text-xs" x-text="rule.text"></td>
                                                    <td class="py-3 px-4 font-mono text-xs" x-text="rule.alpha"></td>
                                                    <td class="py-3 px-4 font-mono text-xs" x-text="rule.z.toFixed(2)"></td>
                                                    <td class="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white text-xs" x-text="(rule.alpha * rule.z).toFixed(4)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Section 5: Defuzzifikasi -->
                            <div class="space-y-3">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">Defuzzifikasi (Weighted Average)</h4>
                                <div class="text-sm text-gray-700 dark:text-gray-300 space-y-4 font-mono leading-relaxed">
                                    <div class="overflow-x-auto whitespace-nowrap text-xs">
                                        Z = (α1 x Z1 + α2 x Z2 + α3 x Z3 + α4 x Z4 + α5 x Z5 + α6 x Z6 + α7 x Z7 + α8 x Z8 + α9 x Z9) / (α1 + α2 + α3 + α4 + α5 + α6 + α7 + α8 + α9)
                                    </div>
                                    <div class="overflow-x-auto whitespace-normal text-xs">
                                        Z = (<span x-text="pembilangExpr()"></span>) / (<span x-text="penyebutExpr()"></span>)
                                    </div>
                                    <div class="text-xs">
                                        Z = <span x-text="steps.defuzzifikasi.pembilang"></span> / <span x-text="steps.defuzzifikasi.penyebut"></span>
                                    </div>
                                    <div class="text-xs">
                                        Z = <span x-text="steps.defuzzifikasi.hasil_crisp"></span>
                                    </div>
                                    <div class="text-base text-gray-900 dark:text-white font-sans">
                                        Z dibulatkan menjadi <span class="font-bold text-success" x-text="Math.round(steps.defuzzifikasi.hasil_crisp)"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Conclusion Summary -->
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-850">
                                <p class="text-gray-800 dark:text-gray-200 text-sm">
                                    Berdasarkan evaluasi metode Fuzzy Tsukamoto, nilai crisp rekomendasi Pembelian Barang adalah <strong class="font-bold text-success" x-text="Math.round(steps.defuzzifikasi.hasil_crisp)"></strong> unit, yang dikategorikan dalam himpunan output <strong class="font-bold text-success" x-text="steps.kategori"></strong>.
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
                <!-- Footer -->
                <div class="flex items-center justify-end p-5 bg-[#fbfbfb] dark:bg-[#121c2c] border-t dark:border-gray-800">
                    <button type="button" class="btn btn-outline-danger" @click="isOpen = false">Tutup</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection


