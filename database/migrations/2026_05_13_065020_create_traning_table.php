<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traning', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_barang')
                  ->constrained('barang')
                  ->onDelete('cascade');

            $table->integer('persediaan_awal');
            $table->integer('pembelian');
            $table->integer('penjualan');
            $table->integer('persediaan_akhir');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traning');
    }
};