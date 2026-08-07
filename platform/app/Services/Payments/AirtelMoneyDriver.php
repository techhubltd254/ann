<?php

namespace App\Services\Payments;

use App\Models\Payment\PaymentIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Airtel Money (Africa) — push/collection rail. Stub-ready: activates when
 * AIRTEL_CLIENT_ID/SECRET are configured.
 */
class AirtelMoneyDriver
{
    public function process(PaymentIntent $intent, array $meta = []): array
    {
        $clientId = config('services.airtel.client_id');
        $clientSecret = config('services.airtel.secret');
        if (! $clientId || ! $clientSecret) {
            Log::info('airtel: stub mode', ['intent' => $intent->id]);
            return ['success' => false, 'stub' => true, 'request' => ['amount' => $intent->amount], 'response' => 'airtel not configured'];
        }

        $base = config('services.airtel.base_url', 'https://openapi.airtel.africa');
        $token = Http::asForm()->post("$base/auth/oauth2/token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials',
        ])->json('access_token');

        if (! $token) {
            return ['success' => false, 'request' => ['amount' => $intent->amount], 'response' => 'airtel auth failed'];
        }

        $resp = Http::withToken($token)->post("$base/merchant/v1/payments/", [
            'reference' => $intent->reference ?? (string) $intent->id,
            'subscriber' => ['country' => 'KE', 'currency' => 'KES', 'msisdn' => $meta['phone'] ?? ''],
            'transaction' => ['amount' => $intent->amount, 'country' => 'KE', 'currency' => 'KES', 'id' => (string) $intent->id],
        ]);

        return [
            'success' => $resp->successful(),
            'request' => ['amount' => $intent->amount],
            'response' => $resp->json(),
        ];
    }
}
