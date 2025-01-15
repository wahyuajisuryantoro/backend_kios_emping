<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\KategoriController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::apiResource('kategori', KategoriController::class);

    Route::prefix('barang')->group(function () {
        Route::get('/', [BarangController::class, 'index']);
        Route::post('/', [BarangController::class, 'store']);
        Route::get('/{kodeBarang}', [BarangController::class, 'show']);
        Route::put('/{kodeBarang}', [BarangController::class, 'update']);
        Route::delete('/{kodeBarang}', [BarangController::class, 'destroy']);
    });


});
