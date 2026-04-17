<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SaleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('sales', SaleController::class)->except(['update']);
    });
});
