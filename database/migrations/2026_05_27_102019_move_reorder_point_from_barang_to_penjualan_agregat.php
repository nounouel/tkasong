<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add column to penjualan_agregat
        Schema::table('penjualan_agregat', function (Blueprint $table) {
            $table->integer('reorder_point')->default(0)->after('total_terjual');
        });

        // 2. Transfer data
        DB::table('penjualan_agregat')->get()->each(function ($pa) {
            $barang = DB::table('barang')->where('id', $pa->id_barang)->first();
            if ($barang && isset($barang->reorder_point)) {
                DB::table('penjualan_agregat')
                    ->where('id', $pa->id)
                    ->update(['reorder_point' => $barang->reorder_point]);
            }
        });

        // 3. Drop column from barang
        Schema::table('barang', function (Blueprint $table) {
            $table->dropColumn('reorder_point');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add column to barang
        Schema::table('barang', function (Blueprint $table) {
            $table->integer('reorder_point')->default(0)->after('stok_minimum');
        });

        // 2. Restore data
        DB::table('penjualan_agregat')->get()->each(function ($pa) {
            DB::table('barang')
                ->where('id', $pa->id_barang)
                ->update(['reorder_point' => $pa->reorder_point]);
        });

        // 3. Drop column from penjualan_agregat
        Schema::table('penjualan_agregat', function (Blueprint $table) {
            $table->dropColumn('reorder_point');
        });
    }
};
