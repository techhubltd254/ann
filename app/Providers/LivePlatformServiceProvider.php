<?php
namespace App\Providers;

use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\HeartbeatLog;
use App\Models\ExhibitorStudioSession;
use App\Models\MeetingBooking;
use App\Models\FavouriteBooth;
use App\Services\HeartbeatService;
use App\Services\SignedUrlService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;

class LivePlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HeartbeatService::class, fn() => new HeartbeatService());
        $this->app->singleton(SignedUrlService::class, fn() => new SignedUrlService());
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->registerGates();
    }

    protected function loadRoutes(): void
    {
        // API routes — rate-limited, authenticated
        Route::prefix('api/live')
            ->name('api.live.')
            ->middleware(['throttle.api', 'security.headers'])
            ->group(function () {
                // Public heartbeat — no auth required (exhibitor pings from encoder)
                Route::post('/heartbeat', [\App\Http\Controllers\Live\HeartbeatController::class, 'ping'])->name('heartbeat');

                // Authenticated endpoints
                Route::middleware('auth:sanctum')->group(function () {
                    Route::post('/meeting-book', [\App\Http\Controllers\Live\HeartbeatController::class, 'bookMeeting'])->name('book-meeting');
                    Route::post('/favourite', [\App\Http\Controllers\Live\HeartbeatController::class, 'toggleFavourite'])->name('favourite');
                });

                // Public read
                Route::get('/booths/active', [\App\Http\Controllers\Live\HeartbeatController::class, 'activeBooths'])->name('active-booths');
            });

        // Super Admin — IP restricted, MFA, security headers
        Route::middleware(['web', 'auth', 'verified', 'ip.whitelist', 'admin:kicc', 'security.headers'])
            ->prefix('kicc-live/admin')
            ->name('live.admin.')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Live\LiveAdminController::class, 'dashboard'])->name('dashboard');
                Route::get('/booths', [\App\Http\Controllers\Live\LiveAdminController::class, 'booths'])->name('booths');
                Route::post('/booths/{booth}/authorize', [\App\Http\Controllers\Live\LiveAdminController::class, 'authorize'])->name('authorize');
                Route::post('/booths/{booth}/terminate', [\App\Http\Controllers\Live\LiveAdminController::class, 'terminate'])->name('terminate');
                Route::post('/booths/{booth}/api-key', [\App\Http\Controllers\Live\LiveAdminController::class, 'generateApiKey'])->name('api-key');
                Route::get('/monitor', [\App\Http\Controllers\Live\LiveAdminController::class, 'monitor'])->name('monitor');
                Route::get('/analytics', [\App\Http\Controllers\Live\LiveAdminController::class, 'analytics'])->name('analytics');
                Route::get('/health', [\App\Http\Controllers\Live\LiveAdminController::class, 'systemHealth'])->name('health');
            });

        // Exhibitor studio — auth + exhibitor middleware + security headers
        Route::middleware(['web', 'auth', 'verified', 'exhibitor', 'security.headers'])
            ->prefix('kicc-live/studio')
            ->name('live.studio.')
            ->group(function () {
                Route::get('/{booth}', [\App\Http\Controllers\Live\ExhibitorStudioController::class, 'index'])->name('index');
                Route::post('/{booth}/go-live', [\App\Http\Controllers\Live\ExhibitorStudioController::class, 'goLive'])->name('go-live');
                Route::post('/{booth}/end-stream', [\App\Http\Controllers\Live\ExhibitorStudioController::class, 'endStream'])->name('end-stream');
                Route::post('/{booth}/update-booth', [\App\Http\Controllers\Live\ExhibitorStudioController::class, 'updateBooth'])->name('update-booth');
                Route::get('/{booth}/signed-url', [\App\Http\Controllers\Live\ExhibitorStudioController::class, 'signedStreamUrl'])->name('signed-url');
            });

        // Booth management
        Route::middleware(['web', 'auth', 'verified', 'security.headers'])
            ->prefix('kicc-live/booths')
            ->name('live.booths.')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Live\BoothController::class, 'index'])->name('index');
                Route::get('/{booth}', [\App\Http\Controllers\Live\BoothController::class, 'show'])->name('show');
                Route::middleware('admin:kicc')->post('/', [\App\Http\Controllers\Live\BoothController::class, 'store'])->name('store');
            });

        // Screen Broadcast Control — videographer panel
Route::middleware(['web', 'auth', 'verified', 'security.headers'])
    ->prefix('broadcast')
    ->name('live.screens.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\Live\ScreenBroadcastController::class, 'index'])->name('broadcast');
        Route::post('/route', [\App\Http\Controllers\Live\ScreenBroadcastController::class, 'routeToScreen'])->name('route');
        Route::post('/route-all', [\App\Http\Controllers\Live\ScreenBroadcastController::class, 'routeToAll'])->name('route-all');
        Route::post('/route-venue', [\App\Http\Controllers\Live\ScreenBroadcastController::class, 'routeToScreenByName'])->name('route-venue');
    });
        Route::middleware(['web', 'security.headers'])
            ->group(function () {
                Route::get('/national-exhibition', [\App\Http\Controllers\Live\NationalExhibitionController::class, 'index'])->name('national.index');
                Route::get('/national-exhibition/{slug}', [\App\Http\Controllers\Live\NationalExhibitionController::class, 'show'])->name('national.show');
                Route::get('/live/{booth}', [\App\Http\Controllers\Live\LiveViewController::class, 'show'])->name('live.show');
            });
    }

    protected function registerGates(): void
    {
        Gate::define('manage-live-platform', fn($user) => $user->hasRole(['kicc_admin', 'superadmin']));
        Gate::define('operate-studio', fn($user, $booth) =>
            $user->id === $booth->user_id || $user->hasRole('exhibitor')
        );
        Gate::define('admin-ip-whitelist', fn($user) => $user->hasRole(['kicc_admin', 'superadmin']));
    }
}