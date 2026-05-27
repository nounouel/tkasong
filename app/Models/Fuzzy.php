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
        'tanggal'
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}
