<?php

namespace App\Providers;

use App\Services\SendgridApiTransport;
use GuzzleHttp\Client;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Blade::component('dashboards-shell', \App\View\Components\DashboardsShell::class);

        // Behind the Cloudflare edge: always generate https URLs (origin speaks HTTP).
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Must live here, NOT in routes/api.php — with route:cache the route files
        // are never loaded, so a limiter defined there would be undefined at runtime.
        RateLimiter::for('api', function () {
            return Limit::perMinute(60)->by(optional(request()->user())->id ?: request()->ip());
        });

        // SendGrid API mailer (uses HTTP API, not SMTP — works on port 443)
        if (env('SENDGRID_API_KEY')) {
            Mail::extend('sendgrid', function (array $config) {
                return new \App\Services\SendgridApiTransport($config['key']);
            });
        }
    }
}
