<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pereaksi', function (Blueprint $table) {
            $table->string('KODE')->primary();
            $table->string('ITEM');
            $table->string('TYPE');
            $table->integer('Stock')->default(0);
            $table->string('Status')->default('Out of Stock');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pereaksi');
    }
};
