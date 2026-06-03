<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'kategori', 'nama_barang', 'satuan',
        'stok_minimum'
    ];

    public function traning()
    {
        return $this->hasMany(Traning::class, 'id_barang');
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
    }
}