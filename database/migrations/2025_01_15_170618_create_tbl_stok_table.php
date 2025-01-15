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
        Schema::create('tbl_stok', function (Blueprint $table) {
            $table->id('id_stok');
            $table->string('kode_barang', 20);
            $table->integer('stok_awal');
            $table->integer('stok_masuk')->default(0);
            $table->integer('stok_keluar')->default(0);
            $table->integer('stok_akhir');
            $table->integer('batas_minimum_stok')->nullable();
            $table->string('lokasi_penyimpanan', 50)->nullable();
            $table->date('tanggal_stok');
            $table->enum('status_barang', ['Tersedia', 'Hampir Habis', 'Habis']);
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
        Schema::dropIfExists('tbl_stok');
    }
};
