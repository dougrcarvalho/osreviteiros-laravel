<?php

use App\Http\Controllers\Api\FamiliaController as ApiFamiliaController;
use App\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:120,1')->group(function () {
    Route::get('/familias', [ApiFamiliaController::class, 'index'])->name('api.familias.index');
    Route::get('/categorias/opcoes', [ApiFamiliaController::class, 'categorias'])->name('api.categorias.opcoes');
});

Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::post('/familias/{familia:id}/download', [DownloadController::class, 'download'])->name('api.familias.download');
});
