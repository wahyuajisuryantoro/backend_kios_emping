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
        Schema::create('tbl_harga', function (Blueprint $table) {
            $table->id('id_harga');
            $table->string('kode_barang', 20);
            $table->decimal('harga_beli', 10, 2);
            $table->decimal('harga_jual_eceran', 10, 2);
            $table->decimal('harga_jual_grosir', 10, 2);
            $table->integer('min_qty_grosir')->default(1);
            $table->decimal('diskon_eceran', 5, 2)->nullable();
            $table->decimal('diskon_grosir', 5, 2)->nullable();
            $table->date('tanggal_perubahan_harga');
            $table->foreign('kode_barang')->references('kode_barang')->on('tbl_barang');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_harga');
    }
};
