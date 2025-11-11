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
        Schema::create('pereaksi', function (Blueprint $table) {
            $table->string('kode_reagent')->primary();
            $table->string('nama_reagent');
            $table->string('jenis_reagent');
            $table->integer('Stock')->default(0);
            $table->string('satuan', 6)->nullable();
            $table->integer('min_stock')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pereaksi');
    }
};
