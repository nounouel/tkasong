<?php

namespace App\Console\Commands;

use App\Service\StockControlService;
use Illuminate\Console\Command;

class CekRekomendasiStok extends Command
{
    protected $signature = 'stok:cekrekomendasi';
    protected $description = 'Cek stok terhadap ROP dan hasilkan rekomendasi fuzzy';

    protected $stockService;

    public function __construct(StockControlService $stockService)
    {
        parent::__construct();
        $this->stockService = $stockService;
    }

    public function handle()
    {
        $this->info('Memulai pengecekan stok...');
        $rekomendasi = $this->stockService->cekSemuaBarang();

        $this->info('Pengecekan selesai.');
        $this->table(
            ['Barang', 'Stok Saat Ini', 'ROP', 'Rata Penjualan', 'Rekomendasi'],
            collect($rekomendasi)->map(fn($item) => [
                $item['barang'],
                $item['stok_saat_ini'],
                $item['reorder_point'],
                $item['rata_penjualan'],
                $item['rekomendasi']
            ])
        );

        return Command::SUCCESS;
    }
}