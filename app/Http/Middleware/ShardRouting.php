<?php

namespace App\Http\Middleware;

use App\Services\ShardManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * ShardRouting — automatically routes read/write traffic to the correct shard
 * based on the request's county context.
 *
 * For web requests with a county slug in the URL, sets the default connection
 * to that county's shard for the duration of the request.
 *
 * For API requests, reads the X-Shard-Key header or X-County-Slug header.
 */
class ShardRouting
{
    public function handle(Request $request, Closure $next, string $operation = 'auto'): Response
    {
        $shardManager = app(ShardManager::class);
        $countySlug = $this->resolveCountySlug($request);

        if ($countySlug) {
            $connection = $shardManager->connectionForSlug($countySlug);
            DB::setDefaultConnection($connection);
        }

        $response = $next($request);

        // Reset default connection
        DB::setDefaultConnection('mysql');

        return $response;
    }

    private function resolveCountySlug(Request $request): ?string
    {
        // 1. Header override (API clients)
        if ($slug = $request->header('X-County-Slug')) {
            return $slug;
        }

        // 2. Route parameter
        if ($slug = $request->route('county')) {
            return $slug;
        }

        // 3. County slug in URL path
        $path = $request->path();
        if (preg_match('#^counties/([a-z-]+)#', $path, $m)) {
            return $m[1];
        }

        // 4. Authenticated user's county
        if ($request->user() && $request->user()->county) {
            return $request->user()->county->slug;
        }

        return null;
    }
}
