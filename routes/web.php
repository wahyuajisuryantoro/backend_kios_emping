<?php

use App\Http\Controllers\KategoriController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::apiResource('kategori', KategoriController::class);
});
