<?php

namespace App\Services\Payments;

use App\Models\Payment\PaymentIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stripe card payments — PaymentIntent API (SAQ-A scope: we never touch card data).
 * Activated when STRIPE_KEY/STRIPE_SECRET are set; otherwise runs in stub mode.
 */
class StripePaymentDriver
{
    public function process(PaymentIntent $intent, array $meta = []): array
    {
        $secret = config('services.stripe.secret');
        if (! $secret) {
            Log::info('stripe: stub mode (no secret configured)', ['intent' => $intent->id]);
            return ['success' => false, 'stub' => true, 'request' => ['amount' => $intent->amount], 'response' => 'stripe not configured'];
        }

        $resp = Http::withToken($secret)
            ->asForm()
            ->post('https://api.stripe.com/v1/payment_intents', [
                'amount' => (int) round($intent->amount * 100), // minor units
                'currency' => strtolower($intent->currency ?? 'kes'),
                'automatic_payment_methods[enabled]' => 'true',
                'metadata[reference]' => $intent->reference ?? (string) $intent->id,
                'metadata[reference_type]' => $intent->reference_type ?? '',
            ]);

        if (! $resp->successful()) {
            return ['success' => false, 'request' => ['amount' => $intent->amount], 'response' => $resp->json('error.message')];
        }

        $pi = $resp->json();
        return [
            'success' => true,
            'request' => ['amount' => $intent->amount, 'currency' => $intent->currency ?? 'KES'],
            'response' => ['id' => $pi['id'], 'client_secret' => $pi['client_secret']],
            'client_secret' => $pi['client_secret'],
            'provider_ref' => $pi['id'],
        ];
    }

    /** Verify a Stripe webhook signature (whsec) — replay + tamper safe. */
    public static function verifyWebhook(string $payload, string $sigHeader, string $secret): bool
    {
        $ts = null; $sigs = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, null);
            if ($k === 't') $ts = $v;
            if ($k === 'v1') $sigs[] = $v;
        }
        if (! $ts || abs(time() - (int) $ts) > 300) return false; // 5-min replay window
        $expected = hash_hmac('sha256', "$ts.$payload", $secret);
        foreach ($sigs as $sig) {
            if (hash_equals($expected, $sig)) return true;
        }
        return false;
    }
}
