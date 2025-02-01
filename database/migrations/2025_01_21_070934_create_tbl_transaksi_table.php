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
        Schema::create('tbl_transaksi', function (Blueprint $table) {
            $table->string('no_transaksi')->primary();
            $table->date('tanggal_transaksi');
            $table->enum('jenis_transaksi', ['Pembelian', 'Penjualan']);
            $table->string('no_referensi')->nullable();
            $table->decimal('total_harga', 12, 2);
            $table->decimal('total_diskon', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_transaksi');
    }
};
