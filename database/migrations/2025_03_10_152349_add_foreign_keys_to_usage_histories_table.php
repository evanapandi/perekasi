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
        Schema::table('usage_histories', function (Blueprint $table) {
            $table->foreign(['kode_reagent'], 'fk_kode_pereaksi')->references(['kode_reagent'])->on('pereaksi')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usage_histories', function (Blueprint $table) {
            $table->dropForeign('fk_kode_pereaksi');
        });
    }
};
