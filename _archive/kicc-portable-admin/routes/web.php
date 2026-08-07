<?php

use Illuminate\Support\Facades\Route;

// ─── Admin Auth ─────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->hasRole('kicc_admin')) return redirect('/kicc-admin');
        if ($user->hasRole('national_admin')) return redirect('/national-admin');
        if ($user->hasRole('county_admin')) {
            $slug = session('admin_county_slug', 'kilifi');
            return redirect("/county-admin/{$slug}/pro");
        }
    }
    return redirect('/login');
});
Route::get('/login', [App\Http\Controllers\Web\AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [App\Http\Controllers\Web\AuthController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Web\AuthController::class, 'logout'])->name('logout');

    // Portal
    Route::get('/portal', [App\Http\Controllers\Web\AdminPortalController::class, 'selector'])->name('admin.portal');

    // National Government Admin
    Route::get('/admin/national', [App\Http\Controllers\Web\AdminPortalController::class, 'national'])->name('admin.national');

    // County Admin
    Route::get('/admin/county', [App\Http\Controllers\Web\AdminPortalController::class, 'county'])->name('admin.county');
    Route::get('/dashboard/county', [App\Http\Controllers\Web\DashboardV2Controller::class, 'county'])->name('dashboard.county');

    // KICC Legacy Dashboard
    Route::get('/dashboard/admin', [App\Http\Controllers\Web\AdminDashboardController::class, 'index'])->name('dashboard.admin');
    Route::post('/dashboard/admin/delete-product/{id}', [App\Http\Controllers\Web\AdminDashboardController::class, 'deleteProduct'])->name('admin.delete-product');
    Route::post('/dashboard/admin/delete-user/{id}', [App\Http\Controllers\Web\AdminDashboardController::class, 'deleteUser'])->name('admin.delete-user');
    Route::post('/dashboard/admin/delete-order/{id}', [App\Http\Controllers\Web\AdminDashboardController::class, 'deleteOrder'])->name('admin.delete-order');

    // KICC Super Admin
    Route::get('/kicc-admin', [App\Http\Controllers\Web\KiccAdminController::class, 'index'])->name('kicc.admin');
    Route::post('/kicc-admin/escrow/{id}/release', [App\Http\Controllers\Web\KiccAdminController::class, 'releaseEscrow'])->name('kicc.escrow.release');
    Route::post('/kicc-admin/approve/{table}/{id}', [App\Http\Controllers\Web\KiccAdminController::class, 'approve'])->name('kicc.approve');
    Route::post('/kicc-admin/sync', [App\Http\Controllers\Web\KiccAdminController::class, 'sync'])->name('kicc.sync');
    Route::post('/kicc-admin/sync/county', [App\Http\Controllers\Web\KiccAdminController::class, 'syncCounty'])->name('kicc.sync.county');

    // National Admin
    Route::get('/national-admin', [App\Http\Controllers\Web\NationalPortalController::class, 'index'])->name('national.admin');
    Route::get('/national-admin/{slug}', [App\Http\Controllers\Web\NationalSiteController::class, 'show'])->name('national.site');

    // County Admin (professional)
    Route::get('/county-admin', [App\Http\Controllers\Web\CountyPortalController::class, 'index'])->name('county.admin');
    Route::get('/county-admin/{slug}/pro', [App\Http\Controllers\Web\CountyAdminController::class, 'proDashboard'])->name('county.admin.pro');
    Route::post('/county-admin/{slug}/content', [App\Http\Controllers\Web\CountyAdminController::class, 'updateContent'])->name('county.admin.content');
    Route::post('/county-admin/{slug}/image', [App\Http\Controllers\Web\CountyAdminController::class, 'uploadImage'])->name('county.admin.image');
    Route::post('/county-admin/{slug}/entity', [App\Http\Controllers\Web\CountyAdminController::class, 'addEntity'])->name('county.admin.entity');
    Route::post('/county-admin/{slug}/sector/link', [App\Http\Controllers\Web\CountyAdminController::class, 'linkSector'])->name('county.admin.sector.link');
    Route::post('/county-admin/{slug}/sector/{sectorId}/unlink', [App\Http\Controllers\Web\CountyAdminController::class, 'unlinkSector'])->name('county.admin.sector.unlink');
    Route::delete('/county-admin/{slug}/entity/{id}', [App\Http\Controllers\Web\CountyAdminController::class, 'deleteEntity'])->name('county.admin.entity.delete');
    // County data CRUD
    foreach (['attraction','hotel','farm','health','institution','transport','culture','product'] as $type) {
        Route::post("/county-admin/{slug}/{$type}", [App\Http\Controllers\Web\CountyAdminController::class, "add{$type}"])->name("county.admin.{$type}");
        Route::delete("/county-admin/{slug}/{$type}/{id}", [App\Http\Controllers\Web\CountyAdminController::class, "delete{$type}"])->name("county.admin.{$type}.delete");
    }

    // County profile, images, weather
    Route::post('/county-admin/{slug}/profile', [App\Http\Controllers\Web\CountyAdminController::class, 'updateProfile'])->name('county.admin.profile');
    Route::delete('/county-admin/{slug}/image/{id}', [App\Http\Controllers\Web\CountyAdminController::class, 'deleteImage'])->name('county.admin.image.delete');
    Route::post('/county-admin/{slug}/weather', [App\Http\Controllers\Web\CountyAdminController::class, 'addWeather'])->name('county.admin.weather');

    // Exhibitor / Provider
    Route::get('/exhibitor-admin', [App\Http\Controllers\Web\ExhibitorPortalController::class, 'index'])->name('exhibitor.admin');
    Route::get('/exhibitor-admin/products', [App\Http\Controllers\Web\ExhibitorPortalController::class, 'products'])->name('exhibitor.products');
    Route::get('/exhibitor-onboarding', [App\Http\Controllers\Web\ExhibitorOnboardingController::class, 'index'])->name('exhibitor.onboarding');
    Route::get('/provider-admin', [App\Http\Controllers\Web\ProviderPortalController::class, 'index'])->name('provider.admin');
    Route::post('/provider-admin/price', [App\Http\Controllers\Web\ProviderPortalController::class, 'updatePrice'])->name('provider.admin.price');
    Route::post('/provider-admin/add', [App\Http\Controllers\Web\ProviderPortalController::class, 'add'])->name('provider.admin.add');

    // ─── Stub routes for views referencing public pages ─────────────
    $stubs = [
        'counties.index' => '/counties', 'counties.show' => '/counties/{county}',
        'marketplace.index' => '/marketplace', 'marketplace.show' => '/marketplace/{slug}',
        'exhibitions.index' => '/exhibitions', 'exhibitions.show' => '/exhibitions/{slug}',
        'venues.index' => '/venues', 'venues.show' => '/venues/{venue}',
        'screens.directory' => '/screens', 'screens.show' => '/screens/{id}',
        'travel.index' => '/travel', 'operations.index' => '/operations',
        'room3d.index' => '/room3d', 'exhibitor.site' => '/exhibitor/{slug}',
        'subscriptions.index' => '/subscriptions',
        'exhibition-3d.map' => '/exhibition-3d/map',
        'exhibition-3d.sector' => '/exhibition-3d/sector',
        'exhibition-3d.booth' => '/exhibition-3d/booth',
        'packages.index' => '/packages', 'cart.index' => '/cart',
        'dashboard.index' => '/dashboard', 'dashboard.bookings' => '/dashboard/bookings',
        'dashboard.exhibitions' => '/dashboard/exhibitions',
        'dashboard.profile.update' => '/dashboard/profile',
        'home' => '/', 'admin.portal' => '/portal',
    ];
    foreach ($stubs as $name => $path) {
        Route::get($path, fn() => redirect('/admin'))->name($name);
    }

    // Stub POST routes for county admin
    Route::post('/county-admin/{slug}/entity/delete/{id}', fn() => redirect('/admin/county'))->name('county.admin.entity.delete');
    Route::post('/county-admin/{slug}/image/delete/{id}', fn() => redirect('/admin/county'))->name('county.admin.image.delete');
    Route::post('/county-admin/{slug}/image/upload', fn() => redirect('/admin/county'))->name('county.admin.image.upload');
    Route::post('/county-admin/{slug}/package', fn() => redirect('/admin/county'))->name('county.admin.package');
    Route::get('/county-admin/{slug}/details', fn() => redirect('/admin/county'))->name('county.admin.details');
    Route::get('/county-admin/{slug}/ads', fn() => redirect('/admin/county'))->name('county.admin.ads');
    Route::get('/county-admin/{slug}/report', fn() => redirect('/admin/county'))->name('county.admin.report');
    Route::post('/exhibitor-admin/products/delete/{id}', fn() => redirect('/exhibitor-admin'))->name('exhibitor.admin.products.delete');
    Route::post('/exhibitor-admin/products/store', fn() => redirect('/exhibitor-admin'))->name('exhibitor.admin.products.store');
});

// ─── Admin Sections ───
Route::middleware('auth')->group(function () {
    Route::get('/admin/sections', [App\Http\Controllers\Web\SectionsAdminController::class, 'index'])->name('admin.sections');
    Route::post('/admin/exhibitions', [App\Http\Controllers\Web\SectionsAdminController::class, 'storeExhibition'])->name('admin.exhibitions.store');
    Route::delete('/admin/exhibitions/{id}', [App\Http\Controllers\Web\SectionsAdminController::class, 'deleteExhibition'])->name('admin.exhibitions.delete');
    Route::post('/admin/venues', [App\Http\Controllers\Web\SectionsAdminController::class, 'storeVenue'])->name('admin.venues.store');
    Route::delete('/admin/venues/{id}', [App\Http\Controllers\Web\SectionsAdminController::class, 'deleteVenue'])->name('admin.venues.delete');
    Route::post('/admin/booths', [App\Http\Controllers\Web\SectionsAdminController::class, 'storeBooth'])->name('admin.booths.store');
    Route::delete('/admin/booths/{id}', [App\Http\Controllers\Web\SectionsAdminController::class, 'deleteBooth'])->name('admin.booths.delete');
    Route::post('/admin/screens', [App\Http\Controllers\Web\SectionsAdminController::class, 'storeScreen'])->name('admin.screens.store');
    Route::delete('/admin/screens/{id}', [App\Http\Controllers\Web\SectionsAdminController::class, 'deleteScreen'])->name('admin.screens.delete');
    Route::post('/admin/plans', [App\Http\Controllers\Web\SectionsAdminController::class, 'storeSubscriptionPlan'])->name('admin.plans.store');
    Route::delete('/admin/plans/{id}', [App\Http\Controllers\Web\SectionsAdminController::class, 'deleteSubscriptionPlan'])->name('admin.plans.delete');
    Route::post('/admin/users', [App\Http\Controllers\Web\SectionsAdminController::class, 'storeUser'])->name('admin.users.store');
    Route::delete('/admin/users/{id}', [App\Http\Controllers\Web\SectionsAdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::post('/admin/sync/pull', [App\Http\Controllers\Web\SectionsAdminController::class, 'syncPull'])->name('admin.sync.pull');
    Route::post('/admin/sync/push', [App\Http\Controllers\Web\SectionsAdminController::class, 'syncPush'])->name('admin.sync.push');
    Route::post('/admin/import', [App\Http\Controllers\Web\SectionsAdminController::class, 'importData'])->name('admin.import');
});

Route::prefix('api')->group(function () {
    Route::get('/county-data/overview', [App\Http\Controllers\Api\CountyDataController::class, 'overview']);
    Route::get('/county-data/counties', [App\Http\Controllers\Api\CountyDataController::class, 'counties']);
    Route::get('/county-data/county/{slug}', [App\Http\Controllers\Api\CountyDataController::class, 'countyDetail']);
    Route::get('/county-data/rankings', [App\Http\Controllers\Api\CountyDataController::class, 'rankings']);
    Route::get('/county-data/sectors', [App\Http\Controllers\Api\CountyDataController::class, 'sectors']);
});

// Sync API (unauthenticated — uses HMAC key verification instead)
Route::prefix('api/sync')->group(function () {
    Route::post('/push', [App\Http\Controllers\Api\SyncController::class, 'push']);
    Route::post('/pull', [App\Http\Controllers\Api\SyncController::class, 'pull']);
    Route::post('/revoke-key', [App\Http\Controllers\Api\SyncController::class, 'revokeKey']);
});

// Data Export
Route::middleware('auth')->prefix('export')->group(function () {
    Route::get('/county/{slug}', [App\Http\Controllers\Api\DataExportController::class, 'exportCounty']);
    Route::get('/county/{slug}/csv', fn($s) => app(App\Http\Controllers\Api\DataExportController::class)->exportCounty($s, 'csv'));
    Route::get('/all', [App\Http\Controllers\Api\DataExportController::class, 'exportAll']);
    Route::get('/summary', [App\Http\Controllers\Api\DataExportController::class, 'exportSummary']);
});

Route::middleware('auth')->get('/ceo-dashboard', function () {
    return \Inertia\Inertia::render('app/ceo-dashboard');
})->name('ceo.dashboard');

// Filament panel routes are auto-registered by AdminPanelProvider