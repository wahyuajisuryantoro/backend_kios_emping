<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\TransaksiController;
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

    // Route untuk barang
    Route::controller(BarangController::class)->group(function () {
        Route::get('barang/list', 'index');          
        Route::post('barang/create', 'store');
        Route::get('barang/{kodeBarang}', 'show');        
        Route::put('barang/{kodeBarang}', 'update');
        Route::delete('barang/{kodeBarang}', 'destroy');
    });

    // Route untuk transaksi
    Route::controller(TransaksiController::class)->group(function(){
        Route::post('penjualan', 'penjualan');
        Route::get('penjualan/{noTransaksi}', 'show');
        Route::get('penjualan/laporan/harian', 'getLaporanHarian');
    });
});