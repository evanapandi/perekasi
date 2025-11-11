<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('restock_histories', function (Blueprint $table) {
            $table->id();
            $table->string('KODE');
            $table->string('nama_pereaksi');
            $table->integer('jumlah_restock');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restock_histories');
    }
};
