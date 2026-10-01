<?php

namespace App\Services;

use App\Support\CircuitBreaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * N8nService — fires n8n automation webhooks for every platform event.
 * Also verifies incoming webhooks from n8n.
 *
 * Protected by circuit breaker: after 5 consecutive failures, all n8n
 * calls are skipped for 60 seconds to prevent cascade timeouts.
 *
 * Non-blocking by design: failures are logged, never thrown.
 */
class N8nService
{
    private static ?CircuitBreaker $breaker = null;

    private static function breaker(): CircuitBreaker
    {
        return self::$breaker ??= new CircuitBreaker('n8n', 5, 60);
    }
    /** All events that can fire webhooks — also the 13 previously missing ones */
    public static array $events = [
        'order_created', 'booking_created', 'user_registered', 'venue_inquiry',
        'screen_ad_booked', 'exhibitor_onboarded', 'county_image_updated',
        'county_ad_created', 'provider_price_changed', 'provider_service_added',
        'provider_service_approved', 'agent_approved', 'agent_rejected',
        'abandoned_cart', 'cold_market_detected', 'agent_onboarded',
        'county_4d_uploaded', 'newsletter_subscribed', 'event_booking_created',
        'message_sent', 'review_approved', 'notification_created',
        'export_enquiry_created', 'export_enquiry_status_changed',
        'invoice_generated', '4d_pipeline_triggered', 'fulfillment_initiated',
        'institution_synced', 'institution_created', 'institution_updated',
        'institution_deleted', 'product_created', 'product_updated',
        'product_deleted', 'county_product_created', 'county_product_updated',
        'county_product_deleted', 'institution_hero_uploaded',

        // ── Virtual Exhibition Platform events ──
        'trader_spotlight_created', 'trader_verified', 'spotlight_video_attached',
        'voice_note_recorded', 'voice_note_transcribed', 'voice_note_published',
        'drone_footage_uploaded', 'drone_sequence_composed', 'presidential_audio_layered',
        'broadcast_scheduled', 'broadcast_started', 'broadcast_ended', 'playlist_updated',
        'screen_group_created', 'live_feed_distributed', 'screen_stream_started',
        'terminal_activated', 'virtual_tour_started',
        'floor_plan_uploaded', 'booth_positioned', 'layout_published',
        'consent_signed', 'waiver_collected', 'media_release_accepted',
        'audio_mining_started', 'speech_extracted', 'transcript_ready', 'broll_synchronized',
        'flythrough_rendered', 'beneficiary_audio_attached',
    ];

    /** Fire an n8n webhook for the given event with its payload. Protected by circuit breaker. */
    public static function fire(string $event, array $payload = []): void
    {
        self::breaker()->call(function () use ($event, $payload) {
            $base = rtrim((string) config('services.n8n.base_url', ''), '/');
            if (!$base) {
                $base = rtrim((string) env('N8N_BASE_URL', ''), '/');
            }
            $path = env('N8N_WEBHOOK_' . strtoupper($event));

            if (!$path) {
                Log::debug("n8n: no webhook configured for event [{$event}] — skipped", $payload);
                return;
            }
            $url = str_starts_with($path, 'http') ? $path : $base . '/' . ltrim($path, '/');
            if (!str_starts_with($url, 'http')) {
                Log::warning("n8n: invalid webhook URL for [{$event}]: {$url}");
                return;
            }

            $headers = ['Content-Type' => 'application/json'];
            $apiKey = config('services.n8n.api_key') ?: env('N8N_API_KEY');
            if ($apiKey) {
                $headers['X-N8N-API-KEY'] = $apiKey;
            }

            Http::timeout(5)->withHeaders($headers)->post($url, [
                'event' => $event,
                'platform' => 'kicc',
                'fired_at' => now()->toIso8601String(),
                'data' => $payload,
            ]);
            Log::info("n8n: fired [{$event}]");
        }, $event, $payload);
    }

    /** Verify an incoming n8n webhook signature */
    public static function verifySignature(string $payload, string $signature): bool
    {
        $secret = config('services.n8n.webhook_secret') ?: env('N8N_WEBHOOK_SECRET');
        if (!$secret) return true; // no secret = trust all
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
