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
        Schema::create('restock_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kode_reagent')->index('fk_kode_pereaksi_rh');
            $table->string('nama_reagent');
            $table->string('jenis_reagent')->nullable();
            $table->integer('jumlah_restock');
            $table->string('satuan', 6)->nullable();
            $table->string('lot_numbers')->nullable();
            $table->date('expired_date')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restock_histories');
    }
};
