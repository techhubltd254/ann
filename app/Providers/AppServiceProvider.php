<?php

namespace App\Providers;

use App\Models\MediaAsset;
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
        //
    }

    public function boot(): void
    {
        Blade::component('dashboards-shell', DashboardsShell::class);

        // Every uploaded video automatically gets adaptive HLS streaming
        MediaAsset::observe(MediaAssetObserver::class);

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
