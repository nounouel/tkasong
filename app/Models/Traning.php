<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Traning extends Model
{
    protected $table = 'traning';

    protected $fillable = [
        'id_barang',
        'persediaan_awal',
        'pembelian',
        'penjualan',
        'persediaan_akhir'
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}