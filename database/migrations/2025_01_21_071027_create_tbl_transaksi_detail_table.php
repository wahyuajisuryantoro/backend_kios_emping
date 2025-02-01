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
        Schema::create('tbl_transaksi_detail', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi');
            $table->string('kode_barang', 20);
            $table->integer('quantity');
            $table->decimal('harga_satuan', 10, 2);
            $table->decimal('diskon', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->foreign('no_transaksi')->references('no_transaksi')->on('tbl_transaksi');
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
        Schema::dropIfExists('tbl_transaksi_detail');
    }
};
