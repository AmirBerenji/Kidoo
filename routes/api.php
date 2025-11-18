<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DegreeController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\LanguageApiController;
use App\Http\Controllers\Api\LocationApicontroller;
use App\Http\Controllers\Api\NannyApiController;
use App\Http\Controllers\Api\ReviewController;
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
        Route::post('photo',[AuthController::class, 'updatePhoto']);
    });
});


Route::prefix('nannies')->group(function () {

    Route::get('', [NannyApiController::class, 'index']);


    Route::middleware('auth:sanctum')->group(function () {
        Route::post('', [NannyApiController::class, 'store']);
        Route::get('{id}', [NannyApiController::class, 'showById']);
        Route::put('{id}', [NannyApiController::class, 'update']); // for updating nanny
        Route::get('/user/info',[NannyApiController::class,'showByUserId']);

    });
});

Route::prefix('reviews')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        // Review CRUD endpoints
        Route::post('/', [ReviewController::class, 'store']);           // Create review
        Route::get('/', [ReviewController::class, 'index']);            // Get reviews list
        Route::put('/{id}', [ReviewController::class, 'update']);      // Update review
        Route::delete('/{id}', [ReviewController::class, 'destroy']);  // Delete review
        Route::get('/check', [ReviewController::class, 'checkUserReview']); // Check if user reviewed

    });
});

Route::prefix('doctors')->group(function () {
    Route::get('/', [DoctorController::class, 'index']);
    Route::post('/', [DoctorController::class, 'store']);
    Route::get('/{id}', [DoctorController::class, 'show']);
    Route::put('/{id}', [DoctorController::class, 'update']);
    Route::post('/{id}', [DoctorController::class, 'update']); // For form-data with _method=PUT
    Route::delete('/{id}', [DoctorController::class, 'destroy']);
});




Route::apiResource('locations', LocationApicontroller::class);
Route::apiResource('languages', LanguageApiController::class);
Route::apiResource('services', ServiceController::class);
Route::apiResource('degrees', DegreeController::class);
