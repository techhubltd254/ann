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

// [DEBUG: Remove after fix] Step-by-step diagnosis of 500 pages
Route::get('/__debug/{slug}', function ($slug) {
    $tests = [];
    try {
        if ($slug === 'county') {
            $tests['query county'] = \App\Models\County::where('slug', 'mombasa')->firstOrFail()->name;
            $county = \App\Models\County::where('slug', 'mombasa')->firstOrFail();
            $tests['load sectors'] = $county->sectors()->count();
            $tests['load tourismAttr'] = $county->tourismAttractions()->count();
            $tests['load hotels'] = $county->hotels()->count();
            $tests['load products'] = $county->products()->count();
            $tests['load institutions'] = $county->institutions()->count();
            $tests['load farms'] = $county->farms()->count();
            $tests['load transport'] = $county->transport()->count();
            $tests['load health'] = $county->healthFacilities()->count();
            $tests['load culture'] = $county->cultureSites()->count();
            $tests['render view'] = view('counties.show', compact('county'))->renderSections()['content'][0] ?? 'partial';
        } elseif ($slug === 'operations') {
            $tests['Campaign'] = \App\Models\Advertising\Campaign::count();
            $tests['Courier'] = \App\Models\Logistics\CourierPartner::count();
            $tests['ShippingZone'] = \App\Models\Logistics\ShippingZone::count();
            $tests['ContentPage'] = \App\Models\Seo\ContentPage::count();
            $tests['AutomationTree'] = app(\App\Services\AutomationTreeService::class)->getTree()['children'][0]['name'] ?? 'none';
            $view = view('operations.index')->with([
                'campaigns' => collect(), 'couriers' => collect(),
                'zones' => collect(), 'pages' => collect(),
                'automationTree' => app(\App\Services\AutomationTreeService::class)->getTree(),
            ]);
            $tests['render'] = $view->renderSections()['content'][0] ?? 'partial';
        } elseif ($slug === 'room3d') {
            $ctrl = app(\App\Http\Controllers\Web\Room3dController::class);
            $tests['response'] = $ctrl->index()->renderSections()['content'][0] ?? 'partial';
        }
    } catch (\Throwable $e) {
        return '<pre>FAIL at: ' . $slug . "\nStep: " . array_key_last($tests ?? []) . "\n" .
               get_class($e) . ': ' . $e->getMessage() . "\n" .
               $e->getFile() . ':' . $e->getLine() . '</pre>';
    }
    return '<pre>' . implode("\n", array_map(fn($k, $v) => "OK: $k -> $v", array_keys($tests), $tests)) . '</pre>';
});

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
