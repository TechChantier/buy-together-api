<?php

use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\ParticipationController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\PurchaseGoalController;
use Illuminate\Support\Facades\Route;

// Authentication endpoints
Route::controller(AuthenticationController::class)->group(function () {
    Route::post('register', 'register')->name('register');
    Route::post('login', 'login')->name('login');
    Route::post('logout', 'logout')->name('logout')->middleware('auth:sanctum');
});

// Purchase Goal endpoints
Route::controller(PurchaseGoalController::class)->group(function () {
    Route::post('purchase-goals/{purchase_goal}/change-status', 'changeStatus')->middleware('auth:sanctum');
});
Route::apiResource('purchase-goals', PurchaseGoalController::class)->middleware('auth:sanctum');

// Participation endpoints
Route::controller(ParticipationController::class)->group(function () {
    Route::get('purchase-goals/{purchase_goal}/participants', 'purchaseGoalParticipants')->middleware('auth:sanctum');
    Route::post('purchase-goals/{purchase_goal}/join', 'join')->middleware('auth:sanctum');
    Route::post('purchase-goals/{purchase_goal}/approve/{user_id}', 'approve')->middleware('auth:sanctum');
    Route::post('purchase-goals/{purchase_goal}/decline/{user_id}', 'decline')->middleware('auth:sanctum');
});

// Product endpoints
Route::apiResource('products', ProductController::class)->except(['index', 'create', 'show'])->middleware('auth:sanctum');
