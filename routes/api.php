<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('courses', CourseController::class);
    
    // Enrollment routes
    Route::get('/courses/{course}/students', [\App\Http\Controllers\CourseStudentController::class, 'index']);
    Route::post('/courses/{course}/students', [\App\Http\Controllers\CourseStudentController::class, 'store']);
    Route::delete('/courses/{course}/students/{student}', [\App\Http\Controllers\CourseStudentController::class, 'destroy']);

    // Assignment routes
    Route::get('/courses/{course}/assignments', [\App\Http\Controllers\AssignmentController::class, 'index']);
    Route::post('/courses/{course}/assignments', [\App\Http\Controllers\AssignmentController::class, 'store']);
    Route::get('/assignments/{assignment}', [\App\Http\Controllers\AssignmentController::class, 'show']);
    Route::put('/assignments/{assignment}', [\App\Http\Controllers\AssignmentController::class, 'update']);
    Route::delete('/assignments/{assignment}', [\App\Http\Controllers\AssignmentController::class, 'destroy']);

    // Submission routes
    Route::get('/assignments/{assignment}/submissions', [\App\Http\Controllers\SubmissionController::class, 'index']);
    Route::post('/assignments/{assignment}/submissions', [\App\Http\Controllers\SubmissionController::class, 'store']);
    Route::get('/submissions/{submission}', [\App\Http\Controllers\SubmissionController::class, 'show']);
});
