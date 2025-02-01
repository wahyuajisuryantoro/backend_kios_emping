<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom toko_id ke tbl_kategori
        Schema::table('tbl_kategori', function (Blueprint $table) {
            $table->foreignId('toko_id')
                  ->constrained('tbl_toko')
                  ->onDelete('cascade');
        });

        // Tambah kolom toko_id ke tbl_brand
        Schema::table('tbl_brand', function (Blueprint $table) {
            $table->foreignId('toko_id')->after('id')
                  ->constrained('tbl_toko')
                  ->onDelete('cascade');
        });

        // Tambah kolom toko_id ke tbl_barang
        Schema::table('tbl_barang', function (Blueprint $table) {
            $table->foreignId('toko_id')->after('kode_barang')
                  ->constrained('tbl_toko')
                  ->onDelete('cascade');
        });

        Schema::table('tbl_stok', function (Blueprint $table) {
            $table->foreignId('toko_id')
                  ->nullable()
                  ->after('id_stok')
                  ->constrained('tbl_toko')
                  ->onDelete('cascade');
        });

        // Tambah kolom toko_id ke tbl_transaksi
        Schema::table('tbl_transaksi', function (Blueprint $table) {
            $table->foreignId('toko_id')->after('no_transaksi')
                  ->constrained('tbl_toko')
                  ->onDelete('cascade');
            $table->foreignId('user_id')->after('toko_id')
                  ->constrained('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_kategori', function (Blueprint $table) {
            $table->dropForeign(['toko_id']);
            $table->dropColumn('toko_id');
        });

        Schema::table('tbl_brand', function (Blueprint $table) {
            $table->dropForeign(['toko_id']);
            $table->dropColumn('toko_id');
        });

        Schema::table('tbl_barang', function (Blueprint $table) {
            $table->dropForeign(['toko_id']);
            $table->dropColumn('toko_id');
        });

        Schema::table('tbl_transaksi', function (Blueprint $table) {
            $table->dropForeign(['toko_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['toko_id', 'user_id']);
        });
    }
};