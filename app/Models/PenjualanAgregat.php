<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanAgregat extends Model
{
    protected $table = 'penjualan_agregat';

    protected $fillable = [
        'id_barang',
        'tanggal',
        'total_terjual',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
