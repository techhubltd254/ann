<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Legacy records-admin surface (ported from the kicc-v2 branch).
            // Kept in its own file so it can never disturb routes/web.php.
            Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/admin_records.php'));
            require base_path('routes/admin_console.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Origin is only reachable through the Cloudflare edge worker — trust the
        // proxy headers it sets (X-Forwarded-Host/Proto) so generated URLs use
        // the public https://kicctest.org host, not the internal origin host.
        // The firewall (UFW) only permits 80/443 from Cloudflare's published
        // ranges, so we restrict trusted proxies to those same CIDRs rather
        // than trusting any source (defense in depth).
        $middleware->trustProxies(at: [
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
            '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
            '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\OptimizeUploadedImages::class);
        $middleware->web(prepend: [
            \App\Http\Middleware\SearchIntent::class,
            \App\Http\Middleware\LoginGate::class,
            \App\Http\Middleware\CachePublicResponse::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\ProtectPrivateRevenue::class,
            \App\Http\Middleware\EnforceAdminHierarchy::class,
            \App\Http\Middleware\AppendAuditContext::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            'throttle:60,1',
        ]);
        $middleware->priority([
            \App\Http\Middleware\CachePublicResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);
        $middleware->api(prepend: [
            \App\Http\Middleware\ProtectPrivateRevenue::class,
            \App\Http\Middleware\AgenticSEO::class,
            'throttle:api',
            \App\Http\Middleware\ThrottleApi::class,
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'county.scope' => \App\Http\Middleware\EnsureCountyScope::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'throttle.api' => \App\Http\Middleware\ThrottleApi::class,
            'ip.whitelist' => \App\Http\Middleware\IpWhitelistAdmin::class,
            'exhibitor' => \App\Http\Middleware\ExhibitorMiddleware::class,
        ]);

        // When an authenticated user hits a guest-only page (login/register),
        // send them to their correct admin dashboard instead of `/`.
        \Illuminate\Auth\Middleware\RedirectIfAuthenticated::redirectUsing(
            fn (Request $request) => app(\App\Services\Auth\LoginRedirectService::class)
                ->redirect($request->user())
                ->getTargetUrl()
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'portal/uploads/*', 'portal/media-flow/*', 'admin/uploads/*', 'admin/media-flow/*') || $request->expectsJson(),
        );
        // Breadcrumb on every 500 for production debugging
        $exceptions->reportable(function (\Throwable $e) {
            if (app()->isProduction()) {
                \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_ERROR,
                    \Sentry\Breadcrumb::TYPE_HTTP,
                    '500',
                    'Internal Server Error: ' . $e->getMessage(),
                    ['file' => $e->getFile(), 'line' => $e->getLine()]
                ));
            }
        });
    })->create();
