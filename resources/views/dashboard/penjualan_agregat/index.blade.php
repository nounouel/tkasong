@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]" x-data="dailySalesData">

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
                        <th> Nama Barang </th>
                        <th> Kategori </th>
                        <th> Tanggal</th>
                        <th> Reorder Point (ROP) </th>
                        <th> Stok Terakhir </th>
                        <th class="ltr:rounded-r-md rtl:rounded-l-md"> Rata-rata Penjualan Perhari</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($penjualanAgregat as $item)
                    <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                        <td>
                            {{ $penjualanAgregat->firstItem() ? $penjualanAgregat->firstItem() + $loop->index : $loop->iteration }}
                        </td>
                        <td class="text-black dark:text-white font-semibold">
                            @if($item->barang)
                                <button type="button" @click="showSales({{ $item->id_barang }}, {{ json_encode($item->barang->nama_barang) }})" 
                                    class="hover:underline hover:text-primary text-left font-semibold">
                                    {{ $item->barang->nama_barang }}
                                </button>
                                @if($item->barang->satuan)
                                    <span class="text-xs text-gray-400 font-normal block sm:inline">({{ $item->barang->satuan }})</span>
                                @endif
                            @else
                                Barang tidak ditemukan
                            @endif
                        </td>
                        <td>  {{ $item->barang->kategori ?? 'Kategori tidak ditemukan' }} </td>
                        <td>  {{ \Carbon\Carbon::parse($item->tanggal)->format('d F Y') }}</td>
                        <td class="font-semibold text-warning"> {{ number_format($item->reorder_point) }} </td>
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

    <!-- sales detail modal -->
    <div class="fixed inset-0 z-[999] hidden overflow-y-auto bg-[black]/60 px-4" :class="isOpen && '!block'">
        <div class="flex min-h-screen items-center justify-center">
            <div x-show="isOpen" x-transition="" x-transition.duration.300="" @click.outside="isOpen = false" class="panel my-8 w-[90%] max-w-2xl overflow-hidden rounded-lg border-0 p-0 md:w-full shadow-2xl">
                <!-- header -->
                <div class="flex items-center justify-between bg-[#fbfbfb] px-5 py-4 dark:bg-[#121c2c] border-b border-[#ebedf2] dark:border-[#1b2e4b]">
                    <h5 class="text-lg font-bold text-black dark:text-white-light flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-primary">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Detail Penjualan Perhari: <span class="text-primary font-extrabold" x-text="selectedBarangName"></span>
                    </h5>
                    <button type="button" class="text-white-dark hover:text-dark dark:hover:text-white" @click="isOpen = false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewbox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                
                <!-- body -->
                <div class="p-5">
                    <!-- Loading state -->
                    <div x-show="isLoading" class="flex flex-col items-center justify-center py-12">
                        <span class="animate-spin inline-block w-8 h-8 border-[3px] border-current border-t-transparent text-primary rounded-full" role="status" aria-label="loading"></span>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Mengambil data penjualan harian...</p>
                    </div>

                    <!-- Content loaded state -->
                    <div x-show="!isLoading">
                        <template x-if="salesData.length > 0">
                            <div class="table-responsive max-h-[350px] overflow-y-auto border border-[#ebedf2] dark:border-[#1b2e4b] rounded-md">
                                <table class="table-hover text-sm">
                                    <thead class="sticky top-0 bg-[#fafafa] dark:bg-[#0e1726] z-10">
                                        <tr>
                                            <th class="w-16">No</th>
                                            <th>Tanggal</th>
                                            <th class="text-right">Jumlah Terjual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(sale, index) in salesData" :key="index">
                                            <tr class="hover:bg-gray-50 dark:hover:bg-black/10">
                                                <td x-text="index + 1"></td>
                                                <td x-text="formatDate(sale.tanggal)"></td>
                                                <td class="text-right font-bold text-primary" x-text="formatNumber(sale.total_terjual)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                        
                        <template x-if="salesData.length === 0">
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-12 w-12 text-gray-400 dark:text-gray-600 mb-3">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">Tidak ada data penjualan pada periode ini.</p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- footer -->
                <div class="flex items-center justify-end bg-[#fbfbfb] px-5 py-4 dark:bg-[#121c2c] border-t border-[#ebedf2] dark:border-[#1b2e4b]">
                    <button type="button" class="btn btn-outline-danger" @click="isOpen = false">Tutup</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('dailySalesData', () => ({
            isOpen: false,
            selectedBarangName: '',
            salesData: [],
            isLoading: false,

            showSales(idBarang, name) {
                this.selectedBarangName = name;
                this.isOpen = true;
                this.isLoading = true;
                this.salesData = [];

                let url = `/penjualan-agregat/daily-sales/${idBarang}`;
                const startDate = document.getElementById('start_date')?.value;
                const endDate = document.getElementById('end_date')?.value;

                let params = [];
                if (startDate) params.push(`start_date=${startDate}`);
                if (endDate) params.push(`end_date=${endDate}`);
                if (params.length > 0) {
                    url += '?' + params.join('&');
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        this.isLoading = false;
                        if (data.status === 'success') {
                            this.salesData = data.sales;
                        }
                    })
                    .catch(error => {
                        this.isLoading = false;
                        console.error('Error fetching daily sales:', error);
                    });
            },

            formatDate(dateStr) {
                if (!dateStr) return '';
                const date = new Date(dateStr);
                return date.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                });
            },

            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
        }));
    });
</script>
@endsection
