<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\KategoriController;
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


});
