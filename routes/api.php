<?php

use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\PurchaseGoalController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthenticationController::class)->group(function () {
    Route::post('register', 'register')->name('register');
    Route::post('login', 'login')->name('login');
    Route::post('logout', 'logout')->name('logout')->middleware('auth:sanctum');
});


Route::apiResource('purchase-goals', PurchaseGoalController::class)->middleware('auth:sanctum');
Route::apiResource('products', ProductController::class)->except(['index', 'create', 'show'])->middleware('auth:sanctum');

