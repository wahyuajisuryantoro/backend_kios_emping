<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\StokController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Route yang memerlukan autentikasi
Route::middleware('auth:sanctum')->group(function () {
    // Route untuk user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Route untuk kategori
    Route::apiResource('kategori', KategoriController::class);

    // Route untuk satuan
    Route::controller(SatuanController::class)->group(function () {
        Route::get('satuan', 'index');
        Route::get('satuan/{tipe}', 'getSatuanByTipe');
        Route::get('satuan/konversi/{id}', 'getKonversi');
        Route::post('satuan', 'store');
        Route::put('satuan/{id}', 'update');
        Route::delete('satuan/{id}', 'destroy');
    });

    // Route untuk barang
    Route::controller(BarangController::class)->group(function () {
        Route::get('barang/list', 'index');
        Route::post('barang/create', 'store');
        Route::get('barang/{kodeBarang}', 'show');
        Route::put('barang/{kodeBarang}', 'update');
        Route::delete('barang/{kodeBarang}', 'destroy');
        // Endpoint baru untuk validasi stok dan harga
        Route::post('barang/validate-stock', 'validateStock');
        Route::get('barang/types', 'getBarangTypes');
    });

    Route::controller(TransaksiController::class)->group(function () {
        Route::get('transaksi/barang', 'getBarang');
        Route::post('transaksi-penjualan', 'store');
        Route::post('transaksi/harga', 'getHarga');
    });
    // Route::controller(TransaksiController::class)->group(function(){
    //     Route::post('penjualan', 'penjualan');
    //     Route::get('penjualan/{noTransaksi}', 'show');
    //     Route::get('penjualan/laporan/harian', 'getLaporanHarian');
    // });

    // // Route untuk stok
    // Route::controller(StokController::class)->group(function () {
    //     Route::get('stok/{kodeBarang}', 'getStok');
    //     Route::post('stok/masuk', 'stokMasuk');
    //     Route::post('stok/keluar', 'stokKeluar');
    //     Route::post('stok/adjust', 'adjustStok');
    //     Route::get('stok/history/{kodeBarang}', 'getStokHistory');
    //     // Endpoint untuk konversi stok
    //     Route::post('stok/konversi', 'konversiStok');
    //     Route::post('stok/check-konversi', 'checkKonversi');
    // });

    // Route untuk transaksi


    Route::post('/logout', [AuthController::class, 'logout']);
});
