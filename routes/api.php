<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MidtransController;

Route::prefix('auth')->group(function () {
    Route::post('/check', [AuthController::class, 'check']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::get('/invoices', [InvoiceController::class, 'index']); // list invoices
    Route::post('/invoices', [InvoiceController::class, 'store']); // admin create invoice
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/invoices/{id}/snap-token', [InvoiceController::class, 'generateSnapToken']); // returns snap_token
});
Route::post('/midtrans/callback', [MidtransController::class, 'callback']); // webhook
