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
        // stok_minimum is created directly in 2026_05_13_064944_create_barang_table.php
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed as stok_minimum is dropped when dropping the whole table
    }
};
