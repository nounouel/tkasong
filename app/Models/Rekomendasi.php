<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rekomendasi extends Model
{
    protected $table = 'rekomendasi';

    protected $fillable = [
        'id_barang',
        'stok_saat_ini',
        'rata_penjualan',
        'jumlah_direkomendasikan',
        'status',
        'dihasilkan_pada',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
