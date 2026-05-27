<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'kategori', 'nama_barang', 'satuan',
        'stok_minimum', 'reorder_point'
    ];

    public function traning()
    {
        return $this->hasMany(Traning::class, 'id_barang');
    }
}