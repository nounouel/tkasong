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
        Schema::table('fuzzies', function (Blueprint $table) {
            $table->decimal('rata_rata_penjualan_perhari', 8, 2)->default(0)->after('nilai_crisp');
                    $table->enum('status', [
                'pending',
                'diproses',
                'dibatalkan'
            ])->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('fuzzies', function (Blueprint $table) {
            $table->dropColumn('rata_rata_penjualan_perhari');
            $table->dropColumn('status');
        });
    }
};
