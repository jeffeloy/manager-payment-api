<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\PaymentRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [PaymentRequestController::class, 'index'])->name('dashboard');

    Route::post('/payment-requests', [PaymentRequestController::class, 'store'])
        ->name('payment-requests.store');

    Route::patch('/payment-requests/{paymentRequest}/approve', [PaymentRequestController::class, 'approve'])
        ->name('payment-requests.approve');

    Route::patch('/payment-requests/{paymentRequest}/reject', [PaymentRequestController::class, 'reject'])
        ->name('payment-requests.reject');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
