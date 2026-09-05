<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/students/search', [\App\Http\Controllers\StudentSearchController::class, 'index']);

    Route::apiResource('users', \App\Http\Controllers\UserController::class)->except(['show']);

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
    Route::get('/assignments/{assignment}/submissions/mine', [\App\Http\Controllers\SubmissionController::class, 'mySubmission']);
    Route::get('/assignments/{assignment}/submissions', [\App\Http\Controllers\SubmissionController::class, 'index']);
    Route::post('/assignments/{assignment}/submissions', [\App\Http\Controllers\SubmissionController::class, 'store']);
    Route::get('/submissions/{submission}', [\App\Http\Controllers\SubmissionController::class, 'show']);
    Route::put('/submissions/{submission}/grade', [\App\Http\Controllers\SubmissionController::class, 'grade']);
});
