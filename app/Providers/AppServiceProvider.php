<?php

namespace App\Providers;

use App\Models\MediaAsset;
use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\CountyTourismAttraction;
use App\Models\CountyHotel;
use App\Models\CountyFarm;
use App\Models\CountyTransport;
use App\Models\CountyHealthFacility;
use App\Models\CountyCultureSite;
use App\Models\CountyProduct;
use App\Models\Exhibition;
use App\Models\Venue;
use App\Models\Ministry;
use App\Models\Agency;
use App\Models\Marketplace\Product;
use App\Observers\MediaAssetObserver;
use App\Services\SendgridApiTransport;
use App\View\Components\DashboardsShell;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\TileMediaResolver::class);
    }

    public function boot(): void
    {
        Blade::component('dashboards-shell', DashboardsShell::class);

        // The literal approved renderer reads these same models. Upload/delete
        // events invalidate its projection immediately, not after a browser refresh TTL.
        $referenceBust = fn () => \Illuminate\Support\Facades\Cache::forget('reference.native.v1');
        foreach ([County::class, CountyInstitution::class, Product::class, Venue::class, Exhibition::class, MediaAsset::class, \App\Models\MediaDerivative::class, \App\Models\Screen::class, \App\Models\LiveStream::class, \App\Models\Travel\Airport::class, \App\Models\Travel\FlightInventory::class, \App\Models\Travel\Hotel::class, \App\Models\Travel\HotelRoom::class, \App\Models\Travel\AirportTransfer::class] as $model) {
            $model::saved($referenceBust);
            $model::deleted($referenceBust);
        }

        // Every uploaded video automatically gets adaptive HLS streaming
        MediaAsset::observe(MediaAssetObserver::class);

        // Bust public cache whenever key admin data changes — changes visible within 60s
        $bust = fn () => bust_cache();
        County::saved($bust);
        County::deleted($bust);
        CountyInstitution::saved($bust);
        CountyInstitution::deleted($bust);
        Product::saved($bust);
        Product::deleted($bust);

        // Bust media fallback cache when assets change
        $mediaBust = fn ($entity) => bust_cache();
        MediaAsset::saved($mediaBust);
        MediaAsset::deleted($mediaBust);
        CountyTourismAttraction::saved($bust);
        CountyTourismAttraction::deleted($bust);
        CountyHotel::saved($bust);
        CountyHotel::deleted($bust);
        CountyFarm::saved($bust);
        CountyFarm::deleted($bust);
        CountyTransport::saved($bust);
        CountyTransport::deleted($bust);
        CountyHealthFacility::saved($bust);
        CountyHealthFacility::deleted($bust);
        CountyCultureSite::saved($bust);
        CountyCultureSite::deleted($bust);
        CountyProduct::saved($bust);
        CountyProduct::deleted($bust);
        Exhibition::saved($bust);
        Exhibition::deleted($bust);
        Venue::saved($bust);
        Venue::deleted($bust);
        Ministry::saved($bust);
        Ministry::deleted($bust);
        Agency::saved($bust);
        Agency::deleted($bust);

        // Laravel Pulse dashboard — only the KICC superadmin may view it.
        Gate::define('viewPulse', function ($user) {
            return ($user->account_type ?? '') === 'superadmin'
                || $user->email === 'admin@kicc.go.ke';
        });

        // Behind the Cloudflare edge: always generate https URLs (origin speaks HTTP).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Must live here, NOT in routes/api.php — with route:cache the route files
        // are never loaded, so a limiter defined there would be undefined at runtime.
        // 300/min/IP: most public reads are served from the Redis response cache,
        // so this is per-user protection, not a throughput gate.
        RateLimiter::for('api', function () {
            return Limit::perMinute(300)->by(optional(request()->user())->id ?: request()->ip());
        });

        // SendGrid API mailer (uses HTTP API, not SMTP — works on port 443)
        // Uses config() not env() because config is cached in production
        if (config('mail.mailers.sendgrid.key')) {
            Mail::extend('sendgrid', function (array $config) {
                return new SendgridApiTransport($config['key']);
            });
        }
    }
}
