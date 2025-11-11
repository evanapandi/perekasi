<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('usage_histories', function (Blueprint $table) {
            $table->id();
            $table->string('nama_analis');
            $table->string('KODE');
            $table->string('jenis_pereaksi');
            $table->integer('jumlah_penggunaan');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('usage_histories');
    }
};
