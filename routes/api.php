<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WaliMuridController;

Route::prefix('walimurid')->group(function () {
    Route::get('/check', [WaliMuridController::class, 'check']);
    Route::post('/register', [WaliMuridController::class, 'register']);
});
