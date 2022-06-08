<?php

use App\Http\Controllers\v1\CatDogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function(){
    Route::get('breeds/{breed?}', [CatDogController::class, 'getBreeds']);
    Route::get('list', [CatDogController::class, 'index']);
    Route::get('{image}', [CatDogController::class, 'getImage']);
});