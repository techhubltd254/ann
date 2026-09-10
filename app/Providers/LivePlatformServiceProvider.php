<?php
namespace App\Providers;

use App\Models\Booth;
use App\Models\BoothAuthorization;
use App\Models\HeartbeatLog;
use App\Models\ExhibitorStudioSession;
use App\Models\MeetingBooking;
use App\Models\FavouriteBooth;
use App\Services\HeartbeatService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;

class LivePlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HeartbeatService::class, fn() => new HeartbeatService());
    }

    public function boot(): void
    {
        $this->loadRoutes();
        $this->registerGates();
    }

    protected function loadRoutes(): void
    {
        Route::middleware('web')
            ->group(function () {
                Route::middleware(['auth', 'verified'])
                    ->prefix('kicc-live')
                    ->name('live.')
                    ->group(function () {
                        // Super Admin routes
                        Route::middleware('admin:kicc')
                            ->prefix('admin')
                            ->name('admin.')
                            ->group(function () {
                                Route::get('/', [\App\Http\Controllers\Live\LiveAdminController::class, 'dashboard'])->name('dashboard');
                                Route::get('/booths', [\App\Http\Controllers\Live\LiveAdminController::class, 'booths'])->name('booths');
                                Route::post('/booths/{booth}/authorize', [\App\Http\Controllers\Live\LiveAdminController::class, 'authorize'])->name('authorize');
                                Route::post('/booths/{booth}/terminate', [\App\Http\Controllers\Live\LiveAdminController::class, 'terminate'])->name('terminate');
                                Route::post('/booths/{booth}/api-key', [\App\Http\Controllers\Live\LiveAdminController::class, 'generateApiKey'])->name('api-key');
                                Route::get('/monitor', [\App\Http\Controllers\Live\LiveAdminController::class, 'monitor'])->name('monitor');
                                Route::get('/analytics', [\App\Http\Controllers\Live\LiveAdminController::class, 'analytics'])->name('analytics');
                            });

                        // Exhibitor routes
                        Route::middleware('role:exhibitor')
                            ->prefix('studio')
                            ->name('studio.')
                            ->group(function () {
                                Route::get('/{booth}', [\App\Http\Controllers\Live\ExhibitorStudioController::class, 'index'])->name('index');
                                Route::post('/{booth}/go-live', [\App\Http\Controllers\Live\ExhibitorStudioController::class, 'goLive'])->name('go-live');
                                Route::post('/{booth}/end-stream', [\App\Http\Controllers\Live\ExhibitorStudioController::class, 'endStream'])->name('end-stream');
                                Route::post('/{booth}/update-booth', [\App\Http\Controllers\Live\ExhibitorStudioController::class, 'updateBooth'])->name('update-booth');
                            });

                        // Booth management routes
                        Route::prefix('booths')->name('booths.')
                            ->group(function () {
                                Route::get('/', [\App\Http\Controllers\Live\BoothController::class, 'index'])->name('index');
                                Route::get('/{booth}', [\App\Http\Controllers\Live\BoothController::class, 'show'])->name('show');
                                Route::post('/', [\App\Http\Controllers\Live\BoothController::class, 'store'])->name('store');
                            });
                    });
            });

        // Public live routes (viewer-facing)
        Route::middleware('web')
            ->group(function () {
                // National Exhibition routes
                Route::get('/national-exhibition', [\App\Http\Controllers\Live\NationalExhibitionController::class, 'index'])->name('national.index');
                Route::get('/national-exhibition/{slug}', [\App\Http\Controllers\Live\NationalExhibitionController::class, 'show'])->name('national.show');

                // Booth viewer routes
                Route::prefix('live')->name('live.')->group(function () {
                    Route::get('/{booth}', [\App\Http\Controllers\Live\LiveViewController::class, 'show'])->name('show');
                    Route::get('/{booth}/chat', [\App\Http\Controllers\Live\LiveViewController::class, 'chat'])->name('chat');
                });
            });

        // API routes for heartbeat + data
        Route::prefix('api/live')
            ->name('api.live.')
            ->group(function () {
                Route::post('/heartbeat', [\App\Http\Controllers\Live\HeartbeatController::class, 'ping'])->name('heartbeat');
                Route::post('/meeting-book', [\App\Http\Controllers\Live\HeartbeatController::class, 'bookMeeting'])->name('book-meeting');
                Route::post('/favourite', [\App\Http\Controllers\Live\HeartbeatController::class, 'toggleFavourite'])->name('favourite');
                Route::get('/booths/active', [\App\Http\Controllers\Live\HeartbeatController::class, 'activeBooths'])->name('active-booths');
            });
    }

    protected function registerGates(): void
    {
        Gate::define('manage-live-platform', fn($user) => $user->hasRole(['kicc_admin', 'superadmin']));
        Gate::define('operate-studio', fn($user, $booth) =>
            $user->id === $booth->user_id || $user->hasRole('exhibitor')
        );
    }
}