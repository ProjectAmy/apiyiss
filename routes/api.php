<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WaliMuridController;

Route::prefix('walimurid')->group(function () {
    Route::post('/check', [WaliMuridController::class, 'check']);
    Route::post('/register', [WaliMuridController::class, 'register']);
});

Route::middleware('auth:sanctum')->prefix('walimurid')->group(function () {
    Route::get('/me', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });
});
