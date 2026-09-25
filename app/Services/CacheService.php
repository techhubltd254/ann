<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CacheService — centralized Redis caching for the entire platform.
 *
 * Every cacheable data point uses a consistent TTL hierarchy:
 *   Public pages  (counties, marketplace)  → 1 hour (3600s)
 *   API responses (pipeline, escrow)       → 5 minutes (300s)
 *   Session data  (user, cart)             → Redis native (no TTL needed)
 *   Hot data      (pool balance, stats)     → 30 seconds (30s)
 *   Cold data     (pipeline graph)          → 24 hours (86400s)
 *
 * All caches are busted automatically when source data changes
 * via CacheSyncService::kicc().
 */
class CacheService
{
    public const TTL_PUBLIC = 3600;     // 1h — pages that change rarely
    public const TTL_API    = 300;      // 5m — API responses
    public const TTL_HOT    = 30;       // 30s — pool balance, real-time stats
    public const TTL_COLD   = 86400;    // 24h — pipeline graph, classification
    public const TTL_MEDIUM = 600;      // 10m — county lists, product categories

    /** Get or remember with the given TTL tier. */
    public function remember(string $key, mixed $data, int $ttl = self::TTL_PUBLIC): mixed
    {
        return Cache::remember($key, $ttl, fn () => is_callable($data) ? $data() : $data);
    }

    /** Hot cache — short TTL for real-time data. */
    public function hot(string $key, callable $data): mixed
    {
        return $this->remember($key, $data, self::TTL_HOT);
    }

    /** API cache — 5 min for API responses. */
    public function api(string $key, callable $data): mixed
    {
        return $this->remember($key, $data, self::TTL_API);
    }

    /** Public page cache — 1 hour. */
    public function public(string $key, callable $data): mixed
    {
        return $this->remember($key, $data, self::TTL_PUBLIC);
    }

    /** Cold data cache — 24 hours. */
    public function cold(string $key, callable $data): mixed
    {
        return $this->remember($key, $data, self::TTL_COLD);
    }

    /** Bust a specific cache key. */
    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    /** Bust all cache keys matching a pattern. */
    public function forgetPattern(string $pattern): void
    {
        try {
            // Redis KEYS is safe here (admin-only, low frequency)
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $k) {
                Cache::forget($k);
            }
        } catch (\Throwable $e) {
            Log::warning('cache: forgetPattern failed', ['pattern' => $pattern, 'error' => $e->getMessage()]);
        }
    }

    /** Tag-based busting (if Redis supports tags). */
    public function tags(array $tags): void
    {
        try {
            Cache::tags($tags)->flush();
        } catch (\Throwable) {
            // Tags not supported — fall back to prefix pattern
            foreach ($tags as $tag) {
                $this->forgetPattern("*{$tag}*");
            }
        }
    }

    /** Warm a set of known cache keys. Call from cron or after deploy. */
    public function warm(array $keys): array
    {
        $results = [];
        foreach ($keys as $key => $callback) {
            try {
                $data = $callback();
                Cache::forever($key, $data);
                $results[$key] = true;
            } catch (\Throwable $e) {
                $results[$key] = $e->getMessage();
            }
        }
        return $results;
    }

    /** Get cache stats. */
    public function stats(): array
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info();
            return [
                'used_memory' => $info['used_memory_human'] ?? '?',
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $info['keyspace_hits'] && $info['keyspace_misses']
                    ? round(($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses'])) * 100, 1) . '%'
                    : '0%',
                'uptime' => $info['uptime_in_days'] ?? 0 . ' days',
                'keys' => $this->countKeys(),
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function countKeys(): int
    {
        try {
            $prefix = config('cache.prefix', 'kicc');
            $keys = Cache::getRedis()->keys("{$prefix}:*");
            return count($keys);
        } catch (\Throwable) {
            return 0;
        }
    }
}