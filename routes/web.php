<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AdminPortalController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\KiccAdminController;
use App\Http\Controllers\Web\NationalPortalController;
use App\Http\Controllers\Web\CountyPortalController;
use App\Http\Controllers\Web\CountyAdminController;
use App\Http\Controllers\Web\CmsController;
use App\Http\Controllers\Web\NationalAdminController;
use App\Http\Controllers\Web\AgentAdminController;
use App\Http\Controllers\Web\ReviewAdminController;
use App\Http\Controllers\Web\TradeAdminController;
use App\Http\Controllers\Web\CouponController;
use App\Http\Controllers\Web\FlashSaleController;
use App\Http\Controllers\Web\MfaController;
use App\Http\Controllers\Web\DashboardV2Controller;
use App\Http\Controllers\Web\AuthController;

// ─── PUBLIC ADMIN AUTH ROUTES ───
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/login-code', [AuthController::class, 'showLoginCodeForm'])->name('login.code');
Route::post('/login-code', [AuthController::class, 'verifyLoginCode']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.phone');
Route::post('/register', [AuthController::class, 'completeRegistration']);

Route::middleware('auth')->group(function () {
    // Portal selector
    Route::get('/portal', [AdminPortalController::class, 'selector'])->name('admin.portal');
    Route::get('/admin/national', [AdminPortalController::class, 'national'])->name('admin.national');
    Route::get('/admin/county', [AdminPortalController::class, 'county'])->name('admin.county');

    // Legacy dashboards
    Route::get('/dashboard/county', [DashboardV2Controller::class, 'county'])->name('dashboard.county');
    Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])->name('dashboard.admin')->middleware('admin:kicc');
    Route::post('/dashboard/admin/delete-product/{id}', [AdminDashboardController::class, 'deleteProduct'])->name('admin.delete-product')->middleware('admin:kicc');
    Route::post('/dashboard/admin/delete-user/{id}', [AdminDashboardController::class, 'deleteUser'])->name('admin.delete-user')->middleware('admin:kicc');
    Route::post('/dashboard/admin/delete-order/{id}', [AdminDashboardController::class, 'deleteOrder'])->name('admin.delete-order')->middleware('admin:kicc');

    // KICC Overall Admin
    Route::get('/kicc-admin', [KiccAdminController::class, 'index'])->name('kicc.admin')->middleware('admin:kicc');
    Route::post('/kicc-admin/escrow/{id}/release', [KiccAdminController::class, 'releaseEscrow'])->name('kicc.admin.escrow.release')->middleware('admin:kicc');
    Route::post('/kicc-admin/approve/{table}/{id}', [KiccAdminController::class, 'approveService'])->name('kicc.admin.approve');

    // Coupons
    Route::get('/kicc-admin/coupons', [CouponController::class, 'adminIndex'])->name('coupon.admin.index')->middleware('admin:kicc');
    Route::post('/kicc-admin/coupons', [CouponController::class, 'adminStore'])->name('coupon.admin.store')->middleware('admin:kicc');

    // Flash Sales
    Route::get('/kicc-admin/flash-sales', [FlashSaleController::class, 'admin'])->name('kicc-admin.flash-sales')->middleware('admin:kicc');
    Route::post('/kicc-admin/flash-sales', [FlashSaleController::class, 'store'])->name('kicc-admin.flash-sales.store')->middleware('admin:kicc');
    Route::post('/kicc-admin/flash-sales/{id}/products', [FlashSaleController::class, 'addProduct'])->name('kicc-admin.flash-sales.add-product')->middleware('admin:kicc');

    // CMS
    Route::middleware(['admin:kicc'])->prefix('kicc-admin/cms')->name('cms.admin.')->group(function () {
        Route::get('/', [CmsController::class, 'adminIndex'])->name('index');
        Route::post('/pages', [CmsController::class, 'storePage'])->name('page.store');
        Route::post('/pages/{page}', [CmsController::class, 'updatePage'])->name('page.update');
        Route::post('/team', [CmsController::class, 'storeTeamMember'])->name('team.store');
        Route::post('/team/{member}', [CmsController::class, 'updateTeamMember'])->name('team.update');
        Route::get('/team/{member}/delete', [CmsController::class, 'deleteTeamMember'])->name('team.delete');
        Route::post('/timeline', [CmsController::class, 'storeTimelineEvent'])->name('timeline.store');
        Route::get('/timeline/{event}/delete', [CmsController::class, 'deleteTimelineEvent'])->name('timeline.delete');
        Route::post('/faq', [CmsController::class, 'storeFaq'])->name('faq.store');
        Route::post('/faq/{faq}', [CmsController::class, 'updateFaq'])->name('faq.update');
        Route::get('/faq/{faq}/delete', [CmsController::class, 'deleteFaq'])->name('faq.delete');
        Route::post('/video', [CmsController::class, 'storeVideo'])->name('video.store');
        Route::get('/video/{video}/delete', [CmsController::class, 'deleteVideo'])->name('video.delete');
    });

    // National Admin (kicc-admin prefix)
    Route::middleware(['admin:national'])->prefix('kicc-admin/national')->name('national.admin.v2.')->group(function () {
        Route::get('/', [NationalAdminController::class, 'index'])->name('index');
        Route::post('/ministries', [NationalAdminController::class, 'storeMinistry'])->name('ministry.store');
        Route::post('/ministries/{ministry}', [NationalAdminController::class, 'updateMinistry'])->name('ministry.update');
        Route::get('/ministries/{ministry}/delete', [NationalAdminController::class, 'deleteMinistry'])->name('ministry.delete');
        Route::post('/agencies', [NationalAdminController::class, 'storeAgency'])->name('agency.store');
        Route::get('/agencies/{agency}/delete', [NationalAdminController::class, 'deleteAgency'])->name('agency.delete');
    });

    // National Government Portal
    Route::get('/national-admin', [NationalPortalController::class, 'index'])->name('national.admin')->middleware('admin:national');
    Route::post('/national-admin/ministries', [NationalPortalController::class, 'storeMinistry'])->name('national.admin.ministry.store')->middleware('admin:national');
    Route::post('/national-admin/ministries/{ministry}', [NationalPortalController::class, 'updateMinistry'])->name('national.admin.ministry.update')->middleware('admin:national');
    Route::get('/national-admin/ministries/{ministry}/delete', [NationalPortalController::class, 'deleteMinistry'])->name('national.admin.ministry.delete')->middleware('admin:national');
    Route::post('/national-admin/agencies', [NationalPortalController::class, 'storeAgency'])->name('national.admin.agency.store')->middleware('admin:national');
    Route::post('/national-admin/agencies/{agency}', [NationalPortalController::class, 'updateAgency'])->name('national.admin.agency.update')->middleware('admin:national');
    Route::get('/national-admin/agencies/{agency}/delete', [NationalPortalController::class, 'deleteAgency'])->name('national.admin.agency.delete')->middleware('admin:national');

    // Trade Admin
    Route::get('/kicc-admin/trade/enquiries', [TradeAdminController::class, 'enquiries'])->name('trade.admin.enquiries')->middleware('admin:kicc');
    Route::post('/kicc-admin/trade/enquiries/{enquiry}', [TradeAdminController::class, 'updateStatus'])->name('trade.admin.enquiry.status')->middleware('admin:kicc');

    // Agent Admin
    Route::get('/kicc-admin/agents', [AgentAdminController::class, 'index'])->name('agent.admin.index')->middleware('admin:kicc');
    Route::get('/kicc-admin/agents/{agent}', [AgentAdminController::class, 'show'])->name('agent.admin.show')->middleware('admin:kicc');
    Route::post('/kicc-admin/agents/{agent}/approve', [AgentAdminController::class, 'approve'])->name('agent.admin.approve')->middleware('admin:kicc');
    Route::post('/kicc-admin/agents/{agent}/reject', [AgentAdminController::class, 'reject'])->name('agent.admin.reject')->middleware('admin:kicc');
    Route::post('/kicc-admin/agents/documents/{document}/verify', [AgentAdminController::class, 'verifyDocument'])->name('agent.admin.document.verify')->middleware('admin:kicc');

    // Review Admin
    Route::get('/kicc-admin/reviews', [ReviewAdminController::class, 'index'])->name('review.admin.index')->middleware('admin:kicc');
    Route::post('/kicc-admin/reviews/{review}/approve', [ReviewAdminController::class, 'approve'])->name('review.admin.approve')->middleware('admin:kicc');
    Route::post('/kicc-admin/reviews/{review}/reject', [ReviewAdminController::class, 'reject'])->name('review.admin.reject')->middleware('admin:kicc');

    // Commissions
    Route::get('/kicc-admin/commissions', function () {
        $commissions = \App\Models\CommissionLog::with('agent', 'order')->latest()->paginate(25);
        $totalPending = \App\Models\CommissionLog::where('status', 'pending')->sum('commission_amount');
        $totalSettled = \App\Models\CommissionLog::where('status', 'settled')->sum('commission_amount');
        return view('commissions.admin-index', compact('commissions', 'totalPending', 'totalSettled'));
    })->name('commission.admin.index')->middleware('admin:kicc');

    // MFA
    Route::get('/mfa/setup', [MfaController::class, 'showSetup'])->name('mfa.setup');
    Route::post('/mfa/setup', [MfaController::class, 'confirmSetup'])->name('mfa.setup.confirm');
    Route::post('/mfa/disable', [MfaController::class, 'disable'])->name('mfa.disable');

    // County Admin Pro (full content management)
    Route::get('/county-admin', [CountyPortalController::class, 'index'])->name('county.admin');
    Route::get('/county-admin/{slug}/pro', [CountyAdminController::class, 'dashboard'])->name('county.admin.pro');
    Route::post('/county-admin/{slug}/pro/content', [CountyAdminController::class, 'updateContent'])->name('county.admin.content');
    Route::post('/county-admin/{slug}/pro/image', [CountyAdminController::class, 'uploadImage'])->name('county.admin.image.upload');
    Route::post('/county-admin/{slug}/pro/image/{sector}/delete', [CountyAdminController::class, 'deleteImage'])->name('county.admin.image.delete');
    Route::post('/county-admin/{slug}/pro/4d-video', [CountyAdminController::class, 'upload4dVideo'])->name('county.admin.4d.upload');
    Route::post('/county-admin/{slug}/pro/4d-video/{entityType}/{entityId}/delete', [CountyAdminController::class, 'delete4dVideo'])->name('county.admin.4d.delete');
    Route::post('/county-admin/{slug}/pro/price', [CountyAdminController::class, 'updatePrice'])->name('county.admin.price');
    Route::post('/county-admin/{slug}/pro/ads', [CountyAdminController::class, 'createAd'])->name('county.admin.ads');
    Route::post('/county-admin/{slug}/pro/package', [CountyAdminController::class, 'purchasePackage'])->name('county.admin.package');
    Route::get('/county-admin/{slug}/pro/report/{type}', [CountyAdminController::class, 'downloadReport'])->name('county.admin.report');
    Route::post('/county-admin/{slug}/pro/details', [CountyAdminController::class, 'updateDetails'])->name('county.admin.details');
    Route::post('/county-admin/{slug}/pro/sector', [CountyAdminController::class, 'toggleSector'])->name('county.admin.sector');
    Route::post('/county-admin/{slug}/pro/sector/tile', [CountyAdminController::class, 'toggleTileSector'])->name('county.admin.sector.tile');
    Route::post('/county-admin/{slug}/pro/entity', [CountyAdminController::class, 'addEntity'])->name('county.admin.entity');
    Route::post('/county-admin/{slug}/pro/entity/{entityId}/delete', [CountyAdminController::class, 'deleteEntity'])->name('county.admin.entity.delete');
});

// MFA challenge (no auth — must be public for challenge flow)
Route::get('/mfa/challenge', [MfaController::class, 'showChallenge'])->name('mfa.challenge');
Route::post('/mfa/challenge', [MfaController::class, 'verifyChallenge'])->name('mfa.challenge.verify');

// Training docs
Route::get('/training/admin-manual', [\App\Http\Controllers\Web\TrainingDocController::class, 'admin'])->name('training.admin');

// Image optimization utility
Route::post('/__admin/optimize-images', function () {
    // ...
})->middleware('auth');