<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DegreeController;
use App\Http\Controllers\Api\LanguageApiController;
use App\Http\Controllers\Api\LocationApicontroller;
use App\Http\Controllers\Api\NannyApiController;
use App\Http\Controllers\Api\ServiceController;

Route::prefix('user')->group(function () {
    // Public routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [AuthController::class, 'user']); // changed from 'user/user' to 'user/profile'
        Route::post('logout', [AuthController::class, 'logout']);
        Route::put('update', [AuthController::class, 'update']);
    });
});


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('nannies', NannyApiController::class);
});



Route::apiResource('locations', LocationApicontroller::class);
Route::apiResource('languages', LanguageApiController::class);
Route::apiResource('services', ServiceController::class);
Route::apiResource('degrees', DegreeController::class);
