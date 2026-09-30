<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Non-blocking edge cache purge. Dispatched from CacheSyncService
 * whenever an admin writes content that invalidates cached pages.
 *
 * Two-phase purge:
 *  1. Cloudflare API — URL-based purge (zone-level, instant across all PoPs)
 *  2. Edge worker /edge/purge — tag-based purge (Cache API granular invalidation)
 *
 * Runs asynchronously via Redis queue. Retries 3× with 10s backoff.
 */
class PurgeEdgeCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public array $urls = [],
        public array $tags = [],
    ) {}

    public function handle(): void
    {
        $baseUrl = config('app.url') ?: 'https://kicctest.org';
        $purgeKey = config('services.cloudflare.purge_key') ?: env('CF_TOKEN');
        $zoneId = config('services.cloudflare.zone_id');
        $orgBase = rtrim($baseUrl, '/');

        // ── 1. Cloudflare API URL-based purge ──
        if ($zoneId && $purgeKey && !empty($this->urls)) {
            $fullUrls = array_map(fn ($u) => $orgBase . $u, $this->urls);
            try {
                $resp = Http::withToken($purgeKey)
                    ->timeout(10)
                    ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                        'files' => $fullUrls,
                    ]);
                if ($resp->successful()) {
                    Log::info("cdn-purge: Cloudflare URL purge OK", ['count' => count($fullUrls)]);
                } else {
                    Log::warning("cdn-purge: Cloudflare URL purge failed", ['status' => $resp->status()]);
                }
            } catch (\Throwable $e) {
                Log::warning("cdn-purge: Cloudflare URL purge exception", ['error' => $e->getMessage()]);
            }
        }

        // ── 2. Edge worker tag-based purge ──
        if (!empty($this->tags)) {
            $purgeEndpoint = "{$orgBase}/edge/purge";
            try {
                Http::timeout(10)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->post($purgeEndpoint, [
                        'tags' => $this->tags,
                        'paths' => [],   // tag-only; worker handles tag invalidation
                    ]);
                Log::info("cdn-purge: tag purge dispatched", ['tags' => $this->tags]);
            } catch (\Throwable $e) {
                Log::warning("cdn-purge: tag purge failed", ['error' => $e->getMessage()]);
            }
        }
    }
}