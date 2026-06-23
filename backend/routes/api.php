<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\TicketScannerController;
use App\Http\Controllers\Api\LocaleController;
use App\Http\Controllers\Api\PersonalNumberController;
use App\Http\Controllers\Payment\PaymentCallbackController;
use App\Http\Controllers\Payment\ProductOrderController;
use App\Http\Controllers\Payment\TicketOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('locale')->group(function () {
    Route::get('/locale', [LocaleController::class, 'show']);

    // Admin auth
    Route::post('/admin/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/validate-ticket', [TicketScannerController::class, 'validateTicket']);
    });
});

// Order endpoints
Route::middleware(['locale', 'throttle:orders'])->group(function () {
    Route::post('/orders/tickets', [TicketOrderController::class, 'store']);
    Route::post('/orders/products', [ProductOrderController::class, 'store']);
    Route::post('/check-personal-number', [PersonalNumberController::class, 'check']);
});

// Payment callbacks
Route::middleware('throttle:payments')->group(function () {
    Route::get('/payments/callback', [PaymentCallbackController::class, 'handle'])
        ->middleware('quipu.hmac');
    Route::get('/payments/redirect', [PaymentCallbackController::class, 'redirect']);
});
