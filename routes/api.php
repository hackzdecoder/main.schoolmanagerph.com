<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-api', function (Request $request) {
    return response()->json([
        'message' => 'Hello, World!2222',
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
