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
        // 1. Add columns to penjualan_agregat
        Schema::table('penjualan_agregat', function (Blueprint $table) {
            if (!Schema::hasColumn('penjualan_agregat', 'stok_terakhir')) {
                $table->integer('stok_terakhir')->default(0)->after('reorder_point');
            }
            if (!Schema::hasColumn('penjualan_agregat', 'rata_rata_penjualan_perhari')) {
                $table->decimal('rata_rata_penjualan_perhari', 8, 2)->default(0)->after('stok_terakhir');
            }
        });

        // 2. Populate data chronologically using optimized SQL updates
        if (DB::getDriverName() === 'sqlite') {
            // SQLite implementation
            DB::statement("
                UPDATE penjualan_agregat
                SET stok_terakhir = (
                    (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi_masuk WHERE id_barang = penjualan_agregat.id_barang AND tanggal <= penjualan_agregat.tanggal)
                    -
                    (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi_keluar WHERE id_barang = penjualan_agregat.id_barang AND tanggal <= penjualan_agregat.tanggal)
                )
            ");

            DB::statement("
                UPDATE penjualan_agregat
                SET rata_rata_penjualan_perhari = (
                    SELECT ROUND(
                        CAST(COALESCE(SUM(pa2.total_terjual), 0) AS REAL) / 
                        (julianday(penjualan_agregat.tanggal) - julianday((SELECT MIN(tanggal) FROM penjualan_agregat WHERE id_barang = penjualan_agregat.id_barang)) + 1),
                        2
                    )
                    FROM penjualan_agregat pa2
                    WHERE pa2.id_barang = penjualan_agregat.id_barang
                      AND pa2.tanggal <= penjualan_agregat.tanggal
                )
            ");
        } else {
            // 1. Create temp tables with index for transactions to speed up stok_terakhir calculation
            DB::statement("
                CREATE TEMPORARY TABLE temp_masuk (
                    id_barang INT,
                    tanggal DATE,
                    jumlah INT,
                    INDEX idx_barang_tanggal (id_barang, tanggal)
                )
            ");
            DB::statement("
                INSERT INTO temp_masuk (id_barang, tanggal, jumlah)
                SELECT id_barang, tanggal, SUM(jumlah) FROM transaksi_masuk GROUP BY id_barang, tanggal
            ");

            DB::statement("
                CREATE TEMPORARY TABLE temp_keluar (
                    id_barang INT,
                    tanggal DATE,
                    jumlah INT,
                    INDEX idx_barang_tanggal (id_barang, tanggal)
                )
            ");
            DB::statement("
                INSERT INTO temp_keluar (id_barang, tanggal, jumlah)
                SELECT id_barang, tanggal, SUM(jumlah) FROM transaksi_keluar GROUP BY id_barang, tanggal
            ");

            // 2. Update stok_terakhir using indexed temp tables
            DB::statement("
                UPDATE penjualan_agregat pa
                SET pa.stok_terakhir = (
                    (SELECT COALESCE(SUM(tm.jumlah), 0) FROM temp_masuk tm WHERE tm.id_barang = pa.id_barang AND tm.tanggal <= pa.tanggal)
                    -
                    (SELECT COALESCE(SUM(tk.jumlah), 0) FROM temp_keluar tk WHERE tk.id_barang = pa.id_barang AND tk.tanggal <= pa.tanggal)
                )
            ");

            DB::statement("DROP TEMPORARY TABLE temp_masuk");
            DB::statement("DROP TEMPORARY TABLE temp_keluar");

            // 3. Create temp table with index for penjualan_agregat to speed up average calculation
            DB::statement("
                CREATE TEMPORARY TABLE temp_agregat (
                    id INT PRIMARY KEY,
                    id_barang INT,
                    tanggal DATE,
                    total_terjual INT,
                    INDEX idx_barang_tanggal (id_barang, tanggal)
                )
            ");

            DB::statement("
                INSERT INTO temp_agregat (id, id_barang, tanggal, total_terjual)
                SELECT id, id_barang, tanggal, total_terjual FROM penjualan_agregat
            ");

            DB::statement("
                UPDATE penjualan_agregat pa
                JOIN (
                    SELECT id_barang, MIN(tanggal) as min_tgl
                    FROM penjualan_agregat
                    GROUP BY id_barang
                ) pa_min ON pa.id_barang = pa_min.id_barang
                SET pa.rata_rata_penjualan_perhari = (
                    SELECT ROUND(
                        COALESCE(SUM(ta.total_terjual), 0) / (DATEDIFF(pa.tanggal, pa_min.min_tgl) + 1),
                        2
                    )
                    FROM temp_agregat ta
                    WHERE ta.id_barang = pa.id_barang
                      AND ta.tanggal <= pa.tanggal
                )
            ");

            DB::statement("DROP TEMPORARY TABLE temp_agregat");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan_agregat', function (Blueprint $table) {
            $colsToDrop = [];
            if (Schema::hasColumn('penjualan_agregat', 'stok_terakhir')) {
                $colsToDrop[] = 'stok_terakhir';
            }
            if (Schema::hasColumn('penjualan_agregat', 'rata_rata_penjualan_perhari')) {
                $colsToDrop[] = 'rata_rata_penjualan_perhari';
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }
        });
    }
};
