<?php

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
require __DIR__.'/auth.php';
use App\Http\Controllers\Web\TravelController;
use App\Http\Controllers\Web\VenueController;
use Illuminate\Support\Facades\Route;
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

// ── Public page cache middleware (applied to public GET routes) ──
$publicCache = \App\Http\Middleware\CachePublicResponse::class;
$noSession = [
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
];


Route::get('/', HomeController::class)->name('home')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);

// Diagnostic — shows which DB the app is connected to (TiDB vs injected MySQL).
// Not cached; always returns live status. Useful on the platform deploy URL.
Route::get('/db-check', [\App\Http\Controllers\Web\DbCheckController::class, 'index'])->name('db-check');

Route::get('/counties', [CountyController::class, 'index'])->name('counties.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/counties/{county}', [CountyController::class, 'show'])->name('counties.show')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/counties/{county}/sector/{sector}', [CountyController::class, 'sector'])->name('counties.sector')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/national-sector', [\App\Http\Controllers\Web\NationalSectorController::class, 'index'])
    ->name('national.sectors')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/national-sector/{slug}', [\App\Http\Controllers\Web\NationalSectorController::class, 'show'])
    ->name('national.sector.show')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/institutions/{institution}', [CountyController::class, 'institution'])->name('counties.institution')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/counties/{county}/products/{product}/book', [\App\Http\Controllers\Web\CountyProductBookingController::class, 'show'])->name('county.product.booking')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::post('/counties/{county}/products/{product}/book', [\App\Http\Controllers\Web\CountyProductBookingController::class, 'book'])->name('county.product.booking.store')->middleware('auth');
Route::get('/counties/{county}/products/{product}/book/success/{reference}', [\App\Http\Controllers\Web\CountyProductBookingController::class, 'success'])->name('county.product.booking.success')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);

// Marketplace
Route::get('/marketplace/compare', [MarketplaceController::class, 'compare'])->name('marketplace.compare')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/marketplace/{slug}', [MarketplaceController::class, 'show'])->name('marketplace.show')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);

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

// ── Experience Cart (integrated booking + transport) ──
Route::post('/experience/create', [\App\Http\Controllers\Web\ExperienceController::class, 'create'])->name('experience.create')->middleware('auth');
Route::post('/experience/{booking}/transport', [\App\Http\Controllers\Web\ExperienceController::class, 'setTransport'])->name('experience.transport')->middleware('auth');
Route::post('/experience/{booking}/addon', [\App\Http\Controllers\Web\ExperienceController::class, 'addAddon'])->name('experience.addon')->middleware('auth');
Route::post('/experience/{booking}/confirm', [\App\Http\Controllers\Web\ExperienceController::class, 'confirm'])->name('experience.confirm')->middleware('auth');
Route::post('/experience/{booking}/cancel', [\App\Http\Controllers\Web\ExperienceController::class, 'cancel'])->name('experience.cancel')->middleware('auth');
Route::post('/experience/{booking}/remove', [\App\Http\Controllers\Web\ExperienceController::class, 'removeFromCart'])->name('experience.remove')->middleware('auth');
Route::get('/experience/counties', [\App\Http\Controllers\Web\ExperienceController::class, 'counties'])->name('experience.counties');
Route::get('/experience/transport-options', [\App\Http\Controllers\Web\ExperienceController::class, 'transportOptions'])->name('experience.transport-options');

Route::get('/flash-sales', [FlashSaleController::class, 'index'])->name('flash-sales.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);

Route::get('/gift-cards', [GiftCardController::class, 'index'])->name('gift-cards.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::post('/gift-cards/purchase', [GiftCardController::class, 'purchase'])->name('gift-cards.purchase')->middleware('auth');
Route::post('/gift-cards/apply', [GiftCardController::class, 'apply'])->name('gift-cards.apply')->middleware(['auth', 'throttle:30,1']);
Route::post('/gift-cards/remove', [GiftCardController::class, 'remove'])->name('gift-cards.remove')->middleware('auth');

Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/auctions/create', [AuctionController::class, 'create'])->name('auctions.create')->middleware('auth');
Route::post('/auctions', [AuctionController::class, 'store'])->name('auctions.store')->middleware('auth');
Route::get('/auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::post('/auctions/{auction}/bid', [AuctionController::class, 'bid'])->name('auctions.bid')->middleware('auth');

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
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add')->middleware('auth');
Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update')->middleware('auth');
Route::delete('/cart/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:30,1');
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');
Route::post('/api/mpesa/callback', [CheckoutController::class, 'mpesaCallback'])->name('mpesa.callback');

// County subscriptions
Route::get('/county/{slug}/subscriptions', [CountySubscriptionController::class, 'index'])->name('county.subscriptions')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/subscriptions', [CountySubscriptionController::class, 'index'])->name('subscriptions.index');

// Packages (blueprint subscription catalogue)
Route::get('/packages', [\App\Http\Controllers\Web\PackagesController::class, 'index'])->name('packages.index');

// 3-Tier Admin Portals
// ── R2 Direct Upload (bypasses Cloudflare 100MB Worker limit) ──
Route::post('/api/r2/presigned-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'confirmR2Upload'])->middleware('auth');

Route::middleware('auth')->group(function () {
    // Portal selector (choose KICC/National/County/Exhibitor admin)
    Route::get('/portal', [AdminPortalController::class, 'selector'])->name('admin.portal');
    // National Government Admin — ministries & agencies
    Route::get('/admin/national', fn() => redirect()->route('national.admin.v2.dashboard'))->name('admin.national');
    // County Admin — scoped to own county
    Route::get('/admin/county', [AdminPortalController::class, 'county'])->name('admin.county');
    // Dashboards (legacy)
    Route::get('/dashboard/county', [DashboardV2Controller::class, 'county'])->name('dashboard.county');
    Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])->name('dashboard.admin')->middleware('admin:kicc');
    Route::post('/dashboard/admin/delete-product/{id}', [AdminDashboardController::class, 'deleteProduct'])->name('admin.delete-product')->middleware('admin:kicc');
    Route::post('/dashboard/admin/delete-user/{id}', [AdminDashboardController::class, 'deleteUser'])->name('admin.delete-user')->middleware('admin:kicc');
    Route::post('/dashboard/admin/delete-order/{id}', [AdminDashboardController::class, 'deleteOrder'])->name('admin.delete-order')->middleware('admin:kicc');
    Route::post('/dashboard/admin/restore-product/{id}', [AdminDashboardController::class, 'restoreProduct'])->name('admin.restore-product')->middleware('admin:kicc');
    Route::post('/dashboard/admin/restore-user/{id}', [AdminDashboardController::class, 'restoreUser'])->name('admin.restore-user')->middleware('admin:kicc');

    // ═══ FOUR-TIER EXHIBITOR PORTALS ═══
    // KICC Overall Admin — control everything
    Route::get('/kicc-admin', [\App\Http\Controllers\Web\KiccAdminController::class, 'index'])->name('kicc.admin')->middleware('admin:kicc');
    Route::post('/kicc-admin/escrow/{id}/release', [\App\Http\Controllers\Web\KiccAdminController::class, 'releaseEscrow'])->name('kicc.admin.escrow.release')->middleware('admin:kicc');
    Route::post('/kicc-admin/artisan', [\App\Http\Controllers\Web\KiccAdminController::class, 'runCommand'])->name('kicc.admin.artisan')->middleware('admin:kicc');
    Route::post('/kicc-admin/hero/upload', [\App\Http\Controllers\Web\KiccAdminController::class, 'uploadHeroVideo'])->name('kicc.admin.hero.upload')->middleware('admin:kicc');
    Route::post('/kicc-admin/hero/delete', [\App\Http\Controllers\Web\KiccAdminController::class, 'deleteHeroVideo'])->name('kicc.admin.hero.delete')->middleware('admin:kicc');
    Route::post('/kicc-admin/county/{slug}/hero', [\App\Http\Controllers\Web\KiccAdminController::class, 'uploadCountyHero'])->name('kicc.admin.county.hero')->middleware('admin:kicc');
    Route::post('/kicc-admin/plans/{id}', [\App\Http\Controllers\Web\KiccAdminController::class, 'updatePlan'])->name('kicc.admin.plan.update')->middleware('admin:kicc');
    // National Government Exhibitor Portal — redirects to KICC national admin
    Route::get('/national-admin', fn() => redirect()->route('national.admin.v2.dashboard'))->name('national.admin')->middleware('admin:national');
Route::post('/national-admin/ministries', [\App\Http\Controllers\Web\NationalPortalController::class, 'storeMinistry'])->name('national.admin.ministry.store')->middleware('admin:national');
Route::post('/national-admin/ministries/{ministry}', [\App\Http\Controllers\Web\NationalPortalController::class, 'updateMinistry'])->name('national.admin.ministry.update')->middleware('admin:national');
Route::get('/national-admin/ministries/{ministry}/delete', [\App\Http\Controllers\Web\NationalPortalController::class, 'deleteMinistry'])->name('national.admin.ministry.delete')->middleware('admin:national');
Route::post('/national-admin/agencies', [\App\Http\Controllers\Web\NationalPortalController::class, 'storeAgency'])->name('national.admin.agency.store')->middleware('admin:national');
Route::post('/national-admin/agencies/{agency}', [\App\Http\Controllers\Web\NationalPortalController::class, 'updateAgency'])->name('national.admin.agency.update')->middleware('admin:national');
Route::get('/national-admin/agencies/{agency}/delete', [\App\Http\Controllers\Web\NationalPortalController::class, 'deleteAgency'])->name('national.admin.agency.delete')->middleware('admin:national');
    // County Exhibitor Portal (county = a website by itself)
    Route::middleware('county.scope')->group(function () {
    Route::get('/county-admin', [\App\Http\Controllers\Web\CountyPortalController::class, 'index'])->name('county.admin');
    Route::get('/county-admin/exhibitor', [\App\Http\Controllers\Web\CountyPortalController::class, 'exhibitor'])->name('county.admin.exhibitor');

    // Professional County Admin (full content/image/price/ad/package control)
    Route::get('/county-admin/{slug}/pro', [\App\Http\Controllers\Web\CountyAdminController::class, 'dashboard'])->name('county.admin.pro');
    Route::post('/county-admin/{slug}/pro/content', [\App\Http\Controllers\Web\CountyAdminController::class, 'updateContent'])->name('county.admin.content');
    Route::post('/county-admin/{slug}/pro/image', [\App\Http\Controllers\Web\CountyMediaController::class, 'uploadImage'])->name('county.admin.image.upload');
    Route::post('/county-admin/{slug}/pro/image/{sector}/delete', [\App\Http\Controllers\Web\CountyMediaController::class, 'deleteImage'])->name('county.admin.image.delete');
    Route::post('/county-admin/{slug}/pro/sector-video', [\App\Http\Controllers\Web\CountyMediaController::class, 'uploadSectorVideo'])->name('county.admin.sector.video.upload');
    Route::post('/county-admin/{slug}/pro/sector-video/{sector}/delete', [\App\Http\Controllers\Web\CountyMediaController::class, 'deleteSectorVideo'])->name('county.admin.sector.video.delete');
    Route::post('/county-admin/{slug}/pro/4d-video', [\App\Http\Controllers\Web\CountyMediaController::class, 'upload4dVideo'])->name('county.admin.4d.upload');
    Route::post('/county-admin/{slug}/pro/4d-video/{entityType}/{entityId}/delete', [\App\Http\Controllers\Web\CountyMediaController::class, 'delete4dVideo'])->name('county.admin.4d.delete');
    Route::post('/county-admin/{slug}/pro/price', [\App\Http\Controllers\Web\CountyAdminController::class, 'updatePrice'])->name('county.admin.price');
    Route::post('/county-admin/{slug}/pro/ads', [\App\Http\Controllers\Web\CountyAdminController::class, 'createAd'])->name('county.admin.ads');
    Route::post('/county-admin/{slug}/pro/package', [\App\Http\Controllers\Web\CountyAdminController::class, 'purchasePackage'])->name('county.admin.package');
    Route::get('/county-admin/{slug}/pro/report/{type}', [\App\Http\Controllers\Web\CountyAdminController::class, 'downloadReport'])->name('county.admin.report');
    Route::post('/county-admin/{slug}/pro/details', [\App\Http\Controllers\Web\CountyAdminController::class, 'updateDetails'])->name('county.admin.details');
    Route::post('/county-admin/{slug}/pro/sector', [\App\Http\Controllers\Web\CountyAdminController::class, 'toggleSector'])->name('county.admin.sector');
    Route::post('/county-admin/{slug}/pro/sector/tile', [\App\Http\Controllers\Web\CountyAdminController::class, 'toggleTileSector'])->name('county.admin.sector.tile');
    Route::post('/county-admin/{slug}/pro/trade-hub', [\App\Http\Controllers\Web\CountyAdminController::class, 'updateTradeHub'])->name('county.admin.trade.hub');
    Route::post('/county-admin/{slug}/pro/spotlight', [\App\Http\Controllers\Web\CountyAdminController::class, 'storeTraderSpotlight'])->name('county.admin.spotlight.store');
    Route::post('/county-admin/{slug}/pro/broadcast', [\App\Http\Controllers\Web\CountyAdminController::class, 'updateBroadcast'])->name('county.admin.broadcast');
    // Virtual Expo — P2: Flythroughs, Drones, Floor Plans
    Route::post('/county-admin/{slug}/pro/housing', [\App\Http\Controllers\Web\CountyAdminController::class, 'storeHousingProject'])->name('county.admin.housing.store');
    Route::post('/county-admin/{slug}/pro/drone', [\App\Http\Controllers\Web\CountyAdminController::class, 'storeDroneSequence'])->name('county.admin.drone.store');
    Route::post('/county-admin/{slug}/pro/floor-plan', [\App\Http\Controllers\Web\CountyAdminController::class, 'storeFloorPlan'])->name('county.admin.floor.store');
    // Virtual Expo — P3: Consent Forms, Voice Notes
    Route::post('/county-admin/{slug}/pro/consent', [\App\Http\Controllers\Web\CountyAdminController::class, 'storeConsentForm'])->name('county.admin.consent.store');
    Route::post('/county-admin/{slug}/pro/voice-note', [\App\Http\Controllers\Web\CountyAdminController::class, 'storeVoiceNote'])->name('county.admin.voice.store');
    // Virtual Expo — P4: Landmarks
    Route::post('/county-admin/{slug}/pro/landmark', [\App\Http\Controllers\Web\CountyAdminController::class, 'storeLandmark'])->name('county.admin.landmark.store');
    Route::post('/county-admin/{slug}/pro/hero-video', [\App\Http\Controllers\Web\CountyMediaController::class, 'uploadHeroVideo'])->name('county.admin.hero.upload');
    Route::post('/county-admin/{slug}/pro/hero-video/delete', [\App\Http\Controllers\Web\CountyMediaController::class, 'deleteHeroVideo'])->name('county.admin.hero.delete');
    Route::post('/county-admin/{slug}/pro/flag-video', [\App\Http\Controllers\Web\CountyMediaController::class, 'uploadFlagVideo'])->name('county.admin.flag.upload');
    Route::post('/county-admin/{slug}/pro/flag-video/delete', [\App\Http\Controllers\Web\CountyMediaController::class, 'deleteFlagVideo'])->name('county.admin.flag.delete');
    Route::post('/county-admin/{slug}/pro/entity', [\App\Http\Controllers\Web\CountyAdminController::class, 'addEntity'])->name('county.admin.entity');
    Route::post('/county-admin/{slug}/pro/entity/{entityId}/delete', [\App\Http\Controllers\Web\CountyAdminController::class, 'deleteEntity'])->name('county.admin.entity.delete');
    Route::post('/county-admin/{slug}/pro/institutions', [\App\Http\Controllers\Web\CountyAdminController::class, 'storeInstitution'])->name('county.admin.institution.store');
    Route::post('/county-admin/{slug}/pro/institutions/{institutionId}/delete', [\App\Http\Controllers\Web\CountyAdminController::class, 'deleteInstitution'])->name('county.admin.institution.delete');
    }); // county.scope

    // ═══ INSTITUTION ADMIN PORTAL (strict per-institution access) ═══
    Route::get('/institution-admin/{institution}', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'dashboard'])->name('institution.admin');
    Route::post('/institution-admin/{institution}/profile', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'updateProfile'])->name('institution.admin.profile');
    Route::post('/institution-admin/{institution}/logo', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'uploadLogo'])->name('institution.admin.logo');
    Route::post('/institution-admin/{institution}/hero-video', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'uploadHeroVideo'])->name('institution.admin.hero-video');
    Route::post('/institution-admin/{institution}/flag-video', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'uploadFlagVideo'])->name('institution.admin.flag-video');
    Route::post('/institution-admin/{institution}/production', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'updateProduction'])->name('institution.admin.production');
    Route::post('/institution-admin/{institution}/sectors', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'updateSectors'])->name('institution.admin.sectors');
    Route::post('/institution-admin/{institution}/products', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'storeProduct'])->name('institution.admin.products.store');
    Route::post('/institution-admin/{institution}/products/{product}/update', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'updateProduct'])->name('institution.admin.products.update');
    Route::post('/institution-admin/{institution}/products/{index}/delete', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'deleteProduct'])->name('institution.admin.products.delete');
    Route::post('/institution-admin/{institution}/videos', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'uploadVideo'])->name('institution.admin.videos.upload');
    Route::post('/institution-admin/{institution}/videos/{index}/delete', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'deleteVideo'])->name('institution.admin.videos.delete');
    Route::get('/institution-admin/{institution}/sync', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'sync'])->name('institution.admin.sync');
    Route::post('/institution-admin/{institution}/team', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'addTeamMember'])->name('institution.admin.team.add');
    Route::post('/institution-admin/{institution}/team/{userId}/remove', [\App\Http\Controllers\Web\InstitutionAdminController::class, 'removeTeamMember'])->name('institution.admin.team.remove');

    // ── 3D Asset Admin (KICC superadmin) ──
    Route::get('/kicc-admin/3d-assets', [\App\Http\Controllers\Web\Admin3dAssetsController::class, 'index'])->name('admin.3d.assets')->middleware('admin:kicc');
    Route::match(['get', 'post'], '/kicc-admin/3d-assets/upload', [\App\Http\Controllers\Web\Admin3dAssetsController::class, 'upload'])->name('admin.3d.upload')->middleware('admin:kicc');
    Route::post('/kicc-admin/3d-assets/{assetId}/attach', [\App\Http\Controllers\Web\Admin3dAssetsController::class, 'attach'])->name('admin.3d.attach')->middleware('admin:kicc');
    Route::post('/kicc-admin/3d-assets/{assetId}/detach', [\App\Http\Controllers\Web\Admin3dAssetsController::class, 'detach'])->name('admin.3d.detach')->middleware('admin:kicc');
    Route::post('/kicc-admin/3d-assets/{assetId}/delete', [\App\Http\Controllers\Web\Admin3dAssetsController::class, 'delete'])->name('admin.3d.delete')->middleware('admin:kicc');

    // ── Institution Admin — 3D Asset Management ──
    Route::get('/institution-admin/{institution}/3d', [\App\Http\Controllers\Web\Admin3dAssetsController::class, 'index'])->name('admin.3d.institution');
    Route::match(['get', 'post'], '/institution-admin/{institution}/3d/upload', [\App\Http\Controllers\Web\Admin3dAssetsController::class, 'upload'])->name('admin.3d.upload.institution');
    Route::post('/institution-admin/{institution}/3d/room3d', [\App\Http\Controllers\Web\Admin3dAssetsController::class, 'storeRoom3d'])->name('admin.3d.room3d.store.institution');

    // Private Exhibitor Portal
    Route::get('/exhibitor-admin', [\App\Http\Controllers\Web\ExhibitorPortalController::class, 'index'])->name('exhibitor.admin');
    Route::post('/exhibitor-admin/products', [\App\Http\Controllers\Web\ExhibitorPortalController::class, 'storeProduct'])->name('exhibitor.admin.products.store');
    Route::post('/exhibitor-admin/products/{id}/delete', [\App\Http\Controllers\Web\ExhibitorPortalController::class, 'deleteProduct'])->name('exhibitor.admin.products.delete');
    Route::post('/exhibitor-admin/upgrade', [\App\Http\Controllers\Web\ExhibitorPortalController::class, 'upgrade'])->name('exhibitor.admin.upgrade');
    Route::get('/exhibitor-onboarding', [\App\Http\Controllers\Web\ExhibitorOnboardingController::class, 'show'])->name('exhibitor.onboarding');
    Route::post('/exhibitor-onboarding', [\App\Http\Controllers\Web\ExhibitorOnboardingController::class, 'store'])->name('exhibitor.onboarding.store');

    // Travel Provider Portal (airlines, hotels, cab companies)
    Route::get('/provider-admin', [\App\Http\Controllers\Web\ProviderPortalController::class, 'index'])->name('provider.admin');
    Route::post('/provider-admin/price', [\App\Http\Controllers\Web\ProviderPortalController::class, 'updatePrice'])->name('provider.admin.price');
    Route::post('/provider-admin/add', [\App\Http\Controllers\Web\ProviderPortalController::class, 'addService'])->name('provider.admin.add');

    // KICC approvals
    Route::post('/kicc-admin/approve/{table}/{id}', [\App\Http\Controllers\Web\KiccAdminController::class, 'approveService'])->name('kicc.admin.approve');
    Route::post('/kicc-admin/deny/{table}/{id}', [\App\Http\Controllers\Web\KiccAdminController::class, 'denyService'])->name('kicc.admin.deny');
Route::post('/kicc-admin/pipelines/store', [\App\Http\Controllers\Web\KiccAdminController::class, 'storePipeline'])->name('kicc.admin.pipeline.store');
Route::post('/kicc-admin/licence/upload', [\App\Http\Controllers\Web\PipelineLicenceController::class, 'upload'])->name('kicc.admin.licence.upload');
Route::post('/kicc-admin/licence/{id}/approve', [\App\Http\Controllers\Web\PipelineLicenceController::class, 'approve'])->name('kicc.admin.licence.approve');
Route::post('/kicc-admin/licence/{id}/reject', [\App\Http\Controllers\Web\PipelineLicenceController::class, 'reject'])->name('kicc.admin.licence.reject');
Route::post('/kicc-admin/pipeline/{code}/config', [\App\Http\Controllers\Web\PipelineLicenceController::class, 'updateConfig'])->name('kicc.admin.pipeline.config');

// ── Venue management from Mother Admin ──
Route::post('/kicc-admin/venues/store', [\App\Http\Controllers\Web\KiccAdminController::class, 'storeVenue'])->name('kicc.admin.venue.store')->middleware('admin:kicc');
Route::post('/kicc-admin/venues/{id}/update', [\App\Http\Controllers\Web\KiccAdminController::class, 'updateVenue'])->name('kicc.admin.venue.update')->middleware('admin:kicc');

// ── Public pipeline sector pages (bypass Authenticate middleware) ──
// REMOVED — pipeline data is proprietary and must be admin-only.
// Pipeline management available at /kicc-admin (Mother Admin) and

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
Route::get('/attractions/{attraction}', [\App\Http\Controllers\Web\AttractionBookingController::class, 'show'])->name('attractions.show');
Route::post('/attractions/{attraction}/book', [\App\Http\Controllers\Web\AttractionBookingController::class, 'book'])->name('attractions.book')->middleware('auth');

// Experience Builder Pipeline (single-page experience planner)
Route::get('/experience/{type}/{id}/plan', [\App\Http\Controllers\Web\ExperienceBuilderController::class, 'plan'])->name('experience.plan');
Route::post('/experience/{type}/{id}/build', [\App\Http\Controllers\Web\ExperienceBuilderController::class, 'build'])->name('experience.build');
Route::get('/experience/{type}/{id}/receipt', [\App\Http\Controllers\Web\ExperienceBuilderController::class, 'receipt'])->name('experience.receipt');
Route::get('/experience/{type}/{id}/itinerary', [\App\Http\Controllers\Web\ExperienceBuilderController::class, 'itinerary'])->name('experience.itinerary');

// Travel & Tourism
Route::get('/travel', [TravelController::class, 'index'])->name('travel.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/travel/flights', [TravelController::class, 'flights'])->name('travel.flights')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::post('/travel/book', [TravelController::class, 'book'])->name('travel.book')->middleware('auth');
Route::get('/travel/receipt/{groupRef}', [TravelController::class, 'receipt'])->name('travel.receipt')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);

// Platform operations (Advertising, SEO, Logistics)
Route::get('/operations', [OperationsController::class, 'index'])->name('operations.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);

Route::get('/exhibitions', [ExhibitionController::class, 'index'])->name('exhibitions.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/exhibitions/{slug}', [ExhibitionController::class, 'show'])->name('exhibitions.show')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);

// ── Live Streams ──
Route::get('/streams', [\App\Http\Controllers\Web\StreamController::class, 'index'])->name('streams.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/streams/admin', [\App\Http\Controllers\Web\StreamController::class, 'adminIndex'])->name('streams.admin')->middleware('auth');
Route::get('/streams/create', [\App\Http\Controllers\Web\StreamController::class, 'create'])->name('streams.create')->middleware('auth');
Route::post('/streams', [\App\Http\Controllers\Web\StreamController::class, 'store'])->name('streams.store')->middleware('auth');
Route::get('/streams/{stream}', [\App\Http\Controllers\Web\StreamController::class, 'show'])->name('streams.show');
Route::post('/streams/{stream}/go-live', [\App\Http\Controllers\Web\StreamController::class, 'goLive'])->name('streams.go-live')->middleware('auth');
Route::post('/streams/{stream}/end', [\App\Http\Controllers\Web\StreamController::class, 'endStream'])->name('streams.end')->middleware('auth');
Route::put('/streams/{stream}', [\App\Http\Controllers\Web\StreamController::class, 'update'])->name('streams.update')->middleware('auth');
Route::post('/streams/{stream}/thumbnail', [\App\Http\Controllers\Web\StreamController::class, 'setThumbnail'])->name('streams.thumbnail')->middleware('auth');
Route::delete('/streams/{stream}', [\App\Http\Controllers\Web\StreamController::class, 'destroy'])->name('streams.destroy')->middleware('auth');
Route::get('/streams/{stream}/chat', [\App\Http\Controllers\Web\StreamController::class, 'apiChatMessages'])->name('streams.chat');
Route::post('/streams/{stream}/chat', [\App\Http\Controllers\Web\StreamController::class, 'apiPostChat'])->name('streams.chat.post');
Route::get('/api/streams/live', [\App\Http\Controllers\Web\StreamController::class, 'apiLiveStreams'])->name('api.streams.live');

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
// ── R2 Direct Upload (bypasses Cloudflare 100MB Worker limit) ──
Route::post('/api/r2/presigned-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'confirmR2Upload'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/messages', [\App\Http\Controllers\Web\MessagingController::class, 'inbox'])->name('messaging.inbox');
    Route::get('/messages/{conversation}', [\App\Http\Controllers\Web\MessagingController::class, 'show'])->name('messaging.show');
    Route::post('/messages/start', [\App\Http\Controllers\Web\MessagingController::class, 'start'])->name('messaging.start');
    Route::post('/messages/{conversation}/send', [\App\Http\Controllers\Web\MessagingController::class, 'send'])->name('messaging.send');
    Route::get('/api/messages/unread', [\App\Http\Controllers\Web\MessagingController::class, 'unreadCount'])->name('api.messages.unread');
});

// Notifications
// ── R2 Direct Upload (bypasses Cloudflare 100MB Worker limit) ──
Route::post('/api/r2/presigned-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'confirmR2Upload'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\Web\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Web\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\Web\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/api/notifications/unread', [\App\Http\Controllers\Web\NotificationController::class, 'unreadCount'])->name('api.notifications.unread');
});

// Coupons
Route::post('/cart/coupon', [\App\Http\Controllers\Web\CouponController::class, 'apply'])->name('coupon.apply')->middleware('auth');
Route::post('/cart/coupon/remove', [\App\Http\Controllers\Web\CouponController::class, 'remove'])->name('coupon.remove')->middleware('auth');
// ── R2 Direct Upload (bypasses Cloudflare 100MB Worker limit) ──
Route::post('/api/r2/presigned-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'confirmR2Upload'])->middleware('auth');

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
Route::post('/ai/itinerary', [\App\Http\Controllers\Web\AIController::class, 'itinerary'])->name('ai.itinerary.api')->middleware('throttle:30,1');
Route::get('/api/recommendations', [\App\Http\Controllers\Web\AIController::class, 'recommendations'])->name('api.recommendations');
Route::get('/api/forecast', [\App\Http\Controllers\Web\AIController::class, 'forecast'])->name('api.forecast');
Route::post('/api/fraud-check', [\App\Http\Controllers\Web\AIController::class, 'fraudCheck'])->name('api.fraud.check')->middleware('throttle:30,1');

// Layer 2: Dynamic image optimizer — resizes + transcodes any platform image to WebP, caches in R2 (immutable)
Route::get('/api/optimize-image', \App\Http\Controllers\Web\OptimizeImageController::class)->name('api.optimize-image');

// Integrations
Route::get('/integrations', [\App\Http\Controllers\Web\IntegrationController::class, 'settings'])->name('integrations.settings');
Route::get('/counties/{county}/map', [\App\Http\Controllers\Web\IntegrationController::class, 'map'])->name('counties.map');
Route::get('/counties/{county}/weather', [\App\Http\Controllers\Web\IntegrationController::class, 'weather'])->name('counties.weather');

// LMS / Capacity Building
Route::get('/lms', [\App\Http\Controllers\Web\CourseController::class, 'index'])->name('lms.index');
Route::get('/lms/{course}', [\App\Http\Controllers\Web\CourseController::class, 'show'])->name('lms.show');
Route::post('/lms/{course}/enroll', [\App\Http\Controllers\Web\CourseController::class, 'enroll'])->name('lms.enroll')->middleware('auth');
// ── R2 Direct Upload (bypasses Cloudflare 100MB Worker limit) ──
Route::post('/api/r2/presigned-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'confirmR2Upload'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/my-courses', [\App\Http\Controllers\Web\CourseController::class, 'myCourses'])->name('lms.my-courses');
});

// Safety & Security
Route::get('/safety/alerts', [\App\Http\Controllers\Web\SafetyController::class, 'alerts'])->name('safety.alerts');
Route::get('/safety/report', [\App\Http\Controllers\Web\SafetyController::class, 'reportForm'])->name('safety.report');
Route::post('/safety/report', [\App\Http\Controllers\Web\SafetyController::class, 'submitReport'])->name('safety.report.submit')->middleware(['auth', 'throttle:10,1']);

// KICC Website (kicc.co.ke functionality)
Route::get('/national-government', [\App\Http\Controllers\Web\NationalGovernmentController::class, 'index'])->name('national-government.index');
Route::get('/kicc/news', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'newsIndex'])->name('kicc.news');
Route::get('/kicc/news/{slug}', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'newsShow'])->name('kicc.news.show');
Route::get('/kicc/about', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'about'])->name('kicc.about');
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
Route::get('/kicc/about-us', function () { return redirect('/kicc/mission'); })->name('kicc.about-us');
Route::get('/kicc/leadership', [\App\Http\Controllers\Web\KiccWebsiteController::class, 'leadership'])->name('kicc.leadership');
Route::get('/faq', function () { return redirect('/kicc/faq'); })->name('faq');
Route::get('/contact', function () { return redirect('/kicc/event-booking'); })->name('contact');
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
    Route::get('/', [\App\Http\Controllers\Web\NationalAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/redirect', [\App\Http\Controllers\Web\NationalAdminController::class, 'index'])->name('index');
    // Media routes (before generic ministry route)
    Route::post('/hero', [\App\Http\Controllers\Web\NationalAdminController::class, 'uploadNationalHero'])->name('hero.upload');
    Route::post('/hero/delete', [\App\Http\Controllers\Web\NationalAdminController::class, 'deleteNationalHero'])->name('hero.delete');
    Route::post('/ministries/{ministry}/video', [\App\Http\Controllers\Web\NationalAdminController::class, 'uploadMinistryVideo'])->name('ministry.video.upload');
    Route::post('/ministries/{ministry}/video/delete', [\App\Http\Controllers\Web\NationalAdminController::class, 'deleteMinistryVideo'])->name('ministry.video.delete');
    Route::post('/ministries/{ministry}/flag', [\App\Http\Controllers\Web\NationalAdminController::class, 'uploadMinistryFlag'])->name('ministry.flag.upload');
    Route::post('/ministries/{ministry}/flag/delete', [\App\Http\Controllers\Web\NationalAdminController::class, 'deleteMinistryFlag'])->name('ministry.flag.delete');
    Route::post('/flag', [\App\Http\Controllers\Web\NationalAdminController::class, 'uploadNationalFlag'])->name('flag.upload');
    Route::post('/flag/delete', [\App\Http\Controllers\Web\NationalAdminController::class, 'deleteNationalFlag'])->name('flag.delete');
    // Ministry & Agency CRUD
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
// ── R2 Direct Upload (bypasses Cloudflare 100MB Worker limit) ──
Route::post('/api/r2/presigned-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'confirmR2Upload'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/trade/enquiries', [\App\Http\Controllers\Web\TradeAdminController::class, 'enquiries'])->name('trade.admin.enquiries')->middleware('admin:kicc');
    Route::post('/kicc-admin/trade/enquiries/{enquiry}', [\App\Http\Controllers\Web\TradeAdminController::class, 'updateStatus'])->name('trade.admin.enquiry.status')->middleware('admin:kicc');
});

// Agent / Tour Operator Onboarding
Route::get('/agents/register', [\App\Http\Controllers\Web\AgentOnboardingController::class, 'register'])->name('agent.register');
Route::post('/agents/register', [\App\Http\Controllers\Web\AgentOnboardingController::class, 'store'])->name('agent.store');
Route::get('/agents/success/{agent}', [\App\Http\Controllers\Web\AgentOnboardingController::class, 'success'])->name('agent.onboarding.success');
// ── R2 Direct Upload (bypasses Cloudflare 100MB Worker limit) ──
Route::post('/api/r2/presigned-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'confirmR2Upload'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/agents', [\App\Http\Controllers\Web\AgentAdminController::class, 'index'])->name('agent.admin.index')->middleware('admin:kicc');
    Route::get('/kicc-admin/agents/{agent}', [\App\Http\Controllers\Web\AgentAdminController::class, 'show'])->name('agent.admin.show')->middleware('admin:kicc');
    Route::post('/kicc-admin/agents/{agent}/approve', [\App\Http\Controllers\Web\AgentAdminController::class, 'approve'])->name('agent.admin.approve')->middleware('admin:kicc');
    Route::post('/kicc-admin/agents/{agent}/reject', [\App\Http\Controllers\Web\AgentAdminController::class, 'reject'])->name('agent.admin.reject')->middleware('admin:kicc');
    Route::post('/kicc-admin/agents/documents/{document}/verify', [\App\Http\Controllers\Web\AgentAdminController::class, 'verifyDocument'])->name('agent.admin.document.verify')->middleware('admin:kicc');
});

// Reviews & Ratings
Route::post('/reviews', [\App\Http\Controllers\Web\ReviewController::class, 'store'])->name('review.store')->middleware('auth', 'throttle:10,1');
Route::post('/reviews/{review}/respond', [\App\Http\Controllers\Web\ReviewController::class, 'updateVendorResponse'])->name('review.respond')->middleware('auth', 'throttle:10,1');
Route::post('/reviews/product', [\App\Http\Controllers\Web\ReviewController::class, 'storeProduct'])->name('product.review.store')->middleware('auth', 'throttle:10,1');
Route::post('/reviews/entity', [\App\Http\Controllers\Web\ReviewController::class, 'storeEntity'])->name('entity.review.store')->middleware('auth', 'throttle:10,1');
// ── R2 Direct Upload (bypasses Cloudflare 100MB Worker limit) ──
Route::post('/api/r2/presigned-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'confirmR2Upload'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/kicc-admin/reviews', [\App\Http\Controllers\Web\ReviewAdminController::class, 'index'])->name('review.admin.index')->middleware('admin:kicc');
    Route::post('/kicc-admin/reviews/{review}/approve', [\App\Http\Controllers\Web\ReviewAdminController::class, 'approve'])->name('review.admin.approve')->middleware('admin:kicc');
    Route::post('/kicc-admin/reviews/{review}/reject', [\App\Http\Controllers\Web\ReviewAdminController::class, 'reject'])->name('review.admin.reject')->middleware('admin:kicc');
    Route::post('/kicc-admin/product-reviews/{review}/approve', [\App\Http\Controllers\Web\ReviewAdminController::class, 'approveProduct'])->name('review.admin.product.approve')->middleware('admin:kicc');
    Route::post('/kicc-admin/product-reviews/{review}/reject', [\App\Http\Controllers\Web\ReviewAdminController::class, 'rejectProduct'])->name('review.admin.product.reject')->middleware('admin:kicc');
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

// ─── Admin: Ecommerce (Full Pipeline) ───
Route::middleware(['auth', 'admin:kicc,national'])->prefix('kicc-admin/ecommerce')->name('admin.ecommerce.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/products', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'products'])->name('products');
    Route::get('/products/create', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'productEdit'])->name('product.create');
    Route::post('/products', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'productStore'])->name('product.store');
    Route::get('/products/{id}/edit', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'productEdit'])->name('product.edit');
    Route::put('/products/{id}', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'productUpdate'])->name('product.update');
    Route::delete('/products/{id}', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'productDelete'])->name('product.delete');
    Route::post('/products/bulk', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'productBulkAction'])->name('product.bulk');
    Route::post('/products/{productId}/variants', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'variantStore'])->name('variant.store');
    Route::put('/variants/{variantId}', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'variantUpdate'])->name('variant.update');

    Route::get('/import', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'importForm'])->name('import.form');
    Route::post('/import/run', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'importRun'])->name('import.run');
    Route::post('/import/bulk', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'importBulk'])->name('import.bulk');

    Route::get('/orders', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'orders'])->name('orders');
    Route::get('/orders/{id}', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'orderShow'])->name('order.show');
    Route::post('/orders/{id}/status', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'orderUpdateStatus'])->name('order.status');
    Route::get('/analytics', [\App\Http\Controllers\Admin\EcommerceAdminController::class, 'analytics'])->name('analytics');
});

Route::get('/venues', [\App\Http\Controllers\Web\ExhibitionController::class, 'venues'])->name('venues.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/venues/{venue}', [VenueController::class, 'show'])->name('venues.show')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::post('/venues/{venue}/inquire', [VenueController::class, 'inquire'])->name('venues.inquire')->middleware('auth');

// Exhibition screen videos
Route::get('/screens', [ScreenController::class, 'directory'])->name('screens.directory');
Route::get('/screens/{screen}', [ScreenController::class, 'show'])->name('screens.show')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::post('/screens/{screen}/advertise', [ScreenController::class, 'advertise'])->name('screens.advertise')->middleware('auth');

// 3D Exhibition experiences (standalone views)
Route::view('/exhibition-3d/map', 'exhibition-3d.map')->name('exhibition-3d.map');
Route::view('/exhibition-3d/sector', 'exhibition-3d.sector')->name('exhibition-3d.sector');
Route::view('/exhibition-3d/booth', 'exhibition-3d.booth')->name('exhibition-3d.booth');
Route::view('/exhibition-3d/terrain', 'exhibition-3d.terrain')->name('exhibition-3d.terrain');

// 3D Room Explorer
Route::get('/room3d', [Room3dController::class, 'index'])->name('room3d.index');
Route::get('/room3d/create', [Room3dController::class, 'create'])->name('room3d.create');
Route::post('/room3d', [Room3dController::class, 'store'])->name('room3d.store');
Route::get('/room3d/{id}', [Room3dController::class, 'show'])->name('room3d.show')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/room3d/{id}/viewer', [Room3dController::class, 'viewer'])->name('room3d.viewer');
Route::get('/room3d/{id}/api', [Room3dController::class, 'api'])->name('room3d.api');


// ── R2 Direct Upload (bypasses Cloudflare 100MB Worker limit) ──
Route::post('/api/r2/presigned-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'presignedUploadUrl'])->middleware('auth');
Route::post('/api/r2/confirm-upload', [\x5cApp\x5cHttp\x5cControllers\x5cWeb\x5cMediaLibraryController::class, 'confirmR2Upload'])->middleware('auth');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/exhibitions', [DashboardController::class, 'exhibitions'])->name('dashboard.exhibitions');
    Route::get('/dashboard/bookings', [DashboardController::class, 'bookings'])->name('dashboard.bookings');
    Route::get('/dashboard/profile', [DashboardController::class, 'profile'])->name('dashboard.profile');
    Route::post('/dashboard/profile', [DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::get('/dashboard/addresses', [DashboardController::class, 'addresses'])->name('dashboard.addresses');
    Route::post('/dashboard/addresses', [DashboardController::class, 'saveAddress'])->name('dashboard.addresses.save');
    Route::delete('/dashboard/addresses/{id}', [DashboardController::class, 'deleteAddress'])->name('dashboard.addresses.delete');
    Route::get('/dashboard/security', [DashboardController::class, 'security'])->name('dashboard.security');
    Route::post('/dashboard/security/2fa', [DashboardController::class, 'toggle2fa'])->name('dashboard.security.2fa');
    Route::get('/dashboard/reviews', [DashboardController::class, 'myReviews'])->name('dashboard.reviews');
    Route::get('/dashboard/referrals', [DashboardController::class, 'referrals'])->name('dashboard.referrals');
    Route::get('/dashboard/gift-cards', [DashboardController::class, 'giftCards'])->name('dashboard.gift-cards');
    Route::get('/dashboard/seller', [DashboardController::class, 'seller'])->name('dashboard.seller');
});

// Google OAuth

// Livestreams
Route::get('/livestreams', [LivestreamController::class, 'index'])->name('livestreams.index')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);
Route::get('/livestreams/{slug}', [LivestreamController::class, 'show'])->name('livestreams.show')
    ->middleware($publicCache)
    ->withoutMiddleware($noSession);

// [ADMIN] One-time image optimization trigger
Route::post('/__admin/optimize-images', function () {
    if (!app()->environment('local', 'production')) abort(404);
    $artisan = new \App\Console\Commands\OptimizeImages();
    $artisan->setLaravel(app());
    return $artisan->handle(app(\App\Services\ImageOptimizer::class));
})->middleware('auth');

// ── One-shot seeder trigger (run via curl) ──
Route::any('/trigseed/{token}', function (string $token) {
    if ($token !== 'kicc-seed-2026x') abort(403);
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'CrossCountyInstitutionsSeeder', '--force' => true]);
    \Illuminate\Support\Facades\Cache::flush();
    return response('<pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>');
});


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

// Screen Broadcast Control — videographer panel for routing live streams to screens
Route::middleware(['auth', 'verified'])->prefix('broadcast')->name('live.screens.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Live\ScreenBroadcastController::class, 'index'])->name('broadcast');
    Route::post('/route', [\App\Http\Controllers\Live\ScreenBroadcastController::class, 'routeToScreen'])->name('route');
    Route::post('/route-all', [\App\Http\Controllers\Live\ScreenBroadcastController::class, 'routeToAll'])->name('route-all');
    Route::post('/route-venue', [\App\Http\Controllers\Live\ScreenBroadcastController::class, 'routeToScreenByName'])->name('route-venue');
});

// Media proxy — serves R2 files directly through Laravel (fallback when worker is unavailable)
Route::get('/media/video/{path}', [\App\Http\Controllers\Web\MediaProxyController::class, 'video'])->where('path', '.*');
Route::get('/media/derivatives/{path}', [\App\Http\Controllers\Web\MediaProxyController::class, 'derivative'])->where('path', '.*');

// Murang'a County Admin SPA (dark mode command center)
Route::get('/muranga-admin/{path?}', function () {
    return response()->file(public_path('muranga-admin/index.html'));
})->where('path', '.*')->middleware('auth');

// ── Digital Consent Forms (Public — compliant with Kenya DPA 2019 & GDPR) ──
Route::get('/consent/verify', [\App\Http\Controllers\Web\ConsentController::class, 'verify'])->name('consent.verify');
Route::get('/consent/physical', [\App\Http\Controllers\Web\ConsentController::class, 'physical'])->name('consent.physical');
Route::get('/consent/{slug}', [\App\Http\Controllers\Web\ConsentController::class, 'show'])->name('consent.show');
Route::post('/consent/{slug}/sign', [\App\Http\Controllers\Web\ConsentController::class, 'sign'])->name('consent.sign');
Route::get('/consent/{slug}/confirmation/{record}', [\App\Http\Controllers\Web\ConsentController::class, 'confirmation'])->name('consent.confirmation');

// ═══ 3D Experiences (Item #7) ═══════════════════════════════════════════════
Route::get('/3d/counties/{county:slug}', function (\App\Models\County $county) {
    return \Inertia\Inertia::render('3D/County3D', [
        'county' => ['id' => $county->id, 'name' => $county->name, 'slug' => $county->slug],
        'modelUrl' => "https://kicctest.org/3d/models/{$county->slug}.glb",
    ]);
})->name('3d.county')->middleware($publicCache);

Route::get('/3d/splats/{name}', function (string $name) {
    $allowed = ['university_orig', 'hospital', 'hospital_netflix', 'hospital_v3'];
    abort_unless(in_array($name, $allowed, true), 404);
    return \Inertia\Inertia::render('3D/SplatViewer', [
        'splatName' => $name,
        'splatUrl' => "https://kicctest.org/3d/splats/{$name}.splat",
    ]);
})->name('3d.splat')->middleware($publicCache);
