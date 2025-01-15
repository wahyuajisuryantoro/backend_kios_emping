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
        Schema::create('tbl_barang', function (Blueprint $table) {
            $table->string('kode_barang', 20)->primary();
            $table->string('nama_barang', 100);
            $table->foreignId('kategori_id')->constrained('tbl_kategori');
            $table->string('merk_barang', 50)->nullable();
            $table->text('deskripsi_barang')->nullable();
            $table->decimal('berat_barang', 10, 2)->nullable();
            $table->string('volume_barang', 50)->nullable();
            $table->string('satuan_barang', 20)->nullable();
            $table->string('gambar_barang', 255)->nullable();
            $table->date('tanggal_kadaluarsa')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_barang');
    }
};
