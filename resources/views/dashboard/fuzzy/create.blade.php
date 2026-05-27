@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]" x-data="{
    idBarang: '{{ old('id_barang') }}',
    tanggal: '{{ old('tanggal') }}',
    permintaan: '{{ old('permintaan') }}',
    stok: '{{ old('stok') }}',
    hasilFuzzy: '{{ old('hasil_fuzzy') }}',
    loading: false,
    isOpen: false,
    steps: null,
    pembilangExpr() {
        if (!this.steps || !this.steps.rules) return '';
        return this.steps.rules.map(r => `${r.alpha} x ${r.z}`).join(' + ');
    },
    penyebutExpr() {
        if (!this.steps || !this.steps.rules) return '';
        return this.steps.rules.map(r => r.alpha).join(' + ');
    },
    async predictFuzzy() {
        if (!this.idBarang || !this.tanggal) {
            alert('Silakan pilih barang dan tanggal terlebih dahulu.');
            return;
        }
        this.loading = true;
        try {
            let response = await fetch('/fuzzy/predict?id_barang=' + this.idBarang + '&tanggal=' + this.tanggal);
            let data = await response.json();
            if (data.status === 'success') {
                this.permintaan = data.permintaan;
                this.stok = data.stok;
                this.hasilFuzzy = data.hasil_fuzzy;
                this.steps = data.steps;
                this.isOpen = true;
            } else {
                alert(data.message || 'Gagal melakukan prediksi.');
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
                            <option value="{{ $item->id }}">{{ $item->nama_barang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-5">
                    <label for="tanggal" class="dark:text-white-light">Tanggal</label>
                    <input id="tanggal" type="date" name="tanggal" class="form-input" required x-model="tanggal" />
                </div>
                <div class="mb-5">
                    <label for="pembelian" class="dark:text-white-light">Pembelian</label>
                    <input id="pembelian" type="number" name="pembelian" class="form-input" placeholder="0" required x-model="permintaan" />
                </div>
                <div class="mb-5">
                    <label for="penjualan" class="dark:text-white-light">Penjualan</label>
                    <input id="penjualan" type="number" name="penjualan" class="form-input" placeholder="0" required x-model="stok" />
                </div>
                <div class="mb-5 md:col-span-2">
                    <label for="stok_akhir" class="dark:text-white-light">Persediaan Akhir</label>
                    <input id="stok_akhir" type="number" name="stok_akhir" class="form-input" placeholder="0" required />
                </div>
            </div>

            <div class="flex items-center gap-3 !mt-6">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <!-- <button type="button" class="btn btn-info flex items-center justify-center" @click="predictFuzzy()" :disabled="loading">
                    <span x-show="loading" class="animate-spin border-2 border-white border-t-transparent rounded-full w-4 h-4 mr-2"></span>
                    Cari Prediksi (Tsukamoto)
                </button> -->
            </div>
        </form>

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
                            
                            <!-- Section 1: Perhitungan Fuzzy Tsukamoto -->
                            <div class="space-y-3">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">Perhitungan Fuzzy Tsukamoto</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-850">
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white">Barang</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-32">Pembelian</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-32">Penjualan</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-36">Persediaan Akhir</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-36">Persediaan Awal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                <td class="py-3 px-4 text-gray-900 dark:text-white font-medium" x-text="steps.nama_barang"></td>
                                                <td class="py-3 px-4 font-bold text-red-500">???</td>
                                                <td class="py-3 px-4" x-text="steps.training_data.penjualan"></td>
                                                <td class="py-3 px-4" x-text="steps.training_data.persediaan_akhir"></td>
                                                <td class="py-3 px-4" x-text="steps.training_data.persediaan_awal"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Section 2: Rentang Nilai -->
                            <div class="space-y-3">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">Rentang Nilai</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-gray-600 dark:text-gray-300">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-850">
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white" x-text="steps.nama_barang"></th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-32">Pembelian</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-32">Penjualan</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-36">Persediaan Akhir</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-36">Persediaan Awal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Min</td>
                                                <td class="py-3 px-4" x-text="steps.min_max.pembelian.min"></td>
                                                <td class="py-3 px-4" x-text="steps.min_max.penjualan.min"></td>
                                                <td class="py-3 px-4" x-text="steps.min_max.persediaan_akhir.min"></td>
                                                <td class="py-3 px-4" x-text="steps.min_max.persediaan_awal.min"></td>
                                            </tr>
                                            <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Max</td>
                                                <td class="py-3 px-4" x-text="steps.min_max.pembelian.max"></td>
                                                <td class="py-3 px-4" x-text="steps.min_max.penjualan.max"></td>
                                                <td class="py-3 px-4" x-text="steps.min_max.persediaan_akhir.max"></td>
                                                <td class="py-3 px-4" x-text="steps.min_max.persediaan_awal.max"></td>
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
                                                            <div>x &le; 0</div>
                                                            <div>(50 - x) / 50</div>
                                                            <div>0 &lt; x &lt; 50</div>
                                                            <div>0</div>
                                                            <div>x &ge; 50</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Penjualan Sedang</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>0</div>
                                                            <div>x &le; 25 atau x &ge; 75</div>
                                                            <div>(x - 25) / 25</div>
                                                            <div>25 &lt; x &lt; 50</div>
                                                            <div>1</div>
                                                            <div>x = 50</div>
                                                            <div>(75 - x) / 25</div>
                                                            <div>50 &lt; x &lt; 75</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Penjualan Banyak</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>0</div>
                                                            <div>x &le; 50</div>
                                                            <div>(x - 50) / 50</div>
                                                            <div>50 &lt; x &lt; 100</div>
                                                            <div>1</div>
                                                            <div>x &ge; 100</div>
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
                                                <div class="w-1/2">&mu; Penjualan Sedikit [<span class="font-semibold text-gray-900 dark:text-white" x-text="steps.permintaan"></span>]</div>
                                                <div class="text-left flex-1 font-mono" x-text="steps.fuzzifikasi.formulas.penjualan.sedikit"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.penjualan.sedikit"></div>
                                            </div>
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">&mu; Penjualan Sedang [<span class="font-semibold text-gray-900 dark:text-white" x-text="steps.permintaan"></span>]</div>
                                                <div class="text-left flex-1 font-mono" x-text="steps.fuzzifikasi.formulas.penjualan.sedang"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.penjualan.sedang"></div>
                                            </div>
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">&mu; Penjualan Banyak [<span class="font-semibold text-gray-900 dark:text-white" x-text="steps.permintaan"></span>]</div>
                                                <div class="text-left flex-1 font-mono" x-text="steps.fuzzifikasi.formulas.penjualan.banyak"></div>
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
                                                            <div>y &le; 0</div>
                                                            <div>(50 - y) / 50</div>
                                                            <div>0 &lt; y &lt; 50</div>
                                                            <div>0</div>
                                                            <div>y &ge; 50</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Stok Sedang</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>0</div>
                                                            <div>y &le; 25 atau y &ge; 75</div>
                                                            <div>(y - 25) / 25</div>
                                                            <div>25 &lt; y &lt; 50</div>
                                                            <div>1</div>
                                                            <div>y = 50</div>
                                                            <div>(75 - y) / 25</div>
                                                            <div>50 &lt; y &lt; 75</div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 align-top font-semibold text-gray-900 dark:text-white">Stok Banyak</td>
                                                    <td class="py-3 px-4">
                                                        <div class="grid grid-cols-2 gap-x-4 max-w-md font-mono text-xs md:text-sm text-gray-800 dark:text-gray-200">
                                                            <div>0</div>
                                                            <div>y &le; 50</div>
                                                            <div>(y - 50) / 50</div>
                                                            <div>50 &lt; y &lt; 100</div>
                                                            <div>1</div>
                                                            <div>y &ge; 100</div>
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
                                                <div class="w-1/2">&mu; Stok Sedikit [<span class="font-semibold text-gray-900 dark:text-white" x-text="steps.stok"></span>]</div>
                                                <div class="text-left flex-1 font-mono" x-text="steps.fuzzifikasi.formulas.stok.sedikit"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.stok.sedikit"></div>
                                            </div>
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">&mu; Stok Sedang [<span class="font-semibold text-gray-900 dark:text-white" x-text="steps.stok"></span>]</div>
                                                <div class="text-left flex-1 font-mono" x-text="steps.fuzzifikasi.formulas.stok.sedang"></div>
                                                <div class="w-24 text-right font-bold text-gray-900 dark:text-white" x-text="'= ' + steps.fuzzifikasi.stok.sedang"></div>
                                            </div>
                                            <div class="flex justify-between items-center py-1">
                                                <div class="w-1/2">&mu; Stok Banyak [<span class="font-semibold text-gray-900 dark:text-white" x-text="steps.stok"></span>]</div>
                                                <div class="text-left flex-1 font-mono" x-text="steps.fuzzifikasi.formulas.stok.banyak"></div>
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
                                                    <td class="py-3 px-4 font-mono">z = 20</td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Pembelian Sedang-Sedikit</td>
                                                    <td class="py-3 px-4 font-mono">z = 45</td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Pembelian Sedang-Banyak</td>
                                                    <td class="py-3 px-4 font-mono">z = 70</td>
                                                </tr>
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50">
                                                    <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">Pembelian Banyak</td>
                                                    <td class="py-3 px-4 font-mono">z = 95</td>
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
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-28">&alpha;-predikat</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-20">z</th>
                                                <th class="py-3 px-4 font-semibold text-left text-gray-900 dark:text-white w-28">&alpha; &times; z</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(rule, index) in steps.rules" :key="index">
                                                <tr class="border-b border-gray-100 dark:border-gray-800/50 hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                                    <td class="py-3 px-4 font-bold text-gray-900 dark:text-white" x-text="rule.name"></td>
                                                    <td class="py-3 px-4" x-text="rule.text"></td>
                                                    <td class="py-3 px-4 font-mono" x-text="rule.alpha"></td>
                                                    <td class="py-3 px-4 font-mono" x-text="rule.z"></td>
                                                    <td class="py-3 px-4 font-mono font-bold text-gray-900 dark:text-white" x-text="(rule.alpha * rule.z).toFixed(4)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Section 5: Defuzzifikasi -->
                            <div class="space-y-3">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white">Defuzzifikasi</h4>
                                <div class="text-sm text-gray-700 dark:text-gray-300 space-y-4 font-mono leading-relaxed">
                                    <div class="overflow-x-auto whitespace-nowrap">
                                        Z = (&alpha;1 x Z1 + &alpha;2 x Z2 + &alpha;3 x Z3 + &alpha;4 x Z4 + &alpha;5 x Z5 + &alpha;6 x Z6 + &alpha;7 x Z7 + &alpha;8 x Z8 + &alpha;9 x Z9) / (&alpha;1 + &alpha;2 + &alpha;3 + &alpha;4 + &alpha;5 + &alpha;6 + &alpha;7 + &alpha;8 + &alpha;9)
                                    </div>
                                    <div class="overflow-x-auto whitespace-normal">
                                        Z = (<span x-text="pembilangExpr()"></span>) / (<span x-text="penyebutExpr()"></span>)
                                    </div>
                                    <div>
                                        Z = <span x-text="steps.defuzzifikasi.pembilang"></span> / <span x-text="steps.defuzzifikasi.penyebut"></span>
                                    </div>
                                    <div>
                                        Z = <span x-text="steps.defuzzifikasi.hasil_crisp"></span>
                                    </div>
                                    <div class="text-base text-gray-900 dark:text-white font-sans">
                                        Z dibulatkan menjadi <span class="font-bold text-success" x-text="Math.round(steps.defuzzifikasi.hasil_crisp)"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Conclusion Summary -->
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-800">
                                <p class="text-gray-800 dark:text-gray-200 text-sm">
                                    Berdasarkan evaluasi metode Fuzzy Tsukamoto, nilai crisp prediksi Pembelian Barang adalah <strong class="font-bold text-success" x-text="Math.round(steps.defuzzifikasi.hasil_crisp)"></strong> unit, yang dikategorikan dalam himpunan output <strong class="font-bold text-success text-success" x-text="steps.kategori"></strong>.
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

</div>
@endsection
