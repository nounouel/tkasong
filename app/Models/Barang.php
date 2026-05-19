<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'nama_barang',
        'stok_minimum',
    ];

    public function traning()
    {
        return $this->hasMany(Traning::class, 'id_barang');
    }
}