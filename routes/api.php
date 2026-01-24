<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\AdminController;

// login wali
Route::prefix('wali')->group(function () {
    Route::post('/auth/check', [AuthController::class, 'check']);
    Route::post('/auth/register', [AuthController::class, 'register']);
});

// login keuangan
Route::post('/keuangan/auth/check', [AuthController::class, 'checkKeuangan']);

// login admin
Route::post('/admin/auth/check', [AuthController::class, 'checkAdmin']);

// midtrans webhook
Route::post('/midtrans/callback', [MidtransController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', function (\Illuminate\Http\Request $request) {
        return $request->user();
    });

    // Wali routes
    Route::prefix('wali')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']); // list invoices for user
        Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
        Route::post('/invoices/{id}/snap-token', [InvoiceController::class, 'generateSnapToken']); // returns snap_token
        Route::get('/students', [StudentController::class, 'getByWali']);
    });

    // Keuangan routes
    Route::prefix('keuangan')->group(function () {
        Route::post('/invoices/create', [InvoiceController::class, 'store']); // admin create invoice
        Route::post('/invoices/bulk', [InvoiceController::class, 'storeBulk']); // admin create invoice bulk
        Route::get('/invoices/list', [InvoiceController::class, 'adminIndex']); // admin view all invoices
        Route::post('/invoices/{id}/snap-token', [InvoiceController::class, 'generateSnapToken']); // admin bayar invoice
        Route::get('/students', [StudentController::class, 'index']);
    });

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/students', [StudentController::class, 'adminList']);
        Route::get('/students/unconnected', [StudentController::class, 'getUnconnected']); // Unconnected students
        Route::post('/students/connect', [StudentController::class, 'connectWali']); // Connect student to wali
        Route::post('/students', [StudentController::class, 'store']); // Create student
        Route::get('/walimurid', [AdminController::class, 'listWalimurid']); // List for dropdown
    });
});

// test API
Route::get('/test', function () {
    return response()->json(['message' => 'Test API']);
});

// Fallback login route to prevent 500 error when middleware redirects
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');



