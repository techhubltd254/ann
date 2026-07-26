<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AdminPortalController;
use App\Http\Controllers\Web\CountyController;
use App\Http\Controllers\Web\CountySubscriptionController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DashboardV2Controller;
use App\Http\Controllers\Web\ExhibitionController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\MarketplaceController;
use App\Http\Controllers\Web\OperationsController;
use App\Http\Controllers\Web\ScreenController;
use App\Http\Controllers\Web\TravelController;
use App\Http\Controllers\Web\VenueController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\SocialAuthController;
use App\Http\Controllers\Web\Room3dController;

Route::get('/', HomeController::class)->name('home');

Route::get('/', HomeController::class)->name('home');

Route::get('/counties', [CountyController::class, 'index'])->name('counties.index');
Route::get('/counties/{county}', [CountyController::class, 'show'])->name('counties.show');
Route::get('/counties/{county}/sector/{sector}', [CountyController::class, 'sector'])->name('counties.sector');

// Marketplace
Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/marketplace/{slug}', [MarketplaceController::class, 'show'])->name('marketplace.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');

// County subscriptions
Route::get('/county/{slug}/subscriptions', [CountySubscriptionController::class, 'index'])->name('county.subscriptions');
Route::get('/subscriptions', [CountySubscriptionController::class, 'index'])->name('subscriptions.index');

// Dashboards
Route::get('/dashboard/county', [DashboardV2Controller::class, 'county'])->name('dashboard.county');
Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])->name('dashboard.admin');
Route::post('/dashboard/admin/delete-product/{id}', [AdminDashboardController::class, 'deleteProduct'])->name('admin.delete-product');
Route::post('/dashboard/admin/delete-user/{id}', [AdminDashboardController::class, 'deleteUser'])->name('admin.delete-user');
Route::post('/dashboard/admin/delete-order/{id}', [AdminDashboardController::class, 'deleteOrder'])->name('admin.delete-order');
Route::get('/admin', [AdminPortalController::class, 'index'])->name('admin.portal');

// Travel & Tourism
Route::get('/travel', [TravelController::class, 'index'])->name('travel.index');

// Platform operations (Advertising, SEO, Logistics)
Route::get('/operations', [OperationsController::class, 'index'])->name('operations.index');

Route::get('/exhibitions', [ExhibitionController::class, 'index'])->name('exhibitions.index');
Route::get('/exhibitions/{slug}', [ExhibitionController::class, 'show'])->name('exhibitions.show');

Route::get('/venues', [ExhibitionController::class, 'venues'])->name('venues.index');
Route::get('/venues/{venue}', [VenueController::class, 'show'])->name('venues.show');
Route::post('/venues/{venue}/inquire', [VenueController::class, 'inquire'])->name('venues.inquire');

// Exhibition screen videos
Route::get('/screens', [ScreenController::class, 'directory'])->name('screens.directory');
Route::get('/screens/{id}', [ScreenController::class, 'show'])->name('screens.show');

// 3D Exhibition experiences (standalone views)
Route::view('/exhibition-3d/map', 'exhibition-3d.map')->name('exhibition-3d.map');
Route::view('/exhibition-3d/sector', 'exhibition-3d.sector')->name('exhibition-3d.sector');
Route::view('/exhibition-3d/booth', 'exhibition-3d.booth')->name('exhibition-3d.booth');

// 3D Room Explorer
Route::get('/room3d', [Room3dController::class, 'index'])->name('room3d.index');
Route::get('/room3d/create', [Room3dController::class, 'create'])->name('room3d.create');
Route::post('/room3d', [Room3dController::class, 'store'])->name('room3d.store');
Route::get('/room3d/{room3d}', [Room3dController::class, 'show'])->name('room3d.show');
Route::get('/room3d/{room3d}/viewer', [Room3dController::class, 'viewer'])->name('room3d.viewer');
Route::get('/room3d/{room3d}/api', [Room3dController::class, 'api'])->name('room3d.api');

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
