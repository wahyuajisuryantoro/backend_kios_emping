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
        // Modifikasi tbl_barang
        Schema::table('tbl_barang', function (Blueprint $table) {
            // Hapus kolom yang tidak diperlukan
            $table->dropColumn([
                'berat_barang',
                'volume_barang',
                'satuan_barang'
            ]);

            // Tambah kolom baru
            $table->enum('tipe_perhitungan', ['berat', 'unit', 'isi'])->after('kategori_id');
            $table->unsignedBigInteger('satuan_dasar_id')->after('tipe_perhitungan');
            $table->decimal('nilai_barang', 10, 2)->after('satuan_dasar_id')->comment('Nilai dalam satuan dasar');

            // Tambah foreign key
            $table->foreign('satuan_dasar_id')
                ->references('id')
                ->on('tbl_satuan')
                ->onDelete('restrict');
        });

        // Modifikasi tbl_stok
        Schema::table('tbl_stok', function (Blueprint $table) {
            $table->unsignedBigInteger('satuan_input_id')->after('kode_barang')->nullable();
            $table->unsignedBigInteger('satuan_akhir_id')->after('stok_akhir')->nullable();
            
            // Ubah tipe data kolom yang ada
            $table->decimal('stok_awal', 10, 2)->change();
            $table->decimal('stok_masuk', 10, 2)->change();
            $table->decimal('stok_keluar', 10, 2)->change();
            $table->decimal('stok_akhir', 10, 2)->change();
            
            // Tambah foreign keys
            $table->foreign('satuan_input_id')
                  ->references('id')
                  ->on('tbl_satuan')
                  ->onDelete('restrict');
                  
            $table->foreign('satuan_akhir_id')
                  ->references('id')
                  ->on('tbl_satuan')
                  ->onDelete('restrict');
        });

        // Modifikasi tbl_harga
        Schema::table('tbl_harga', function (Blueprint $table) {
            $table->unsignedBigInteger('satuan_jual_id')->after('kode_barang');

            $table->foreign('satuan_jual_id')
                ->references('id')
                ->on('tbl_satuan')
                ->onDelete('restrict');
        });

        // Modifikasi tbl_transaksi_detail
        Schema::table('tbl_transaksi_detail', function (Blueprint $table) {
            $table->decimal('quantity', 10, 2)->change();
            $table->unsignedBigInteger('satuan_transaksi_id')->after('quantity');

            $table->foreign('satuan_transaksi_id')
                ->references('id')
                ->on('tbl_satuan')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback tbl_barang
        Schema::table('tbl_barang', function (Blueprint $table) {
            $table->dropForeign(['satuan_dasar_id']);
            $table->dropColumn(['tipe_perhitungan', 'satuan_dasar_id', 'nilai_barang']);

            $table->decimal('berat_barang', 10, 2)->nullable();
            $table->string('volume_barang', 50)->nullable();
            $table->string('satuan_barang', 20)->nullable();
        });

        // Rollback tbl_stok
        Schema::table('tbl_stok', function (Blueprint $table) {
            $table->dropForeign(['satuan_input_id']);
            $table->dropForeign(['satuan_akhir_id']);
            $table->dropColumn(['satuan_input_id', 'satuan_akhir_id']);

            $table->integer('stok_awal')->change();
            $table->integer('stok_masuk')->change();
            $table->integer('stok_keluar')->change();
            $table->integer('stok_akhir')->change();
        });

        // Rollback tbl_harga
        Schema::table('tbl_harga', function (Blueprint $table) {
            $table->dropForeign(['satuan_jual_id']);
            $table->dropColumn('satuan_jual_id');
        });

        // Rollback tbl_transaksi_detail
        Schema::table('tbl_transaksi_detail', function (Blueprint $table) {
            $table->dropForeign(['satuan_transaksi_id']);
            $table->dropColumn('satuan_transaksi_id');
            $table->integer('quantity')->change();
        });
    }
};
