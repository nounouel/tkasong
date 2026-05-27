<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekomendasi', function (Blueprint $table) {

            $table->id();

            $table->foreignId('id_barang')
                  ->constrained('barang')
                  ->cascadeOnDelete();

            $table->integer('stok_saat_ini');

            $table->float('rata_penjualan');

            $table->integer('jumlah_direkomendasikan');

            $table->enum('status', [
                'pending',
                'diproses',
                'dibatalkan'
            ])->default('pending');

            $table->date('dihasilkan_pada');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekomendasi');
    }
};