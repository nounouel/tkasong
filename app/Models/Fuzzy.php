<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fuzzy extends Model
{
    protected $table = 'fuzzies';

    protected $fillable = [
        'id_barang',
        'permintaan',
        'stok',
        'hasil_fuzzy',
        'nilai_crisp',
        'tanggal',
        'rata_rata_penjualan_perhari'
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function getStokSaatIniAttribute()
    {
        return $this->stok;
    }

    public function getRataPenjualanAttribute()
    {
        return $this->permintaan;
    }

    public function getJumlahDirekomendasikanAttribute()
    {
        return $this->nilai_crisp;
    }

    public function getDihasilkanPadaAttribute()
    {
        return $this->tanggal;
    }

    public function getKategoriFuzzyAttribute()
    {
        return $this->hasil_fuzzy;
    }

    public function getReorderPointAttribute()
    {
        $latestAgregat = \App\Models\PenjualanAgregat::where('id_barang', $this->id_barang)
            ->orderBy('tanggal', 'desc')
            ->first();
        return $latestAgregat ? $latestAgregat->reorder_point : 10;
    }
}
