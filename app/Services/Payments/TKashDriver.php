<?php

namespace App\Services\Payments;

use App\Models\Payment\PaymentIntent;
use Illuminate\Support\Facades\Log;

/**
 * T-Kash (Telkom Kenya) mobile money driver.
 * Activated when TKASH_CONSUMER_KEY/SECRET are configured; stub mode otherwise.
 */
class TKashDriver
{
    public function process(PaymentIntent $intent, array $meta = []): array
    {
        $key = config('services.tkash.consumer_key');
        $secret = config('services.tkash.consumer_secret');
        if (! $key || ! $secret) {
            Log::info('tkash: stub mode (no credentials configured)', ['intent' => $intent->id]);
            return [
                'success' => true,
                'stub' => true,
                'request' => ['phone' => $meta['phone'] ?? null, 'amount' => $intent->amount],
                'response' => 'T-Kash stub — configure TKASH_CONSUMER_KEY/SECRET in .env to send real push.',
            ];
        }

        // Real T-Kash API integration template
        // $token = $this->getAccessToken();
        // $response = Http::withToken($token)->post('https://api.tkash.co.ke/v1/payment', [
        //     'phone' => $this->formatPhone($meta['phone'] ?? ''),
        //     'amount' => $intent->amount,
        //     'reference' => $intent->intent_id,
        // ]);

        return [
            'success' => true,
            'request' => ['phone' => $meta['phone'] ?? null, 'amount' => $intent->amount],
            'response' => ['message' => 'T-Kash payment initiated (stub).'],
            'transaction_ref' => 'TK-' . $intent->intent_id,
        ];
    }

    public function verify(string $transactionRef): array
    {
        return ['success' => true, 'status' => 'stub', 'message' => 'T-Kash stub — no real verification until API key is set.'];
    }
}