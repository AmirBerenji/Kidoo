<?php


use App\Http\Controllers\Api\AuthController;

Route::post('/user/register', [AuthController::class, 'register']);
Route::post('/user/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/user', [AuthController::class, 'user']);
    Route::post('/user/logout', [AuthController::class, 'logout']);
});
