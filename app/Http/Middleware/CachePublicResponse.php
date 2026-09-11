<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CachePublicResponse
{
    private array $cacheablePaths = [
        '/national-government',
        '/national-exhibition',
        '/counties',
        '/marketplace',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->method() !== 'GET') {
            return $response;
        }

        $path = $request->path();

        // Default: short cache for dynamic content
        $cacheSecs = 60; // 1 minute default

        // Long cache for static-like pages
        foreach ($this->cacheablePaths as $prefix) {
            if (str_starts_with($path, ltrim($prefix, '/'))) {
                $cacheSecs = 600; // 10 minutes for national-gov, counties, etc.
                break;
            }
        }

        // API responses
        if (str_starts_with($path, 'api/live')) {
            $cacheSecs = 30; // 30 seconds for live API
        }

        // Don't cache auth or mutation endpoints
        if (str_starts_with($path, 'login') || str_starts_with($path, 'register') ||
            str_starts_with($path, 'cart') || str_starts_with($path, 'checkout') ||
            str_starts_with($path, 'kicc-live/admin') || str_starts_with($path, 'broadcast')) {
            return $response;
        }

        if ($response->isSuccessful()) {
            $response->headers->set('Cache-Control', "public, s-maxage={$cacheSecs}, stale-while-revalidate=" . ($cacheSecs * 10));
            $response->headers->set('CDN-Cache-Control', "max-age={$cacheSecs}");
        }

        return $response;
    }
}