<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\StudentController;

Route::prefix('auth')->group(function () {
    Route::post('/check', [AuthController::class, 'check']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });

    // Walli routes
    Route::prefix('wali')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']); // list invoices for user
        Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
        Route::post('/invoices/{id}/snap-token', [InvoiceController::class, 'generateSnapToken']); // returns snap_token
    });

    // Keuangan routes
    Route::prefix('keuangan')->group(function () {
        Route::post('/invoices/create', [InvoiceController::class, 'store']); // admin create invoice
        Route::post('/invoices/bulk', [InvoiceController::class, 'storeBulk']); // admin create invoice bulk
        Route::get('/invoices/list', [InvoiceController::class, 'adminIndex']); // admin view all invoices
        Route::get('/students', [StudentController::class, 'index']);
    });
});
Route::post('/midtrans/callback', [MidtransController::class, 'callback']); // webhook
