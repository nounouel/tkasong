@extends('layouts.master')
@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    {{-- ============================================================ --}}
    {{-- INDIKATOR CARDS --}}
    {{-- ============================================================ --}}
    <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">

        {{-- Card 1: Stok di Bawah Minimum --}}
        <div class="panel border-l-4 border-danger">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-white-dark">Stok Di Bawah Minimum</p>
                    <h3 class="mt-1 text-3xl font-bold text-danger">{{ $jumlahBawahMinimum }}</h3>
                    <p class="mt-1 text-xs text-white-dark">jenis barang perlu direstok</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-danger/10 text-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Card 2: Stok Masuk --}}
        <div class="panel border-l-4 border-success">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-white-dark">Total Stok Masuk</p>
                    <h3 class="mt-1 text-3xl font-bold text-success">{{ number_format($totalMasuk) }}</h3>
                    <p class="mt-1 text-xs text-white-dark">unit (total pembelian)</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-success/10 text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Card 3: Stok Keluar --}}
        <div class="panel border-l-4 border-warning">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-white-dark">Total Stok Keluar</p>
                    <h3 class="mt-1 text-3xl font-bold text-warning">{{ number_format($totalKeluar) }}</h3>
                    <p class="mt-1 text-xs text-white-dark">unit (total penjualan)</p>
                </div>
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-warning/10 text-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4 4l4 4m0 0l4-4m-4 4V4" />
                    </svg>
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================================ --}}
    {{-- GRAFIK & TABEL BARANG BAWAH MINIMUM --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Grafik Bar: Top 5 Barang Paling Menipis --}}
        <div class="panel">
            <div class="mb-5 flex items-center justify-between">
                <h5 class="text-lg font-semibold dark:text-white-light">Top 5 Barang Paling Menipis</h5>
                <span class="badge bg-danger">Stok Terendah</span>
            </div>

            @if($top5Menipis->isEmpty())
                <div class="flex flex-col items-center justify-center py-10 text-white-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6M4 20h16"/>
                    </svg>
                    <p class="text-sm">Belum ada data transaksi</p>
                </div>
            @else
                <div x-data="{}" x-init="renderChart()">
                    <div id="barChart"></div>
                </div>
            @endif

            {{-- Data JSON untuk chart --}}
            <script>
                var top5Data = {
                    labels: @json($top5Menipis->map(fn($item) => $item->nama_barang . ' (' . ($item->kategori ?? '-') . ' - ' . ($item->satuan ?? '-') . ')')),
                    stok: @json($top5Menipis->pluck('persediaan_akhir')),
                    minimum: @json($top5Menipis->pluck('stok_minimum')),
                };

                function renderChart() {
                    if (typeof ApexCharts === 'undefined' || !document.getElementById('barChart')) return;

                    var options = {
                        series: [
                            {
                                name: 'Stok Saat Ini',
                                data: top5Data.stok.map(function(val, i) {
                                    return {
                                        x: top5Data.labels[i],
                                        y: val,
                                        fillColor: Number(val) < Number(top5Data.minimum[i]) ? '#e7515a' : '#00ab55'
                                    };
                                }),
                            },
                            {
                                name: 'Batas Minimum',
                                data: top5Data.minimum.map(function(val, i) {
                                    return {
                                        x: top5Data.labels[i],
                                        y: val
                                    };
                                }),
                                type: 'line',
                            }
                        ],
                        chart: {
                            type: 'bar',
                            height: 300,
                            toolbar: { show: false },
                            fontFamily: 'Nunito, sans-serif',
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 6,
                                distributed: false,
                                dataLabels: { position: 'bottom' },
                            },
                        },
                        colors: ['#00ab55', '#e7515a'],
                        dataLabels: {
                            enabled: true,
                            style: { fontSize: '12px', colors: ['#fff'] },
                        },
                        xaxis: {
                            categories: top5Data.labels,
                            labels: {
                                style: { colors: '#888ea8', fontSize: '12px' },
                            },
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#888ea8', fontSize: '12px' },
                            },
                        },
                        legend: { show: false },
                        grid: {
                            borderColor: '#e0e6ed',
                            strokeDashArray: 5,
                        },
                        tooltip: {
                            theme: 'dark',
                        },
                    };

                    var chart = new ApexCharts(document.getElementById('barChart'), options);
                    chart.render();
                }

                document.addEventListener('DOMContentLoaded', function () {
                    renderChart();
                });
            </script>
        </div>

        {{-- Grafik Bar: Top 5 Barang Paling Banyak Terjual --}}
        <div class="panel">
            <div class="mb-5 flex items-center justify-between">
                <h5 class="text-lg font-semibold dark:text-white-light">Top 5 Barang Paling Banyak Terjual</h5>
                <span class="badge bg-primary">Terlaris</span>
            </div>

            @if($top5Terjual->isEmpty())
                <div class="flex flex-col items-center justify-center py-10 text-white-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6M4 20h16"/>
                    </svg>
                    <p class="text-sm">Belum ada data transaksi penjualan</p>
                </div>
            @else
                <div x-data="{}" x-init="renderTopSoldChart()">
                    <div id="topSoldChart"></div>
                </div>
            @endif

            {{-- Data JSON untuk chart --}}
            <script>
                var top5TerjualData = {
                    labels: @json($top5Terjual->map(fn($item) => $item->nama_barang . ' (' . ($item->kategori ?? '-') . ' - ' . ($item->satuan ?? '-') . ')')),
                    terjual: @json($top5Terjual->pluck('total_terjual')),
                };

                function renderTopSoldChart() {
                    if (typeof ApexCharts === 'undefined' || !document.getElementById('topSoldChart')) return;

                    var options = {
                        series: [
                            {
                                name: 'Jumlah Terjual',
                                data: top5TerjualData.terjual
                            }
                        ],
                        chart: {
                            type: 'bar',
                            height: 300,
                            toolbar: { show: false },
                            fontFamily: 'Nunito, sans-serif',
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 6,
                                dataLabels: { position: 'bottom' },
                            },
                        },
                        colors: ['#4361ee'],
                        dataLabels: {
                            enabled: true,
                            style: { fontSize: '12px', colors: ['#fff'] },
                        },
                        xaxis: {
                            categories: top5TerjualData.labels,
                            labels: {
                                style: { colors: '#888ea8', fontSize: '12px' },
                            },
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#888ea8', fontSize: '12px' },
                            },
                        },
                        legend: { show: false },
                        grid: {
                            borderColor: '#e0e6ed',
                            strokeDashArray: 5,
                        },
                        tooltip: {
                            theme: 'dark',
                        },
                    };

                    var chart = new ApexCharts(document.getElementById('topSoldChart'), options);
                    chart.render();
                }

                document.addEventListener('DOMContentLoaded', function () {
                    renderTopSoldChart();
                });
            </script>
        </div>

    </div>
    {{-- Tabel Barang Expired Date (Transaksi Masuk) --}}
    <div class="mt-6 grid grid-cols-1 gap-6">
        <div class="panel">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                <h5 class="text-lg font-semibold dark:text-white-light">Barang Expired Date (Transaksi Masuk)</h5>
                <div class="flex items-center gap-2">
                    @if($jumlahKadaluarsa > 0)
                        <span class="badge bg-danger animate-pulse">{{ $jumlahKadaluarsa }} Kadaluarsa</span>
                    @endif
                    @if($jumlahHampirKadaluarsa > 0)
                        <span class="badge bg-warning">{{ $jumlahHampirKadaluarsa }} Hampir Kadaluarsa</span>
                    @endif
                    @if($jumlahKadaluarsa == 0 && $jumlahHampirKadaluarsa == 0)
                        <span class="badge bg-success">Semua Aman</span>
                    @endif
                </div>
            </div>

            @if($transaksiExpired->isEmpty())
                <div class="flex flex-col items-center justify-center py-10 text-white-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 opacity-40 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">Belum ada data barang transaksi masuk dengan expired date</p>
                </div>
            @else
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th class="ltr:rounded-l-md rtl:rounded-r-md">No</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Tanggal Masuk</th>
                                <th>Jumlah Masuk</th>
                                <th>Expired Date</th>
                                <th>Status Masa Simpan</th>
                                <th class="ltr:rounded-r-md rtl:rounded-l-md">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transaksiExpired as $item)
                            @php
                                $today = \Carbon\Carbon::today();
                                $expDate = \Carbon\Carbon::parse($item->expired_date)->startOfDay();
                                $diffDays = (int) $today->diffInDays($expDate, false);
                            @endphp
                            <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                                <td>{{ $transaksiExpired->firstItem() ? $transaksiExpired->firstItem() + $loop->index : $loop->iteration }}</td>
                                <td class="font-semibold text-black dark:text-white">
                                    {{ $item->barang->nama_barang ?? 'Barang tidak ditemukan' }}
                                    @if($item->barang && $item->barang->satuan)
                                        <span class="text-xs text-gray-400 font-normal">({{ $item->barang->satuan }})</span>
                                    @endif
                                </td>
                                <td>{{ $item->barang->kategori ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d F Y') }}</td>
                                <td>
                                    <span class="font-bold text-success">+{{ number_format($item->jumlah) }}</span>
                                </td>
                                <td>
                                    <span class="font-semibold {{ $diffDays < 0 ? 'text-danger' : ($diffDays <= 30 ? 'text-warning' : '') }}">
                                        {{ \Carbon\Carbon::parse($item->expired_date)->format('d F Y') }}
                                    </span>
                                </td>
                                <td>
                                    @if($diffDays < 0)
                                        <span class="badge bg-danger">Kadaluarsa ({{ abs($diffDays) }} hari lalu)</span>
                                    @elseif($diffDays == 0)
                                        <span class="badge bg-danger">Kadaluarsa Hari Ini</span>
                                    @elseif($diffDays <= 30)
                                        <span class="badge bg-warning">Hampir Kadaluarsa ({{ $diffDays }} hari lagi)</span>
                                    @else
                                        <span class="badge bg-success">Aman ({{ $diffDays }} hari lagi)</span>
                                    @endif
                                </td>
                                <td>{{ $item->keterangan ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $transaksiExpired->appends(request()->except('expired_page'))->links() }}
                </div>
            @endif
        </div>
    </div>
    {{-- Tabel Barang Di Bawah Minimum --}}
    <div class="mt-6 grid grid-cols-1 gap-6">
        <div class="panel">
            <div class="mb-5 flex items-center justify-between">
                <h5 class="text-lg font-semibold dark:text-white-light">Barang Di Bawah Stok Minimum</h5>
                @if($jumlahBawahMinimum > 0)
                    <span class="badge bg-danger animate-pulse">{{ $jumlahBawahMinimum }} Item</span>
                @else
                    <span class="badge bg-success">Semua Aman</span>
                @endif
            </div>

            @if($barangBawahMinimum->isEmpty())
                <div class="flex flex-col items-center justify-center py-10 text-white-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 opacity-40 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">Semua stok barang aman</p>
                </div>
            @else
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th class="ltr:rounded-l-md rtl:rounded-r-md">No</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Satuan</th>
                                <th>Stok Saat Ini</th>
                                <th>Batas Minimum</th>
                                <th class="ltr:rounded-r-md rtl:rounded-l-md">ROP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($barangBawahMinimum as $item)
                            <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                                <td>{{ $barangBawahMinimum->firstItem() ? $barangBawahMinimum->firstItem() + $loop->index : $loop->iteration }}</td>
                                <td class="font-semibold text-black dark:text-white">
                                    {{ $item->nama_barang }}
                                </td>
                                <td>{{ $item->kategori ?? '-' }}</td>
                                <td>{{ $item->satuan ?? '-' }}</td>
                                <td>
                                    <span class="font-bold text-danger">{{ $item->persediaan_akhir }}</span>
                                </td>
                                <td>{{ $item->stok_minimum }}</td>
                                <td>{{ number_format($item->reorder_point) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $barangBawahMinimum->appends(request()->except('page'))->links() }}
                </div>
            @endif
        </div>
    </div>


</div>
@endsection



