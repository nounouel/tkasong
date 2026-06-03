<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKeluar extends Model
{
    protected $table = 'transaksi_keluar';

    protected $fillable = [
        'id_barang',
        'tanggal',
        'jumlah',
        'keterangan',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    protected static function booted()
    {
        static::saved(function ($transaksi) {
            // Jika tanggal atau id_barang diubah, sinkronkan data yang lama juga
            if ($transaksi->wasChanged(['id_barang', 'tanggal'])) {
                $oldIdBarang = $transaksi->getOriginal('id_barang') ?? $transaksi->id_barang;
                $oldTanggal = $transaksi->getOriginal('tanggal') ?? $transaksi->tanggal;
                PenjualanAgregat::syncAgregatForBarang($oldIdBarang, $oldTanggal);
                app(\App\Service\StockControlService::class)->cekDanRekomendasiById($oldIdBarang);
            }
            PenjualanAgregat::syncAgregatForBarang($transaksi->id_barang, $transaksi->tanggal);
            app(\App\Service\StockControlService::class)->cekDanRekomendasiById($transaksi->id_barang);
        });

        static::deleted(function ($transaksi) {
            PenjualanAgregat::syncAgregatForBarang($transaksi->id_barang, $transaksi->tanggal);
            app(\App\Service\StockControlService::class)->cekDanRekomendasiById($transaksi->id_barang);
        });
    }
}
