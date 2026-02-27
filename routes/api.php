<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-api', function (Request $request) {
    return response()->json([
        'message' => 'Testing is complete, Do backend now!',
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
