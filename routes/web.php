<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::apiResource('kategori', KategoriController::class);

    Route::controller(BarangController::class)->group(function () {
        Route::get('barang/list', 'index');          
        Route::post('barang/create', 'store');
        Route::get('barang/{kodeBarang}', 'show');        
        Route::put('barang/{kodeBarang}', 'update');
        Route::delete('barang/{kodeBarang}', 'destroy');
    });

    Route::controller(TransaksiController::class)->group(function(){
        Route::post('penjualan', [TransaksiController::class, 'penjualan']);
        Route::get('penjualan/{noTransaksi}', [TransaksiController::class, 'show']);
        Route::get('penjualan/laporan/harian', [TransaksiController::class, 'getLaporanHarian']);
    });

});
