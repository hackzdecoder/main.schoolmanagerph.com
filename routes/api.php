<?php
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthenticationController;
use App\Http\Controllers\API\MessagingController;
use App\Http\Controllers\API\StudentController;
use Illuminate\Support\Facades\Route;

Route::post('/refresh', [AuthenticationController::class, 'refresh']);
Route::post('/login', [AuthenticationController::class, 'authenticate_user']);

Route::post('/mailer', [AuthenticationController::class, 'test_mailer']);

// Route::get('/test-db-config', function () {
//     return [
//         'database' => env('APP_ENV') === 'dev' ? env('DB_DATABASE_DEV') : env('DB_DATABASE_PROD'),
//         'username' => env('APP_ENV') === 'dev' ? env('DB_USERNAME_DEV') : env('DB_USERNAME_PROD'),
//         'password' => env('APP_ENV') === 'dev' ? env('DB_PASSWORD_DEV') : env('DB_PASSWORD_PROD'),
//     ];
// });

Route::middleware(['auth:sanctum'])->group(function () {

    // Students Routes
    Route::prefix('student')->group(function () {
        // Student Module
        Route::get('/profile', [StudentController::class, 'students']);

        // Attendance Modules
        Route::get('/attendance', [StudentController::class, 'student_attendance']);
        Route::get('/attendance/fullname', [StudentController::class, 'student_attendance_fullname']);

        // Messaging Modules
        Route::get('/messages', [StudentController::class, 'student_messages']);
        Route::get('/messages/fullname', [StudentController::class, 'student_messages_fullname']);
    });

    // Attendance Routes
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'attendance']);
        Route::post('/filter', [AttendanceController::class, 'attendance_filter']);
        Route::post('/filter/fullname', [AttendanceController::class, 'attendance_filter_by_fullname']);
        Route::post('/filter/date-range', [AttendanceController::class, 'attendance_filter_by_date_range']);
    });

    // Messages Routes
    Route::prefix('messages')->group(function () {
        Route::get('/', [MessagingController::class, 'messages']);
        Route::post('/filter', [AttendanceController::class, 'messages_filter']);
        Route::post('/filter/fullname', [MessagingController::class, 'messages_filter_by_fullname']);
        Route::post('/filter/date-range', [MessagingController::class, 'messages_filter_by_date_range']);
    });

    Route::post('/logout', [AuthenticationController::class, 'logout']);

});