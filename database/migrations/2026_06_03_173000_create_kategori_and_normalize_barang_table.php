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
        // 1. Create kategori table
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori', 100)->unique();
            $table->timestamps();
        });

        // 2. Add id_kategori column to barang
        Schema::table('barang', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kategori')->nullable()->after('id');
        });

        // 3. Move existing string categories to kategori table and map them
        $barangKategori = DB::table('barang')
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->pluck('kategori')
            ->unique();

        foreach ($barangKategori as $katName) {
            $id = DB::table('kategori')->insertGetId([
                'nama_kategori' => $katName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('barang')
                ->where('kategori', $katName)
                ->update(['id_kategori' => $id]);
        }

        // 4. Drop the old string column and add foreign key constraint
        Schema::table('barang', function (Blueprint $table) {
            $table->dropColumn('kategori');
            $table->foreign('id_kategori')->references('id')->on('kategori')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['id_kategori']);
            $table->string('kategori')->nullable()->after('id');
        });

        // Restore categories
        $barangs = DB::table('barang')
            ->join('kategori', 'barang.id_kategori', '=', 'kategori.id')
            ->select('barang.id', 'kategori.nama_kategori')
            ->get();

        foreach ($barangs as $b) {
            DB::table('barang')
                ->where('id', $b->id)
                ->update(['kategori' => $b->nama_kategori]);
        }

        Schema::table('barang', function (Blueprint $table) {
            $table->dropColumn('id_kategori');
        });

        Schema::dropIfExists('kategori');
    }
};
