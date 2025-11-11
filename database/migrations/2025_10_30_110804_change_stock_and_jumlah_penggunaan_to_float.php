<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pereaksi', function (Blueprint $table) {
            $table->float('Stock', 15, 3)->change();
        });
        Schema::table('usage_histories', function (Blueprint $table) {
            $table->float('jumlah_penggunaan', 15, 3)->change();
        });
        Schema::table('restock_histories', function (Blueprint $table) {
            $table->float('jumlah_restock', 15, 3)->change();
        });
    }
    public function down(): void
    {
        Schema::table('pereaksi', function (Blueprint $table) {
            $table->decimal('Stock', 15, 3)->change();
        });
        Schema::table('usage_histories', function (Blueprint $table) {
            $table->decimal('jumlah_penggunaan', 15, 3)->change();
        });
        Schema::table('restock_histories', function (Blueprint $table) {
            $table->decimal('jumlah_restock', 15, 3)->change();
        });
    }
};