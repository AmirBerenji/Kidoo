<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DegreeController;
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

        Route::post('/{id}/reviews', [ReviewController::class, 'storeNurseReview']);
        Route::get('/{id}/reviews', [ReviewController::class, 'getNurseReviews']);
        Route::get('/{id}/reviews/check', [ReviewController::class, 'checkUserReview'])
            ->defaults('type', 'nurse');

        // Update and Delete Review
        Route::put('/reviews/{id}', [ReviewController::class, 'updateReview']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'deleteReview']);
    });
});


Route::apiResource('locations', LocationApicontroller::class);
Route::apiResource('languages', LanguageApiController::class);
Route::apiResource('services', ServiceController::class);
Route::apiResource('degrees', DegreeController::class);
