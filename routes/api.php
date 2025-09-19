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
        Route::put('photo',[AuthController::class, 'updatePhoto']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // Nanny CRUD operations
    Route::apiResource('nannies', NannyApiController::class);

    // Nanny image management routes
    Route::post('nannies/upload-images', [NannyApiController::class, 'uploadImages']);
    Route::delete('nannies/delete-image', [NannyApiController::class, 'deleteImage']);
    Route::put('nannies/set-profile-photo', [NannyApiController::class, 'setProfilePhoto']);
});

// Public routes (no authentication required)
Route::apiResource('locations', LocationApicontroller::class);
Route::apiResource('languages', LanguageApiController::class);
Route::apiResource('services', ServiceController::class);
Route::apiResource('degrees', DegreeController::class);
