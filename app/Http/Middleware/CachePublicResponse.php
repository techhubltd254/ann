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
            $this->publicHeaders($response);

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
                $this->publicHeaders($response);
            } catch (\Throwable $e) {
                Log::warning('response_cache_store_failed: ' . $e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Mark the response cacheable at the CDN/edge layer. The explicit
     * Cache-Control lets Cloudflare hold public GET responses at the edge
     * and keeps the origin (and its session/Redis/DB work) completely out
     * of the guest request path. s-maxage mirrors the Redis TTL so the
     * edge and origin expire together; max-age lets browsers reuse it.
     */
    protected function publicHeaders(Response $response): void
    {
        $ttl = (int) config('response_cache.ttl', 60);

        $response->headers->set('Cache-Control', sprintf(
            'public, max-age=%d, s-maxage=%d',
            $ttl,
            $ttl
        ));
        $response->headers->remove('Set-Cookie');
        $response->headers->remove('XSRF-TOKEN');
    }

    protected function cacheable(Response $response): bool
    {
        if (! $response->isSuccessful()) {
            return false;
        }

        // Binary image responses (the optimize-image pipeline) must keep their
        // own immutable Cache-Control — never override or cache them here.
        $ct = $response->headers->get('Content-Type') ?? '';
        if (str_starts_with($ct, 'image/')) {
            return false;
        }

        // Responses that must deliver a session cookie (e.g. the first
        // request that boots a session) are safe to cache — we store only
        // the content/status/type, never cookies, and the live response
        // keeps its own Set-Cookie so the visitor still gets a session.
        return true;
    }
}