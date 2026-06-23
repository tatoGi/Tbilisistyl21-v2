<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Api\LocaleController;
use Illuminate\Support\Facades\Route;

Route::middleware('locale')->group(function () {
    Route::get('/locale', [LocaleController::class, 'show']);

    // Admin auth
    Route::post('/admin/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});
