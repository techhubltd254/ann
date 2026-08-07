<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Receives "media published" events from the Kotlin admin engine.
 *
 * Security contract (matches edge worker + engine WebhookService):
 *  - HMAC-SHA256 over "{timestamp}.{rawBody}", header X-Signature-256
 *  - timestamp within ±5 min (replay prevention)
 *  - nonce single-use, 24 h TTL in Redis (replay prevention across retries)
 *  - idempotency: event_id processed once (cache-aside marker)
 *
 * Effect: warms the derivative URL, purges Cloudflare cache-tags so the new
 * county/sector video appears on the site immediately.
 */
class MediaPublishController extends Controller
{
    private const MAX_SKEW_SECONDS = 300;

    public function handle(Request $request): JsonResponse
    {
        if (! $this->verifySignature($request)) {
            Log::warning('media-publish: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $payload = $request->validate([
            'event_id'      => ['required', 'string', 'max:64'],
            'nonce'         => ['required', 'string', 'max:64'],
            'asset_id'      => ['required', 'string', 'max:64'],
            'entity_type'   => ['required', 'in:county,sector,national,venue'],
            'entity_slug'   => ['required', 'string', 'max:120'],
            'slot'          => ['required', 'string', 'max:60'],
            'derivatives'   => ['required', 'array'],
            'derivatives.hls'      => ['nullable', 'url'],
            'derivatives.webm'     => ['nullable', 'url'],
            'derivatives.poster'   => ['nullable', 'url'],
            'derivatives.blur'     => ['nullable', 'string', 'max:400'],
            'derivatives.glb_lod0' => ['nullable', 'url'],
        ]);

        // Replay prevention: nonce is single-use for 24 h.
        $nonceKey = "wh:nonce:{$payload['nonce']}";
        if (! Redis::set($nonceKey, 1, 'EX', 86400, 'NX')) {
            return response()->json(['error' => 'replay_detected'], 409);
        }

        // Idempotency: the engine retries with backoff — we may see the same event twice.
        $eventKey = "wh:event:{$payload['event_id']}";
        if (Cache::has($eventKey)) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        // Warm the derivative (pull through origin once so CDN edge is hot).
        if ($hls = $payload['derivatives']['hls'] ?? null) {
            Http::timeout(10)->withOptions(['stream' => true])->get($hls)->throw();
        }

        // Purge edge by cache-tag so the page re-renders with the new media.
        $tags = ["{$payload['entity_type']}:{$payload['entity_slug']}", 'media'];
        $this->purgeCloudflare($tags);

        Cache::put($eventKey, true, now()->addDay());
        Log::info('media-publish: applied', [
            'asset' => $payload['asset_id'],
            'slot'  => "{$payload['entity_type']}:{$payload['entity_slug']}#{$payload['slot']}",
        ]);

        return response()->json(['status' => 'published', 'purged' => $tags]);
    }

    private function verifySignature(Request $request): bool
    {
        $signature = (string) $request->header('X-Signature-256', '');
        $timestamp = (int) $request->header('X-Timestamp', 0);
        if ($signature === '' || abs(time() - $timestamp) > self::MAX_SKEW_SECONDS) {
            return false;
        }
        $secret = (string) config('services.engine_webhook.secret'); // env: ENGINE_WEBHOOK_SECRET
        if ($secret === '') {
            Log::critical('media-publish: ENGINE_WEBHOOK_SECRET not configured');
            return false;
        }
        $expected = hash_hmac('sha256', "{$timestamp}.{$request->getContent()}", $secret);
        return hash_equals($expected, preg_replace('/^sha256=/', '', $signature));
    }

    private function purgeCloudflare(array $tags): void
    {
        $zone = config('services.cloudflare.zone_id');
        $token = config('services.cloudflare.api_token');
        if (! $zone || ! $token) {
            Log::warning('media-publish: Cloudflare purge skipped (not configured)');
            return;
        }
        Http::withToken($token)
            ->timeout(10)
            ->post("https://api.cloudflare.com/client/v4/zones/{$zone}/purge_cache", ['tags' => $tags])
            ->throw();
    }
}
