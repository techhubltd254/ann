<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * CacheSyncService — keeps the public site in sync when admins make changes.
 *
 * Two layers must be busted so edits appear instantly on kicctest.org:
 *   1. Laravel Redis cache (Cache::remember keys used by public controllers)
 *   2. Cloudflare edge cache (s-maxage headers set by CachePublicResponse)
 *
 * Used by CountyAdminController, NationalAdminController and KiccAdminController
 * after every successful write.
 */
class CacheSyncService
{
    /**
     * Bust every cache key that starts with one of these fragments.
     */
    private function forgetKeysWithPrefix(string $prefix): void
    {
        $store = config('cache.default');

        if ($store === 'redis' || $store === 'predis') {
            $this->forgetRedisPrefix($prefix);
            return;
        }

        // Fallback: iterate the in-memory / file store. Not exhaustive, but
        // better than nothing for non-Redis environments.
        try {
            $all = array_keys(Cache::getStore()->getItems() ?? []);
            foreach ($all as $k) {
                if (str_starts_with($k, $prefix)) {
                    Cache::forget(str_replace(config('cache.prefix'), '', $k));
                }
            }
        } catch (\Throwable $e) {
            // best-effort only
        }
    }

    private function forgetRedisPrefix(string $prefix): void
    {
        try {
            $client = Redis::connection('cache')->client();
            $cachePrefix = (string) config('cache.prefix');
            // The Redis connection may add its own prefix (e.g. '...-database-')
            // on top of Laravel's cache prefix — match both forms.
            $connPrefix = (string) config('database.redis.options.prefix', '');
            $pattern = $cachePrefix . $prefix . '*';
            if ($connPrefix !== '' && ! str_starts_with($pattern, $connPrefix)) {
                $pattern = $connPrefix . $pattern;
            }

            // PhpRedis: scan(cursor, pattern, count) — cursor passed by reference.
            $cursor = null;
            do {
                $keys = $client->scan($cursor, $pattern, 500);
                if (is_string($keys)) {
                    $keys = [$keys];
                }
                if (! empty($keys)) {
                    // The client auto-prefixes DEL keys with the connection
                    // prefix — strip it from scanned keys first.
                    $toDelete = array_map(fn ($k) => $connPrefix !== '' && str_starts_with($k, $connPrefix)
                        ? substr($k, strlen($connPrefix))
                        : $k, $keys);
                    $client->del($toDelete);
                }
            } while ($cursor !== 0 && $cursor !== null && $cursor !== false);
        } catch (\Throwable $e) {
            Log::warning('cache-sync: redis scan failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Bust everything for a county — all entity caches, pins, sector items,
     * media resolves and DAV availability keys.
     */
    public function county(int $countyId): void
    {
        $countyId = (int) $countyId;

        $prefixes = [
            'kicc_counties_index',
            "kicc_county_sector_counts_{$countyId}",
            "kicc_county_attractions_{$countyId}",
            "kicc_county_hotels_{$countyId}",
            "kicc_county_products_{$countyId}",
            "kicc_county_exhibitions_{$countyId}",
            "kicc_county_linked_sectors_{$countyId}",
            "kicc_county_sector_items_{$countyId}_",
            "resolve:county_hero_id_{$countyId}",
            "resolve:county_hero_url_{$countyId}",
            "county_pins_{$countyId}",
            "dav:county:{$countyId}",
            "dav:sector:{$countyId}:",
            "county_admin_dash_{$countyId}_",
        ];

        foreach ($prefixes as $p) {
            $this->forgetKeysWithPrefix($p);
        }

        $this->purgeCloudflareUrls([
            '/counties',
            '/counties/' . $this->countySlug($countyId),
        ]);
    }

    /**
     * Bust a single sector within a county (tile/video/media edits).
     */
    public function sector(int $countyId, int $sectorId): void
    {
        $this->forgetKeysWithPrefix("dav:sector:{$countyId}:{$sectorId}");
        $this->forgetKeysWithPrefix("kicc_county_sector_items_{$countyId}_{$sectorId}_");
        $this->purgeCloudflareUrls(['/counties/' . $this->countySlug($countyId)]);
    }

    /**
     * Bust a single institution (hero, media, content edits).
     */
    public function institution(int $institutionId): void
    {
        $this->forgetKeysWithPrefix("dav:inst:{$institutionId}");
        $this->forgetKeysWithPrefix("resolve:inst_hero_id_{$institutionId}");
    }

    /**
     * Bust national government / exhibition page caches + ministry media.
     */
    public function national(): void
    {
        $prefixes = [
            'national',
            'ministry',
            'agency',
            'national_admin_dash',
            'resolve:county_hero_id_0',
            'resolve:county_hero_url_0',
        ];
        foreach ($prefixes as $p) {
            $this->forgetKeysWithPrefix($p);
        }

        $this->purgeCloudflareUrls([
            '/national-government',
            '/national-exhibition',
        ]);
    }

    /**
     * Bust global / KICC-level caches (marketplace index, home page, counts).
     */
    public function kicc(): void
    {
        $prefixes = [
            'kicc_counties_index',
            'kicc_marketplace',
            'marketplace',
            'kicc_home',
            'home',
            'display_priority',
            'counties',
            'kicc_admin_dash_',
        ];
        foreach ($prefixes as $p) {
            $this->forgetKeysWithPrefix($p);
        }

        $this->purgeCloudflareUrls([
            '/',
            '/marketplace',
            '/counties',
            '/national-government',
        ]);
    }

    /**
     * Full reset — clear the entire cache store (used sparingly).
     */
    public function flushAll(): void
    {
        try {
            Cache::flush();
        } catch (\Throwable $e) {
            Log::warning('cache-sync: flush failed', ['error' => $e->getMessage()]);
        }
    }

    private function countySlug(int $countyId): string
    {
        $slug = Cache::remember("sync:county_slug_{$countyId}", 3600, function () use ($countyId) {
            return \App\Models\County::where('id', $countyId)->value('slug') ?? '';
        });
        return (string) $slug;
    }

    /**
     * Purge Cloudflare edge cache for specific URLs.
     */
    public function purgeCloudflareUrls(array $urls): void
    {
        $urls = array_values(array_filter(array_map(fn ($u) => 'https://kicctest.org' . $u, $urls)));

        $zone = config('services.cloudflare.zone_id');
        $token = config('services.cloudflare.api_token');
        if (! $zone || ! $token) {
            return;
        }

        try {
            Http::withToken($token)
                ->timeout(10)
                ->post("https://api.cloudflare.com/client/v4/zones/{$zone}/purge_cache", [
                    'files' => $urls,
                ])->throw();
        } catch (\Throwable $e) {
            Log::warning('cache-sync: cloudflare purge failed', [
                'urls' => $urls,
                'error' => $e->getMessage(),
            ]);
        }
    }
}