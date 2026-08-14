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

Route::get('/counties', [CountyController::class, 'index'])->name('counties.index');
Route::get('/counties/{county}', [CountyController::class, 'show'])->name('counties.show');
Route::get('/counties/{county}/sector/{sector}', [CountyController::class, 'sector'])->name('counties.sector');
Route::get('/counties/{county}/products/{product}/book', [\App\Http\Controllers\Web\CountyProductBookingController::class, 'show'])->name('county.product.booking');
Route::post('/counties/{county}/products/{product}/book', [\App\Http\Controllers\Web\CountyProductBookingController::class, 'book'])->name('county.product.booking.store');
Route::get('/counties/{county}/products/{product}/book/success/{reference}', [\App\Http\Controllers\Web\CountyProductBookingController::class, 'success'])->name('county.product.booking.success');

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

// Packages (blueprint subscription catalogue)
Route::get('/packages', [\App\Http\Controllers\Web\PackagesController::class, 'index'])->name('packages.index');

// 3-Tier Admin Portals
Route::middleware('auth')->group(function () {
    // Portal selector (choose KICC/National/County/Exhibitor admin)
    Route::get('/portal', [AdminPortalController::class, 'selector'])->name('admin.portal');
    // National Government Admin — ministries & agencies
    Route::get('/admin/national', [AdminPortalController::class, 'national'])->name('admin.national');
    // County Admin — scoped to own county
    Route::get('/admin/county', [AdminPortalController::class, 'county'])->name('admin.county');
    // Dashboards (legacy)
    Route::get('/dashboard/county', [DashboardV2Controller::class, 'county'])->name('dashboard.county');
    Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])->name('dashboard.admin');
    Route::post('/dashboard/admin/delete-product/{id}', [AdminDashboardController::class, 'deleteProduct'])->name('admin.delete-product');
    Route::post('/dashboard/admin/delete-user/{id}', [AdminDashboardController::class, 'deleteUser'])->name('admin.delete-user');
    Route::post('/dashboard/admin/delete-order/{id}', [AdminDashboardController::class, 'deleteOrder'])->name('admin.delete-order');

    // ═══ FOUR-TIER EXHIBITOR PORTALS ═══
    // KICC Overall Admin — control everything
    Route::get('/kicc-admin', [\App\Http\Controllers\Web\KiccAdminController::class, 'index'])->name('kicc.admin');
    Route::post('/kicc-admin/escrow/{id}/release', [\App\Http\Controllers\Web\KiccAdminController::class, 'releaseEscrow'])->name('kicc.admin.escrow.release');
    // National Government Exhibitor Portal
    Route::get('/national-admin', [\App\Http\Controllers\Web\NationalPortalController::class, 'index'])->name('national.admin');
    // County Exhibitor Portal (county = a website by itself)
    Route::get('/county-admin', [\App\Http\Controllers\Web\CountyPortalController::class, 'index'])->name('county.admin');

    // Professional County Admin (full content/image/price/ad/package control)
    Route::get('/county-admin/{slug}/pro', [\App\Http\Controllers\Web\CountyAdminController::class, 'dashboard'])->name('county.admin.pro');
    Route::post('/county-admin/{slug}/pro/content', [\App\Http\Controllers\Web\CountyAdminController::class, 'updateContent'])->name('county.admin.content');
    Route::post('/county-admin/{slug}/pro/image', [\App\Http\Controllers\Web\CountyAdminController::class, 'uploadImage'])->name('county.admin.image.upload');
    Route::post('/county-admin/{slug}/pro/image/{sector}/delete', [\App\Http\Controllers\Web\CountyAdminController::class, 'deleteImage'])->name('county.admin.image.delete');
    Route::post('/county-admin/{slug}/pro/4d-video', [\App\Http\Controllers\Web\CountyAdminController::class, 'upload4dVideo'])->name('county.admin.4d.upload');
    Route::post('/county-admin/{slug}/pro/4d-video/{entityType}/{entityId}/delete', [\App\Http\Controllers\Web\CountyAdminController::class, 'delete4dVideo'])->name('county.admin.4d.delete');
    Route::post('/county-admin/{slug}/pro/price', [\App\Http\Controllers\Web\CountyAdminController::class, 'updatePrice'])->name('county.admin.price');
    Route::post('/county-admin/{slug}/pro/ads', [\App\Http\Controllers\Web\CountyAdminController::class, 'createAd'])->name('county.admin.ads');
    Route::post('/county-admin/{slug}/pro/package', [\App\Http\Controllers\Web\CountyAdminController::class, 'purchasePackage'])->name('county.admin.package');
    Route::get('/county-admin/{slug}/pro/report/{type}', [\App\Http\Controllers\Web\CountyAdminController::class, 'downloadReport'])->name('county.admin.report');
    Route::post('/county-admin/{slug}/pro/details', [\App\Http\Controllers\Web\CountyAdminController::class, 'updateDetails'])->name('county.admin.details');
    Route::post('/county-admin/{slug}/pro/sector', [\App\Http\Controllers\Web\CountyAdminController::class, 'toggleSector'])->name('county.admin.sector');
    Route::post('/county-admin/{slug}/pro/sector/tile', [\App\Http\Controllers\Web\CountyAdminController::class, 'toggleTileSector'])->name('county.admin.sector.tile');
    Route::post('/county-admin/{slug}/pro/entity', [\App\Http\Controllers\Web\CountyAdminController::class, 'addEntity'])->name('county.admin.entity');
    Route::post('/county-admin/{slug}/pro/entity/{entityId}/delete', [\App\Http\Controllers\Web\CountyAdminController::class, 'deleteEntity'])->name('county.admin.entity.delete');
    // Private Exhibitor Portal
    Route::get('/exhibitor-admin', [\App\Http\Controllers\Web\ExhibitorPortalController::class, 'index'])->name('exhibitor.admin');
    Route::post('/exhibitor-admin/products', [\App\Http\Controllers\Web\ExhibitorPortalController::class, 'storeProduct'])->name('exhibitor.admin.products.store');
    Route::post('/exhibitor-admin/products/{id}/delete', [\App\Http\Controllers\Web\ExhibitorPortalController::class, 'deleteProduct'])->name('exhibitor.admin.products.delete');
    Route::get('/exhibitor-onboarding', [\App\Http\Controllers\Web\ExhibitorOnboardingController::class, 'show'])->name('exhibitor.onboarding');
    Route::post('/exhibitor-onboarding', [\App\Http\Controllers\Web\ExhibitorOnboardingController::class, 'store'])->name('exhibitor.onboarding.store');

    // Travel Provider Portal (airlines, hotels, cab companies)
    Route::get('/provider-admin', [\App\Http\Controllers\Web\ProviderPortalController::class, 'index'])->name('provider.admin');
    Route::post('/provider-admin/price', [\App\Http\Controllers\Web\ProviderPortalController::class, 'updatePrice'])->name('provider.admin.price');
    Route::post('/provider-admin/add', [\App\Http\Controllers\Web\ProviderPortalController::class, 'addService'])->name('provider.admin.add');

    // KICC approvals
    Route::post('/kicc-admin/approve/{table}/{id}', [\App\Http\Controllers\Web\KiccAdminController::class, 'approveService'])->name('kicc.admin.approve');

    // ─── Media Pipeline (admin-controlled; nothing hardcoded) ───
    Route::prefix('media')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\MediaLibraryController::class, 'index'])->name('media.library');
        Route::get('/upload', [\App\Http\Controllers\Web\MediaLibraryController::class, 'upload'])->name('media.upload');
        Route::post('/upload', [\App\Http\Controllers\Web\MediaLibraryController::class, 'store'])->name('media.store');
        Route::get('/{asset}', [\App\Http\Controllers\Web\MediaLibraryController::class, 'show'])->name('media.show');
        Route::delete('/{asset}', [\App\Http\Controllers\Web\MediaLibraryController::class, 'destroy'])->name('media.destroy');

        Route::post('/{asset}/pipeline', [\App\Http\Controllers\Web\MediaLibraryController::class, 'dispatchPipeline'])->name('media.pipeline.dispatch');
        Route::get('/jobs/{job}/status', [\App\Http\Controllers\Web\MediaLibraryController::class, 'jobStatus'])->name('media.job.status');
        Route::post('/jobs/{job}/cancel', [\App\Http\Controllers\Web\MediaLibraryController::class, 'cancelJob'])->name('media.job.cancel');
        Route::post('/{asset}/attach', [\App\Http\Controllers\Web\MediaLibraryController::class, 'attach'])->name('media.attach');
        Route::post('/{asset}/detach', [\App\Http\Controllers\Web\MediaLibraryController::class, 'detach'])->name('media.detach');
    });
});

// Public exhibitor websites (independent, interconnected)
Route::get('/exhibitor/{slug}', [\App\Http\Controllers\Web\ExhibitorSiteController::class, 'show'])->name('exhibitor.site');
Route::get('/national/{slug}', [\App\Http\Controllers\Web\NationalSiteController::class, 'show'])->name('national.site');

// Attraction booking (every tourist attraction is bookable)
Route::get('/attractions/{id}', [\App\Http\Controllers\Web\AttractionBookingController::class, 'show'])->name('attractions.show');
Route::post('/attractions/{id}/book', [\App\Http\Controllers\Web\AttractionBookingController::class, 'book'])->name('attractions.book');

// Travel & Tourism
Route::get('/travel', [TravelController::class, 'index'])->name('travel.index');
Route::get('/travel/flights', [TravelController::class, 'flights'])->name('travel.flights');
Route::post('/travel/book', [TravelController::class, 'book'])->name('travel.book');
Route::get('/travel/receipt/{groupRef}', [TravelController::class, 'receipt'])->name('travel.receipt');

// Platform operations (Advertising, SEO, Logistics)
Route::get('/operations', [OperationsController::class, 'index'])->name('operations.index');

Route::get('/exhibitions', [ExhibitionController::class, 'index'])->name('exhibitions.index');
Route::get('/exhibitions/{slug}', [ExhibitionController::class, 'show'])->name('exhibitions.show');

// Trade Agreements & Trading Blocs
Route::get('/trade-agreements', [\App\Http\Controllers\Web\TradeAgreementController::class, 'index'])->name('trade.agreements.index');
Route::get('/trade-agreements/{slug}', [\App\Http\Controllers\Web\TradeAgreementController::class, 'show'])->name('trade.agreements.show');
Route::get('/trading-blocs', [\App\Http\Controllers\Web\TradeAgreementController::class, 'blocs'])->name('trade.blocs.index');
Route::get('/trading-blocs/{slug}', [\App\Http\Controllers\Web\TradeAgreementController::class, 'blocShow'])->name('trade.blocs.show');

// Trade Export functionality (real functional flow)
Route::get('/export/eligibility', [\App\Http\Controllers\Web\TradeExportController::class, 'eligibility'])->name('trade.eligibility');
Route::post('/export/eligibility', [\App\Http\Controllers\Web\TradeExportController::class, 'checkEligibility'])->name('trade.eligibility.check');
Route::get('/export/apply/{slug}', [\App\Http\Controllers\Web\TradeExportController::class, 'applyForm'])->name('trade.export.apply');
Route::post('/export/enquiry', [\App\Http\Controllers\Web\TradeExportController::class, 'storeEnquiry'])->name('trade.enquiry.store');
Route::get('/export/success/{reference}', [\App\Http\Controllers\Web\TradeExportController::class, 'enquirySuccess'])->name('trade.enquiry.success');

// Search
Route::get('/search', [\App\Http\Controllers\Web\SearchController::class, 'index'])->name('search.index');

// Messaging
Route::middleware('auth')->group(function () {
    Route::get('/messages', [\App\Http\Controllers\Web\MessagingController::class, 'inbox'])->name('messaging.inbox');
    Route::get('/messages/{conversation}', [\App\Http\Controllers\Web\MessagingController::class, 'show'])->name('messaging.show');
    Route::post('/messages/start', [\App\Http\Controllers\Web\MessagingController::class, 'start'])->name('messaging.start');
    Route::post('/messages/{conversation}/send', [\App\Http\Controllers\Web\MessagingController::class, 'send'])->name('messaging.send');
    Route::get('/api/messages/unread', [\App\Http\Controllers\Web\MessagingController::class, 'unreadCount'])->name('api.messages.unread');
});

// Notifications
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\Web\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Web\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Web\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/api/notifications/unread', [\App\Http\Controllers\Web\NotificationController::class, 'unreadCount'])->name('api.notifications.unread');
});

// Coupons
Route::post('/cart/coupon', [\App\Http\Controllers\Web\CouponController::class, 'apply'])->name('coupon.apply');
Route::post('/cart/coupon/remove', [\App\Http\Controllers\Web\CouponController::class, 'remove'])->name('coupon.remove');
Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/coupons', [\App\Http\Controllers\Web\CouponController::class, 'adminIndex'])->name('coupon.admin.index');
    Route::post('/kicc-admin/coupons', [\App\Http\Controllers\Web\CouponController::class, 'adminStore'])->name('coupon.admin.store');
});

// Tourism entities (guides, rentals, restaurants, event organizers)
Route::get('/tourism/guides', [\App\Http\Controllers\Web\TourismEntityController::class, 'guides'])->name('tourism.guides');
Route::get('/tourism/car-rentals', [\App\Http\Controllers\Web\TourismEntityController::class, 'rentals'])->name('tourism.rentals');
Route::get('/tourism/restaurants', [\App\Http\Controllers\Web\TourismEntityController::class, 'restaurants'])->name('tourism.restaurants');
Route::get('/tourism/event-organizers', [\App\Http\Controllers\Web\TourismEntityController::class, 'organizers'])->name('tourism.organizers');

// Tourism Intelligence Dashboard
Route::get('/intelligence', [\App\Http\Controllers\Web\IntelligenceController::class, 'dashboard'])->name('intelligence.dashboard');

// AI Features
Route::get('/ai/chat', [\App\Http\Controllers\Web\AIController::class, 'chatPage'])->name('ai.chat');
Route::post('/ai/chat', [\App\Http\Controllers\Web\AIController::class, 'chat'])->name('ai.chat.api');
Route::get('/ai/itinerary', [\App\Http\Controllers\Web\AIController::class, 'itineraryPage'])->name('ai.itinerary');
Route::post('/ai/itinerary', [\App\Http\Controllers\Web\AIController::class, 'itinerary'])->name('ai.itinerary.api');
Route::get('/api/recommendations', [\App\Http\Controllers\Web\AIController::class, 'recommendations'])->name('api.recommendations');
Route::get('/api/forecast', [\App\Http\Controllers\Web\AIController::class, 'forecast'])->name('api.forecast');
Route::post('/api/fraud-check', [\App\Http\Controllers\Web\AIController::class, 'fraudCheck'])->name('api.fraud.check');
Route::post('/api/image-search', [\App\Http\Controllers\Web\AIController::class, 'imageSearch'])->name('api.image.search');

// Integrations
Route::get('/integrations', [\App\Http\Controllers\Web\IntegrationController::class, 'settings'])->name('integrations.settings');
Route::get('/counties/{county}/map', [\App\Http\Controllers\Web\IntegrationController::class, 'map'])->name('counties.map');
Route::get('/counties/{county}/weather', [\App\Http\Controllers\Web\IntegrationController::class, 'weather'])->name('counties.weather');

// LMS / Capacity Building
Route::get('/lms', [\App\Http\Controllers\Web\CourseController::class, 'index'])->name('lms.index');
Route::get('/lms/{course}', [\App\Http\Controllers\Web\CourseController::class, 'show'])->name('lms.show');
Route::post('/lms/{course}/enroll', [\App\Http\Controllers\Web\CourseController::class, 'enroll'])->name('lms.enroll');
Route::middleware('auth')->group(function () {
    Route::get('/my-courses', [\App\Http\Controllers\Web\CourseController::class, 'myCourses'])->name('lms.my-courses');
});

// Safety & Security
Route::get('/safety/alerts', [\App\Http\Controllers\Web\SafetyController::class, 'alerts'])->name('safety.alerts');
Route::get('/safety/report', [\App\Http\Controllers\Web\SafetyController::class, 'reportForm'])->name('safety.report');
Route::post('/safety/report', [\App\Http\Controllers\Web\SafetyController::class, 'submitReport'])->name('safety.report.submit');
Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/trade/enquiries', [\App\Http\Controllers\Web\TradeAdminController::class, 'enquiries'])->name('trade.admin.enquiries');
    Route::post('/kicc-admin/trade/enquiries/{enquiry}', [\App\Http\Controllers\Web\TradeAdminController::class, 'updateStatus'])->name('trade.admin.enquiry.status');
});

// Agent / Tour Operator Onboarding
Route::get('/agents/register', [\App\Http\Controllers\Web\AgentOnboardingController::class, 'register'])->name('agent.register');
Route::post('/agents/register', [\App\Http\Controllers\Web\AgentOnboardingController::class, 'store'])->name('agent.store');
Route::get('/agents/success/{agent}', [\App\Http\Controllers\Web\AgentOnboardingController::class, 'success'])->name('agent.onboarding.success');
Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/agents', [\App\Http\Controllers\Web\AgentAdminController::class, 'index'])->name('agent.admin.index');
    Route::get('/kicc-admin/agents/{agent}', [\App\Http\Controllers\Web\AgentAdminController::class, 'show'])->name('agent.admin.show');
    Route::post('/kicc-admin/agents/{agent}/approve', [\App\Http\Controllers\Web\AgentAdminController::class, 'approve'])->name('agent.admin.approve');
    Route::post('/kicc-admin/agents/{agent}/reject', [\App\Http\Controllers\Web\AgentAdminController::class, 'reject'])->name('agent.admin.reject');
    Route::post('/kicc-admin/agents/documents/{document}/verify', [\App\Http\Controllers\Web\AgentAdminController::class, 'verifyDocument'])->name('agent.admin.document.verify');
});

// Reviews & Ratings
Route::post('/reviews', [\App\Http\Controllers\Web\ReviewController::class, 'store'])->name('review.store');
Route::post('/reviews/{review}/respond', [\App\Http\Controllers\Web\ReviewController::class, 'updateVendorResponse'])->name('review.respond');
Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/reviews', [\App\Http\Controllers\Web\ReviewAdminController::class, 'index'])->name('review.admin.index');
    Route::post('/kicc-admin/reviews/{review}/approve', [\App\Http\Controllers\Web\ReviewAdminController::class, 'approve'])->name('review.admin.approve');
    Route::post('/kicc-admin/reviews/{review}/reject', [\App\Http\Controllers\Web\ReviewAdminController::class, 'reject'])->name('review.admin.reject');
});

// Commission & Licensing Admin
Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/commissions', function () {
        $commissions = \App\Models\CommissionLog::with('agent', 'order')->latest()->paginate(25);
        $totalPending = \App\Models\CommissionLog::where('status', 'pending')->sum('commission_amount');
        $totalSettled = \App\Models\CommissionLog::where('status', 'settled')->sum('commission_amount');
        return view('commissions.admin-index', compact('commissions', 'totalPending', 'totalSettled'));
    })->name('commission.admin.index');
});

// MFA (Multi-Factor Authentication)
Route::middleware('auth')->group(function () {
    Route::get('/mfa/setup', [\App\Http\Controllers\Web\MfaController::class, 'showSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [\App\Http\Controllers\Web\MfaController::class, 'confirmSetup'])->name('mfa.setup.confirm');
    Route::post('/mfa/disable', [\App\Http\Controllers\Web\MfaController::class, 'disable'])->name('mfa.disable');
});
Route::get('/mfa/challenge', [\App\Http\Controllers\Web\MfaController::class, 'showChallenge'])->name('mfa.challenge');
Route::post('/mfa/challenge', [\App\Http\Controllers\Web\MfaController::class, 'verifyChallenge'])->name('mfa.challenge.verify');

Route::get('/venues', [ExhibitionController::class, 'venues'])->name('venues.index');
Route::get('/venues/{venue}', [VenueController::class, 'show'])->name('venues.show');
Route::post('/venues/{venue}/inquire', [VenueController::class, 'inquire'])->name('venues.inquire');

// Exhibition screen videos
Route::get('/screens', [ScreenController::class, 'directory'])->name('screens.directory');
Route::get('/screens/{id}', [ScreenController::class, 'show'])->name('screens.show');
Route::post('/screens/{id}/advertise', [ScreenController::class, 'advertise'])->name('screens.advertise');

// 3D Exhibition experiences (standalone views)
Route::view('/exhibition-3d/map', 'exhibition-3d.map')->name('exhibition-3d.map');
Route::view('/exhibition-3d/sector', 'exhibition-3d.sector')->name('exhibition-3d.sector');
Route::view('/exhibition-3d/booth', 'exhibition-3d.booth')->name('exhibition-3d.booth');

// 3D Room Explorer
Route::get('/room3d', [Room3dController::class, 'index'])->name('room3d.index');
Route::get('/room3d/create', [Room3dController::class, 'create'])->name('room3d.create');
Route::post('/room3d', [Room3dController::class, 'store'])->name('room3d.store');
Route::get('/room3d/{id}', [Room3dController::class, 'show'])->name('room3d.show');
Route::get('/room3d/{id}/viewer', [Room3dController::class, 'viewer'])->name('room3d.viewer');
Route::get('/room3d/{id}/api', [Room3dController::class, 'api'])->name('room3d.api');

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

// [ADMIN] One-time image optimization trigger
Route::post('/__admin/optimize-images', function () {
    if (!app()->environment('local', 'production')) abort(404);
    $artisan = new \App\Console\Commands\OptimizeImages();
    $artisan->setLaravel(app());
    return $artisan->handle(app(\App\Services\ImageOptimizer::class));
})->middleware('auth');

