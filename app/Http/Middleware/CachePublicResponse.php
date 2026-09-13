<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicResponse
{
    private array $cacheablePrefixes = [
        '/national-government',
        '/national-exhibition',
        '/counties',
        '/marketplace',
        '/exhibitions',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        return $this->setCacheHeaders($request, $response);
    }

    private function setCacheHeaders(Request $request, Response $response): Response
    {
        if ($request->method() !== 'GET') {
            return $response;
        }

        $path = '/' . $request->path();

        // Skip media proxy routes — binary streaming responses
        if (str_starts_with($path, '/media/')) {
            return $response;
        }

        // Skip non-HTML responses (JSON, binary, etc.)
        $contentType = $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        // Never cache auth, admin, or mutation paths
        foreach (['/login','/register','/cart','/checkout','/kicc-live/admin','/broadcast','/api','/live'] as $no) {
            if (str_starts_with($path, $no)) {
                return $response;
            }
        }

        if (!$response->isSuccessful()) {
            return $response;
        }

        $cacheSecs = 60; // default 1 min
        foreach ($this->cacheablePrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $cacheSecs = 600; // 10 min for public pages
                break;
            }
        }

        // Forcefully set cache headers — this must override any middleware that ran after us
        $response->headers->remove('Cache-Control');
        $response->headers->remove('Pragma');
        $response->headers->remove('Expires');

        $response->headers->set('Cache-Control', "public, s-maxage={$cacheSecs}, max-age={$cacheSecs}, stale-while-revalidate=" . ($cacheSecs * 10));
        $response->headers->set('CDN-Cache-Control', "max-age={$cacheSecs}");
        $response->headers->set('X-Kicc-Cache', "s-maxage={$cacheSecs}");

        return $response;
    }
}