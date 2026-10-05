<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\MfaController;
use App\Http\Controllers\Web\SocialAuthController;
use Illuminate\Support\Facades\Route;

// MFA (Multi-Factor Authentication)
Route::middleware('auth')->group(function () {
    Route::get('/mfa/setup', [MfaController::class, 'showSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [MfaController::class, 'confirmSetup'])->name('mfa.setup.confirm');
    Route::post('/mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');
});
Route::get('/mfa/challenge', [MfaController::class, 'showChallenge'])->name('mfa.challenge');
Route::post('/mfa/challenge', [MfaController::class, 'verifyChallenge'])->name('mfa.challenge.verify');

// Registration & Login (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register/send-code', [AuthController::class, 'sendCode'])->name('register.send-code');
    Route::get('/register/verify', [AuthController::class, 'showVerify'])->name('register.verify');
    Route::post('/register/verify', [AuthController::class, 'verifyCode']);
    Route::get('/register/details', [AuthController::class, 'showDetails'])->name('register.details');
    Route::post('/register/complete', [AuthController::class, 'completeRegistration'])->name('register.complete');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/send-code', [AuthController::class, 'sendLoginCode'])->name('login.send-code');
    Route::get('/login/code', [AuthController::class, 'showLoginCode'])->name('login.code');
    Route::post('/login/code/verify', [AuthController::class, 'verifyLoginCode'])->name('login.code.verify');

    // Password reset
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Authenticated-only
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Hidden admin login (no public link — admins must know the URL)
Route::get('/kicc-admin/login', [AuthController::class, 'showAdminLogin'])->name('auth.admin-login.form');
Route::post('/kicc-admin/login', [AuthController::class, 'adminLogin'])->name('auth.admin-login');
Route::get('/kicc-admin/reset-pwd', [AuthController::class, 'resetAdminPassword']);
Route::get('/kicc-admin/diag-interlink', [\App\Http\Controllers\Web\KiccAdminController::class, 'diagInterlink']);

// Social OAuth
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);// Admin password reset (uses a strong one-time token from .env for security)
Route::get('/__reset-admin-pwd/{token}', [AuthController::class, 'resetAdminPassword']);
