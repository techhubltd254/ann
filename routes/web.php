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
use App\Http\Controllers\Web\WishlistController;
use App\Http\Controllers\Web\OrderTrackingController;
use App\Http\Controllers\Web\ProductQAController;
use App\Http\Controllers\Web\RecentlyViewedController;
use App\Http\Controllers\Web\AuctionController;
use App\Http\Controllers\Web\RfqController;
use App\Http\Controllers\Web\FlashSaleController;
use App\Http\Controllers\Web\GiftCardController;
use App\Http\Controllers\Web\SellerAnalyticsController;
use App\Http\Controllers\Web\LiveChatController;
use App\Http\Controllers\Web\LivestreamController;
use App\Http\Controllers\Web\Room3dController;

Route::get('/', HomeController::class)->name('home')
    ->middleware(\App\Http\Middleware\CachePublicResponse::class)
    ->withoutMiddleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ]);

Route::get('/counties', [CountyController::class, 'index'])->name('counties.index')
    ->middleware(\App\Http\Middleware\CachePublicResponse::class)
    ->withoutMiddleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ]);
Route::get('/counties/{county}', [CountyController::class, 'show'])->name('counties.show')
    ->middleware(\App\Http\Middleware\CachePublicResponse::class)
    ->withoutMiddleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ]);
Route::get('/counties/{county}/sector/{sector}', [CountyController::class, 'sector'])->name('counties.sector');
Route::get('/counties/{county}/products/{product}/book', [\App\Http\Controllers\Web\CountyProductBookingController::class, 'show'])->name('county.product.booking');
Route::post('/counties/{county}/products/{product}/book', [\App\Http\Controllers\Web\CountyProductBookingController::class, 'book'])->name('county.product.booking.store');
Route::get('/counties/{county}/products/{product}/book/success/{reference}', [\App\Http\Controllers\Web\CountyProductBookingController::class, 'success'])->name('county.product.booking.success');

// Marketplace
Route::get('/marketplace/compare', [MarketplaceController::class, 'compare'])->name('marketplace.compare');
Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/marketplace/{slug}', [MarketplaceController::class, 'show'])->name('marketplace.show');

// Ecommerce features
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle')->middleware('auth');
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index')->middleware('auth');
Route::get('/wishlist/count', [WishlistController::class, 'count'])->name('wishlist.count')->middleware('auth');

Route::get('/orders', [OrderTrackingController::class, 'myOrders'])->name('orders.index')->middleware('auth');
Route::get('/orders/{orderNumber}', [OrderTrackingController::class, 'show'])->name('orders.track')->middleware('auth');
Route::post('/orders/{orderNumber}/return', [OrderTrackingController::class, 'requestReturn'])->name('orders.return')->middleware('auth');

Route::post('/products/{product}/questions', [ProductQAController::class, 'ask'])->name('product-questions.ask')->middleware('auth');
Route::post('/questions/{id}/answer', [ProductQAController::class, 'answer'])->name('product-questions.answer')->middleware('auth');

Route::post('/recently-viewed/track', [RecentlyViewedController::class, 'track'])->name('recently-viewed.track')->middleware('throttle:30,1');
Route::get('/recently-viewed', [RecentlyViewedController::class, 'get'])->name('recently-viewed.get');

Route::get('/flash-sales', [FlashSaleController::class, 'index'])->name('flash-sales.index');

Route::get('/gift-cards', [GiftCardController::class, 'index'])->name('gift-cards.index');
Route::post('/gift-cards/purchase', [GiftCardController::class, 'purchase'])->name('gift-cards.purchase')->middleware('auth');
Route::post('/gift-cards/apply', [GiftCardController::class, 'apply'])->name('gift-cards.apply')->middleware('throttle:30,1');
Route::get('/gift-cards/remove', [GiftCardController::class, 'remove'])->name('gift-cards.remove');

Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
Route::get('/auctions/create', [AuctionController::class, 'create'])->name('auctions.create')->middleware('auth');
Route::post('/auctions', [AuctionController::class, 'store'])->name('auctions.store')->middleware('auth');
Route::get('/auctions/{id}', [AuctionController::class, 'show'])->name('auctions.show');
Route::post('/auctions/{id}/bid', [AuctionController::class, 'bid'])->name('auctions.bid')->middleware('auth');

Route::get('/rfq', [RfqController::class, 'index'])->name('rfq.index')->middleware('auth');
Route::get('/rfq/create', [RfqController::class, 'create'])->name('rfq.create')->middleware('auth');
Route::post('/rfq', [RfqController::class, 'store'])->name('rfq.store')->middleware('auth');
Route::get('/rfq/marketplace', [RfqController::class, 'sellerIndex'])->name('rfq.seller')->middleware('auth');
Route::post('/rfq/{rfqId}/quote', [RfqController::class, 'quote'])->name('rfq.quote')->middleware('auth');

Route::get('/seller/analytics', [SellerAnalyticsController::class, 'dashboard'])->name('seller.analytics')->middleware('auth');

Route::get('/live-chat/{vendorId}', [LiveChatController::class, 'widget'])->name('live-chat.widget')->middleware('auth');
Route::post('/live-chat/{vendorId}/send', [LiveChatController::class, 'send'])->name('live-chat.send')->middleware('auth');
Route::get('/live-chat/{vendorId}/poll', [LiveChatController::class, 'poll'])->name('live-chat.poll')->middleware('auth');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:30,1');
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/api/mpesa/callback', [CheckoutController::class, 'mpesaCallback'])->name('mpesa.callback');

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
    Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])->name('dashboard.admin')->middleware('admin:kicc');
    Route::post('/dashboard/admin/delete-product/{id}', [AdminDashboardController::class, 'deleteProduct'])->name('admin.delete-product')->middleware('admin:kicc');
    Route::post('/dashboard/admin/delete-user/{id}', [AdminDashboardController::class, 'deleteUser'])->name('admin.delete-user')->middleware('admin:kicc');
    Route::post('/dashboard/admin/delete-order/{id}', [AdminDashboardController::class, 'deleteOrder'])->name('admin.delete-order')->middleware('admin:kicc');

    // ═══ FOUR-TIER EXHIBITOR PORTALS ═══
    // KICC Overall Admin — control everything
    Route::get('/kicc-admin', [\App\Http\Controllers\Web\KiccAdminController::class, 'index'])->name('kicc.admin')->middleware('admin:kicc');
    Route::post('/kicc-admin/escrow/{id}/release', [\App\Http\Controllers\Web\KiccAdminController::class, 'releaseEscrow'])->name('kicc.admin.escrow.release')->middleware('admin:kicc');
    Route::post('/kicc-admin/artisan', [\App\Http\Controllers\Web\KiccAdminController::class, 'runCommand'])->name('kicc.admin.artisan')->middleware('admin:kicc');
    Route::post('/kicc-admin/hero/upload', [\App\Http\Controllers\Web\KiccAdminController::class, 'uploadHeroVideo'])->name('kicc.admin.hero.upload')->middleware('admin:kicc');
    Route::post('/kicc-admin/hero/delete', [\App\Http\Controllers\Web\KiccAdminController::class, 'deleteHeroVideo'])->name('kicc.admin.hero.delete')->middleware('admin:kicc');
    // National Government Exhibitor Portal
    Route::get('/national-admin', [\App\Http\Controllers\Web\NationalPortalController::class, 'index'])->name('national.admin')->middleware('admin:national');
Route::post('/national-admin/ministries', [\App\Http\Controllers\Web\NationalPortalController::class, 'storeMinistry'])->name('national.admin.ministry.store')->middleware('admin:national');
Route::post('/national-admin/ministries/{ministry}', [\App\Http\Controllers\Web\NationalPortalController::class, 'updateMinistry'])->name('national.admin.ministry.update')->middleware('admin:national');
Route::get('/national-admin/ministries/{ministry}/delete', [\App\Http\Controllers\Web\NationalPortalController::class, 'deleteMinistry'])->name('national.admin.ministry.delete')->middleware('admin:national');
Route::post('/national-admin/agencies', [\App\Http\Controllers\Web\NationalPortalController::class, 'storeAgency'])->name('national.admin.agency.store')->middleware('admin:national');
Route::post('/national-admin/agencies/{agency}', [\App\Http\Controllers\Web\NationalPortalController::class, 'updateAgency'])->name('national.admin.agency.update')->middleware('admin:national');
Route::get('/national-admin/agencies/{agency}/delete', [\App\Http\Controllers\Web\NationalPortalController::class, 'deleteAgency'])->name('national.admin.agency.delete')->middleware('admin:national');
    // County Exhibitor Portal (county = a website by itself)
    Route::get('/county-admin', [\App\Http\Controllers\Web\CountyPortalController::class, 'index'])->name('county.admin');
    Route::get('/county-admin/exhibitor', [\App\Http\Controllers\Web\CountyPortalController::class, 'exhibitor'])->name('county.admin.exhibitor');

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
    Route::get('/kicc-admin/coupons', [\App\Http\Controllers\Web\CouponController::class, 'adminIndex'])->name('coupon.admin.index')->middleware('admin:kicc');
    Route::post('/kicc-admin/coupons', [\App\Http\Controllers\Web\CouponController::class, 'adminStore'])->name('coupon.admin.store')->middleware('admin:kicc');
    Route::get('/kicc-admin/flash-sales', [FlashSaleController::class, 'admin'])->name('kicc-admin.flash-sales')->middleware('admin:kicc');
    Route::post('/kicc-admin/flash-sales', [FlashSaleController::class, 'store'])->name('kicc-admin.flash-sales.store')->middleware('admin:kicc');
    Route::post('/kicc-admin/flash-sales/{id}/products', [FlashSaleController::class, 'addProduct'])->name('kicc-admin.flash-sales.add-product')->middleware('admin:kicc');
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
Route::post('/ai/chat', [\App\Http\Controllers\Web\AIController::class, 'chat'])->name('ai.chat.api')->middleware('throttle:30,1');
Route::get('/ai/itinerary', [\App\Http\Controllers\Web\AIController::class, 'itineraryPage'])->name('ai.itinerary');
Route::post('/ai/itinerary', [\App\Http\Controllers\Web\AIController::class, 'itinerary'])->name('ai.itinerary.api');
Route::get('/api/recommendations', [\App\Http\Controllers\Web\AIController::class, 'recommendations'])->name('api.recommendations');
Route::get('/api/forecast', [\App\Http\Controllers\Web\AIController::class, 'forecast'])->name('api.forecast');
Route::post('/api/fraud-check', [\App\Http\Controllers\Web\AIController::class, 'fraudCheck'])->name('api.fraud.check');

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

// KICC Website (kicc.co.ke functionality)
Route::get('/national-government', [\App\Http\Controllers\Web\NationalGovernmentController::class, 'index'])->name('national-government.index');
Route::get('/kicc/news', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'newsIndex'])->name('kicc.news');
Route::get('/kicc/news/{slug}', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'newsShow'])->name('kicc.news.show');
Route::get('/kicc/about', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'about']);
Route::get('/kicc/mission', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'mission'])->name('kicc.mission');
Route::get('/kicc/board', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'board'])->name('kicc.board');
Route::get('/kicc/management', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'management'])->name('kicc.management');
Route::get('/kicc/history', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'history'])->name('kicc.history');
Route::get('/kicc/org-structure', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'orgStructure'])->name('kicc.org-structure');
Route::get('/kicc/event-booking', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'eventBookingForm'])->name('kicc.event-booking');
Route::post('/kicc/event-booking', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'eventBookingStore'])->name('kicc.event-booking.store');
Route::get('/kicc/event-booking/success/{reference}', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'eventBookingSuccess'])->name('kicc.event-booking.success');
Route::get('/kicc/jobs', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'jobs'])->name('kicc.jobs');
Route::get('/kicc/jobs/{job}', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'jobsShow'])->name('kicc.jobs.show');
Route::post('/kicc/jobs/{job}/apply', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'jobsApply'])->name('kicc.jobs.apply');
Route::post('/kicc/newsletter', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'newsletterSubscribe'])->name('kicc.newsletter');
Route::get('/kicc/pricing', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'pricing'])->name('kicc.pricing');
Route::get('/kicc/sustainability', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'sustainability'])->name('kicc.sustainability');
Route::get('/kicc/visitor-facilities', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'visitorFacilities'])->name('kicc.visitor-facilities');
Route::get('/kicc/transport', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'transport'])->name('kicc.transport');
Route::get('/kicc/places-to-stay', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'placesToStay'])->name('kicc.places-to-stay');
Route::get('/kicc/helipad', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'helipad'])->name('kicc.helipad');
Route::get('/kicc/virtual-tour', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'virtualTour'])->name('kicc.virtual-tour');
Route::get('/kicc/video-gallery', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'videoGallery'])->name('kicc.video-gallery');
Route::get('/kicc/policy-documents', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'policyDocuments'])->name('kicc.policy-documents');
Route::get('/kicc/opportunities', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'opportunities'])->name('kicc.opportunities');
Route::get('/kicc/faq', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'faq'])->name('kicc.faq');
Route::get('/kicc/annual-reports', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'annualReports'])->name('kicc.annual-reports');
Route::get('/kicc/publications', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'publications'])->name('kicc.publications');
Route::get('/kicc/service-charter', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'serviceCharter'])->name('kicc.service-charter');
Route::get('/kicc/about-us', function () { return redirect('/kicc/mission'); });
Route::get('/kicc/leadership', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'leadership'])->name('kicc.leadership');
Route::get('/faq', function () { return redirect('/kicc/faq'); });
Route::get('/contact', function () { return redirect('/kicc/event-booking'); });
Route::get('/kicc/our-departments', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'ourDepartments'])->name('kicc.our-departments');

// CMS Admin
Route::middleware(['auth', 'admin:kicc'])->prefix('kicc-admin/cms')->name('cms.admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\CmsController::class, 'adminIndex'])->name('index');
    Route::post('/pages/{page}', [\App\Http\Controllers\Web\CmsController::class, 'updatePage'])->name('page.update');
    Route::post('/pages', [\App\Http\Controllers\Web\CmsController::class, 'storePage'])->name('page.store');
    Route::post('/team', [\App\Http\Controllers\Web\CmsController::class, 'storeTeamMember'])->name('team.store');
    Route::post('/team/{member}', [\App\Http\Controllers\Web\CmsController::class, 'updateTeamMember'])->name('team.update');
    Route::get('/team/{member}/delete', [\App\Http\Controllers\Web\CmsController::class, 'deleteTeamMember'])->name('team.delete');
    Route::post('/timeline', [\App\Http\Controllers\Web\CmsController::class, 'storeTimelineEvent'])->name('timeline.store');
    Route::get('/timeline/{event}/delete', [\App\Http\Controllers\Web\CmsController::class, 'deleteTimelineEvent'])->name('timeline.delete');
    Route::post('/faq', [\App\Http\Controllers\Web\CmsController::class, 'storeFaq'])->name('faq.store');
    Route::post('/faq/{faq}', [\App\Http\Controllers\Web\CmsController::class, 'updateFaq'])->name('faq.update');
    Route::get('/faq/{faq}/delete', [\App\Http\Controllers\Web\CmsController::class, 'deleteFaq'])->name('faq.delete');
    Route::post('/video', [\App\Http\Controllers\Web\CmsController::class, 'storeVideo'])->name('video.store');
    Route::get('/video/{video}/delete', [\App\Http\Controllers\Web\CmsController::class, 'deleteVideo'])->name('video.delete');
});

// National Government Admin (like counties)
Route::middleware(['auth', 'admin:national'])->prefix('kicc-admin/national')->name('national.admin.v2.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\NationalAdminController::class, 'index'])->name('index');
    Route::post('/ministries', [\App\Http\Controllers\Web\NationalAdminController::class, 'storeMinistry'])->name('ministry.store');
    Route::post('/ministries/{ministry}', [\App\Http\Controllers\Web\NationalAdminController::class, 'updateMinistry'])->name('ministry.update');
    Route::get('/ministries/{ministry}/delete', [\App\Http\Controllers\Web\NationalAdminController::class, 'deleteMinistry'])->name('ministry.delete');
    Route::post('/agencies', [\App\Http\Controllers\Web\NationalAdminController::class, 'storeAgency'])->name('agency.store');
    Route::get('/agencies/{agency}/delete', [\App\Http\Controllers\Web\NationalAdminController::class, 'deleteAgency'])->name('agency.delete');
});

// KPIs & Monitoring
Route::get('/kpi', [\App\Http\Controllers\Web\KpiController::class, 'dashboard'])->name('kpi.dashboard');

// Multi-Currency
Route::get('/currency', [\App\Http\Controllers\Web\MultiCurrencyController::class, 'settings'])->name('currency.settings');
Route::post('/api/currency/convert', [\App\Http\Controllers\Web\MultiCurrencyController::class, 'convert'])->name('api.currency.convert');

// Training & Documentation
Route::get('/training', [\App\Http\Controllers\Web\TrainingDocController::class, 'index'])->name('training.index');
Route::get('/training/admin-manual', [\App\Http\Controllers\Web\TrainingDocController::class, 'admin'])->name('training.admin');
Route::get('/training/api-docs', [\App\Http\Controllers\Web\TrainingDocController::class, 'api'])->name('training.api');
Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/trade/enquiries', [\App\Http\Controllers\Web\TradeAdminController::class, 'enquiries'])->name('trade.admin.enquiries')->middleware('admin:kicc');
    Route::post('/kicc-admin/trade/enquiries/{enquiry}', [\App\Http\Controllers\Web\TradeAdminController::class, 'updateStatus'])->name('trade.admin.enquiry.status')->middleware('admin:kicc');
});

// Agent / Tour Operator Onboarding
Route::get('/agents/register', [\App\Http\Controllers\Web\AgentOnboardingController::class, 'register'])->name('agent.register');
Route::post('/agents/register', [\App\Http\Controllers\Web\AgentOnboardingController::class, 'store'])->name('agent.store');
Route::get('/agents/success/{agent}', [\App\Http\Controllers\Web\AgentOnboardingController::class, 'success'])->name('agent.onboarding.success');
Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/agents', [\App\Http\Controllers\Web\AgentAdminController::class, 'index'])->name('agent.admin.index')->middleware('admin:kicc');
    Route::get('/kicc-admin/agents/{agent}', [\App\Http\Controllers\Web\AgentAdminController::class, 'show'])->name('agent.admin.show')->middleware('admin:kicc');
    Route::post('/kicc-admin/agents/{agent}/approve', [\App\Http\Controllers\Web\AgentAdminController::class, 'approve'])->name('agent.admin.approve')->middleware('admin:kicc');
    Route::post('/kicc-admin/agents/{agent}/reject', [\App\Http\Controllers\Web\AgentAdminController::class, 'reject'])->name('agent.admin.reject')->middleware('admin:kicc');
    Route::post('/kicc-admin/agents/documents/{document}/verify', [\App\Http\Controllers\Web\AgentAdminController::class, 'verifyDocument'])->name('agent.admin.document.verify')->middleware('admin:kicc');
});

// Reviews & Ratings
Route::post('/reviews', [\App\Http\Controllers\Web\ReviewController::class, 'store'])->name('review.store');
Route::post('/reviews/{review}/respond', [\App\Http\Controllers\Web\ReviewController::class, 'updateVendorResponse'])->name('review.respond');
Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/reviews', [\App\Http\Controllers\Web\ReviewAdminController::class, 'index'])->name('review.admin.index')->middleware('admin:kicc');
    Route::post('/kicc-admin/reviews/{review}/approve', [\App\Http\Controllers\Web\ReviewAdminController::class, 'approve'])->name('review.admin.approve')->middleware('admin:kicc');
    Route::post('/kicc-admin/reviews/{review}/reject', [\App\Http\Controllers\Web\ReviewAdminController::class, 'reject'])->name('review.admin.reject')->middleware('admin:kicc');
});

// Commission & Licensing Admin
Route::middleware(['auth', 'admin:kicc'])->group(function () {
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

// Livestreams
Route::get('/livestreams', [LivestreamController::class, 'index'])->name('livestreams.index');
Route::get('/livestreams/{slug}', [LivestreamController::class, 'show'])->name('livestreams.show');

// [ADMIN] One-time image optimization trigger
Route::post('/__admin/optimize-images', function () {
    if (!app()->environment('local', 'production')) abort(404);
    $artisan = new \App\Console\Commands\OptimizeImages();
    $artisan->setLaravel(app());
    return $artisan->handle(app(\App\Services\ImageOptimizer::class));
})->middleware('auth');

// SEO & metadata
Route::get('/robots.txt', fn() => response()->file(public_path('robots.txt'), ['Content-Type' => 'text/plain']));
Route::get('/llms.txt', fn() => response()->file(public_path('llms.txt'), ['Content-Type' => 'text/plain']));
Route::get('/sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'priority' => '1.0'],
        ['loc' => url('/counties'), 'priority' => '0.9'],
        ['loc' => url('/marketplace'), 'priority' => '0.8'],
        ['loc' => url('/trade-agreements'), 'priority' => '0.7'],
        ['loc' => url('/trading-blocs'), 'priority' => '0.7'],
        ['loc' => url('/export/eligibility'), 'priority' => '0.6'],
    ];
    $counties = \App\Models\County::where('is_active', true)->get();
    foreach ($counties as $c) {
        $urls[] = ['loc' => route('counties.show', $c->slug), 'priority' => '0.8'];
    }
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= "  <url>\n    <loc>{$u['loc']}</loc>\n    <priority>{$u['priority']}</priority>\n  </url>\n";
    }
    $xml .= '</urlset>';
    return response($xml, 200, ['Content-Type' => 'application/xml']);
});
Route::get('/favicon.ico', fn() => response()->file(public_path('favicon.ico'), ['Content-Type' => 'image/x-icon']));

