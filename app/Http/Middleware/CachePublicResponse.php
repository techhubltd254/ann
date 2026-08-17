<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CachePublicResponse
{
    /**
     * Cache full GET/HEAD responses for anonymous visitors to cut
     * per-request DB + render cost on heavy public pages and read-only
     * API endpoints. Responses are cached in Redis keyed by method+path.
     *
     * - Only GET/HEAD requests are cached.
     * - Requests with an existing session cookie (logged-in/sessioned
     *   users) pass straight through so their data is never served stale.
     * - Cached responses never carry a Set-Cookie header; anonymous
     *   visitors simply don't get a session cookie, which is fine for
     *   public catalog browsing (forms/auth still hit the live path).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        if ($request->user() || $request->cookies->has(session()->getName())) {
            return $next($request);
        }

        $ttl = (int) config('response_cache.ttl', 60);
        $key = 'resp:' . sha1($request->method() . '|' . $request->fullUrl());

        $cached = Cache::get($key);
        if ($cached !== null) {
            $response = new Response($cached['content'] ?? '', $cached['status'] ?? 200);
            $response->headers->set('Content-Type', $cached['content_type'] ?? 'text/html; charset=UTF-8');
            $response->headers->set('X-Cache', 'HIT');

            return $response;
        }

        $response = $next($request);

        if ($this->cacheable($response)) {
            try {
                Cache::put($key, [
                    'content' => $response->getContent(),
                    'status' => $response->getStatusCode(),
                    'content_type' => $response->headers->get('Content-Type') ?? 'text/html; charset=UTF-8',
                ], $ttl);
                $response->headers->set('X-Cache', 'MISS');
            } catch (\Throwable $e) {
                Log::warning('response_cache_store_failed: ' . $e->getMessage());
            }
        }

        return $response;
    }

    protected function cacheable(Response $response): bool
    {
        if (! $response->isSuccessful()) {
            return false;
        }

        if ($response->headers->has('Set-Cookie')) {
            $response->headers->remove('Set-Cookie');
        }

        return true;
    }
}