<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
    Route::post('/api/public/leads', [OnboardingController::class, 'createWebsiteLead'])->name('leads.website.store')->middleware('throttle:10,1');
    Route::post('/public/leads', [OnboardingController::class, 'createWebsiteLead'])->name('leads.website.store.compat')->middleware('throttle:10,1');
    Route::get('/api/onboarding/token/{token}', [OnboardingController::class, 'validateToken'])->name('onboarding.token.validate');
    Route::post('/api/onboarding/send-otp', [OnboardingController::class, 'sendOtp'])->name('onboarding.otp.send')->middleware('throttle:5,1');
    Route::post('/api/onboarding/verify-otp', [OnboardingController::class, 'verifyOtp'])->name('onboarding.otp.verify')->middleware('throttle:10,1');
    Route::post('/api/onboard', [OnboardingController::class, 'onboard'])->name('onboarding.complete')->middleware('throttle:10,1');
});


Route::middleware(['auth.blocked'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/password/change', [LoginController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/password/change', [LoginController::class, 'changePassword'])->name('password.change');
});
