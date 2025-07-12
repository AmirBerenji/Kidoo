<?php


use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DegreeController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NannyController;
use App\Http\Controllers\Api\ServiceController;

Route::prefix('user')->group(function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [AuthController::class, 'user']); // changed from 'user/user' to 'user/profile'
        Route::post('logout', [AuthController::class, 'logout']);
    });
});


Route::apiResource('nannies', NannyController::class);
Route::apiResource('locations', LocationController::class);
Route::apiResource('languages', LanguageController::class);
Route::apiResource('services', ServiceController::class);
Route::apiResource('degrees', DegreeController::class);
