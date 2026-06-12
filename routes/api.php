<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaymentRequestController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/payment-requests', [PaymentRequestController::class, 'index']);
    Route::post('/payment-requests', [PaymentRequestController::class, 'store']);
    Route::get('/payment-requests/{paymentRequest}', [PaymentRequestController::class, 'show']);
    Route::patch('/payment-requests/{paymentRequest}/approve', [PaymentRequestController::class, 'approve']);
    Route::patch('/payment-requests/{paymentRequest}/reject', [PaymentRequestController::class, 'reject']);
});
