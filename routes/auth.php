<?php

use App\Http\Controllers\Api\OnboardingController as ApiOnboardingController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/onboarding')
    ->middleware(['onboarding.signature'])
    ->group(function (): void {
        Route::post('create-user', [ApiOnboardingController::class, 'createUser']);
        Route::post('update-user', [ApiOnboardingController::class, 'updateUser']);
        Route::get('status', [ApiOnboardingController::class, 'status']);
    });

// Authentication Routes
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::middleware(['auth.blocked'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/password/change', [LoginController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/password/change', [LoginController::class, 'changePassword'])->name('password.change');
});
