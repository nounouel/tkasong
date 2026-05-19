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
    {{-- BARIS BAWAH: GRAFIK + TABEL BARANG BAWAH MINIMUM --}}
    {{-- ============================================================ --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

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
                    <p class="text-sm">Belum ada data traning</p>
                </div>
            @else
                <div x-data="{}" x-init="renderChart()">
                    <div id="barChart"></div>
                </div>
            @endif

            {{-- Data JSON untuk chart --}}
            <script>
                var top5Data = {
                    labels: @json($top5Menipis->pluck('nama_barang')),
                    stok: @json($top5Menipis->pluck('persediaan_akhir')),
                    minimum: @json($top5Menipis->pluck('stok_minimum')),
                };

                function renderChart() {
                    if (typeof ApexCharts === 'undefined' || !document.getElementById('barChart')) return;

                    var colors = top5Data.stok.map(function(val, i) {
                        return val < top5Data.minimum[i] ? '#e7515a' : '#00ab55';
                    });

                    var options = {
                        series: [
                            {
                                name: 'Stok Saat Ini',
                                data: top5Data.stok,
                            },
                            {
                                name: 'Batas Minimum',
                                data: top5Data.minimum,
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
                                distributed: true,
                                dataLabels: { position: 'bottom' },
                            },
                        },
                        colors: colors,
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

        {{-- Tabel: Barang Di Bawah Minimum --}}
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
                                <th class="ltr:rounded-l-md rtl:rounded-r-md">Nama Barang</th>
                                <th>Stok Saat Ini</th>
                                <th>Batas Minimum</th>
                                <th class="ltr:rounded-r-md rtl:rounded-l-md">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($barangBawahMinimum as $item)
                            <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                                <td class="font-semibold text-black dark:text-white">
                                    {{ $item->nama_barang }}
                                </td>
                                <td>
                                    <span class="font-bold text-danger">{{ $item->persediaan_akhir }}</span>
                                </td>
                                <td>{{ $item->stok_minimum }}</td>
                                <td>
                                    <span class="badge bg-danger shadow-md dark:group-hover:bg-transparent">
                                        Kritis
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
