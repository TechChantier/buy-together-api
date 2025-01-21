<?php

use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\ParticipationController;
use App\Http\Controllers\API\PurchaseGoalController;
use Illuminate\Support\Facades\Route;

// Authentication endpoints
Route::controller(AuthenticationController::class)->group(function () {
    Route::post('register', 'register')->name('register');
    Route::post('login', 'login')->name('login');
    Route::post('logout', 'logout')->name('logout')->middleware('auth:sanctum');
});

// Purchase Goal endpoints
Route::controller(PurchaseGoalController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::post('purchase-goals/{id}/change-status', 'changeStatus');
    Route::get('purchase-goals', 'index')->withoutMiddleware('auth:sanctum');
    Route::get('purchase-goals/{id}', 'show')->withoutMiddleware('auth:sanctum');
    Route::post('purchase-goals', 'store');
    Route::match(['put', 'patch'],'purchase-goals/{id}', 'update');
    Route::delete('purchase-goals/{id}', 'destroy');
});

// Participation endpoints
Route::controller(ParticipationController::class)->middleware(['auth:sanctum'])->group(function () {
    Route::get('purchase-goals/{id}/participants', 'purchaseGoalParticipants');
    Route::post('purchase-goals/{id}/join', 'join');
    Route::post('purchase-goals/{id}/leave', 'leave');
    Route::post('purchase-goals/{id}/approve/{user_id}', 'approve');
    Route::post('purchase-goals/{id}/decline/{user_id}', 'decline');
});
