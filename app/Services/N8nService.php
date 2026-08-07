<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * N8nService — fires n8n automation webhooks for every platform event.
 *
 * Configure per-event webhook URLs in .env:
 *   N8N_BASE_URL=https://n8n.example.com   (optional base)
 *   N8N_WEBHOOK_ORDER_CREATED=/webhook/order-created
 *   N8N_WEBHOOK_BOOKING_CREATED=/webhook/booking-created
 *   N8N_WEBHOOK_USER_REGISTERED=/webhook/user-registered
 *   N8N_WEBHOOK_VENUE_INQUIRY=/webhook/venue-inquiry
 *   N8N_WEBHOOK_SCREEN_AD_BOOKED=/webhook/screen-ad-booked
 *
 * Non-blocking by design: failures are logged, never thrown —
 * automation must never break the user-facing flow.
 */
class N8nService
{
    /** Fire an n8n webhook for the given event with its payload. */
    public static function fire(string $event, array $payload = []): void
    {
        try {
            $base = rtrim((string) env('N8N_BASE_URL', ''), '/');
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

            Http::timeout(5)->post($url, [
                'event' => $event,
                'platform' => 'kicc',
                'fired_at' => now()->toIso8601String(),
                'data' => $payload,
            ]);
            Log::info("n8n: fired [{$event}]");
        } catch (\Throwable $e) {
            Log::warning("n8n: webhook [{$event}] failed: " . $e->getMessage());
        }
    }
}
