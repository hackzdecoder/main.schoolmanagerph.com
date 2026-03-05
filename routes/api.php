<?php
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\MessagingController;
use App\Http\Controllers\API\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticationController::class, 'authenticate_user']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthenticationController::class, 'logout']);

    // Students Routes
    Route::post('/students', [StudentController::class, 'students']);

    // Attendance Routes
    Route::post('/attendance', [AttendanceController::class, 'attendance']);
    Route::post('/attendance/filter', [AttendanceController::class, 'attendance_filter']);

    // Messages Routes
    Route::post('/messages', [MessagingController::class, 'messages']);
    Route::post('/messages/filter', [MessagingController::class, 'message_filter']);
});