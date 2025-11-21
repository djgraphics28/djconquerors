<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiAuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [ApiAuthController::class, 'register']);
Route::post('/login', [ApiAuthController::class, 'login']);
Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword']);
Route::post('/logout', [ApiAuthController::class, 'logout'])->middleware('auth:sanctum');

