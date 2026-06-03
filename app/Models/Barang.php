<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'id_kategori', 'nama_barang', 'satuan',
        'stok_minimum'
    ];

    public function traning()
    {
        return $this->hasMany(Traning::class, 'id_barang');
    }

    public function kategoriRelation()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori');
    }

    public function getKategoriAttribute()
    {
        return $this->kategoriRelation ? $this->kategoriRelation->nama_kategori : '-';
    }

    protected static function booted()
    {
        static::saved(function ($barang) {
            if ($barang->wasChanged('stok_minimum')) {
                $agregates = \DB::table('penjualan_agregat')
                    ->where('id_barang', $barang->id)
                    ->get();

                foreach ($agregates as $agregat) {
                    $d = $agregat->total_terjual;
                    $L = 2; // Lead Time
                    $ss = $barang->stok_minimum;
                    $rop = ($d * $L) + $ss;

                    \DB::table('penjualan_agregat')
                        ->where('id', $agregat->id)
                        ->update(['reorder_point' => $rop]);
                }
            }
        });

        static::created(function ($barang) {
            // Jalankan cek rekomendasi awal karena barang baru biasanya stoknya 0 (di bawah ROP)
            app(\App\Service\StockControlService::class)->cekDanRekomendasi($barang);
        });
    }

    public function getReorderPointAttribute()
    {
        $latestAgregat = \App\Models\PenjualanAgregat::where('id_barang', $this->id)
            ->orderBy('tanggal', 'desc')
            ->first();
        return $latestAgregat ? $latestAgregat->reorder_point : ($this->stok_minimum ?? 10);
    }
}