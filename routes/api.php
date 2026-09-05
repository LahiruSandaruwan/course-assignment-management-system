<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('courses', CourseController::class);
});
