<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CountyController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ExhibitionController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ScreenController;
use App\Http\Controllers\Web\VenueController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\SocialAuthController;

Route::get('/', HomeController::class)->name('home');

Route::get('/counties', [CountyController::class, 'index'])->name('counties.index');
Route::get('/counties/{county}', [CountyController::class, 'show'])->name('counties.show');
Route::get('/counties/{county}/sector/{sector}', [CountyController::class, 'sector'])->name('counties.sector');

Route::get('/exhibitions', [ExhibitionController::class, 'index'])->name('exhibitions.index');
Route::get('/exhibitions/{slug}', [ExhibitionController::class, 'show'])->name('exhibitions.show');

Route::get('/venues', [ExhibitionController::class, 'venues'])->name('venues.index');
Route::get('/venues/{venue}', [VenueController::class, 'show'])->name('venues.show');

// Exhibition screen videos
Route::get('/screens', [ScreenController::class, 'directory'])->name('screens.directory');
Route::get('/screens/{id}', [ScreenController::class, 'show'])->name('screens.show');

// 3D Exhibition experiences (standalone views)
Route::view('/exhibition-3d/map', 'exhibition-3d.map')->name('exhibition-3d.map');
Route::view('/exhibition-3d/sector', 'exhibition-3d.sector')->name('exhibition-3d.sector');
Route::view('/exhibition-3d/booth', 'exhibition-3d.booth')->name('exhibition-3d.booth');

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
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/exhibitions', [DashboardController::class, 'exhibitions'])->name('dashboard.exhibitions');
    Route::get('/dashboard/bookings', [DashboardController::class, 'bookings'])->name('dashboard.bookings');
    Route::get('/dashboard/profile', [DashboardController::class, 'profile'])->name('dashboard.profile');
    Route::post('/dashboard/profile', [DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
});

// Google OAuth
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
