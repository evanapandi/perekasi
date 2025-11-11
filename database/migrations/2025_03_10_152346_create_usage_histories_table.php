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
        Schema::create('usage_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nama_analis');
            $table->string('kode_reagent')->index('fk_kode_pereaksi');
            $table->string('nama_reagent')->nullable();
            $table->string('jenis_reagent');
            $table->integer('jumlah_penggunaan');
            $table->string('satuan', 6)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_histories');
    }
};
