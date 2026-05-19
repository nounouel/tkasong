<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('fuzzies', function ($table) {
        $table->double('nilai_crisp')->nullable()->after('hasil_fuzzy');
    });
}

public function down()
{
    Schema::table('fuzzies', function ($table) {
        $table->dropColumn('nilai_crisp');
    });
}
};
