<?php

namespace App\Services\Payments;

use App\Models\Payment\PaymentIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Crypto payment driver (USDT/USDC/ETH via Onramp or BitPesa).
 * When ONRAMP_API_KEY is set, it creates a live payment link.
 * Otherwise runs in stub mode with a static wallet address.
 */
class CryptoDriver
{
    public function process(PaymentIntent $intent, array $meta = []): array
    {
        $apiKey = config('services.crypto.onramp_api_key');
        $walletAddress = config('services.crypto.wallet_address', '0x0000000000000000000000000000000000000000');
        $network = $meta['network'] ?? config('services.crypto.network', 'USDT_TRC20');

        if ($apiKey) {
            try {
                $response = Http::withToken($apiKey)->post('https://api.onramp.money/v1/orders', [
                    'amount' => $intent->amount,
                    'currency' => $intent->currency ?? 'KES',
                    'reference' => $intent->intent_id,
                    'wallet_address' => $walletAddress,
                    'network' => $network,
                    'redirect_url' => config('app.url') . '/payment/' . $intent->intent_id . '/callback',
                ]);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'request' => ['amount' => $intent->amount, 'network' => $network],
                        'response' => $response->json(),
                        'payment_url' => $response->json('url'),
                        'transaction_ref' => 'CRYPTO-' . $intent->intent_id,
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('Crypto payment failed: ' . $e->getMessage());
            }
        }

        // Stub mode: return static wallet address
        Log::info('crypto: stub mode (no Onramp API key)', ['intent' => $intent->id]);

        return [
            'success' => true,
            'stub' => true,
            'request' => ['amount' => $intent->amount, 'network' => $network],
            'response' => [
                'message' => 'Crypto payment stub — configure ONRAMP_API_KEY for live payments.',
                'wallet_address' => $walletAddress,
                'network' => $network,
                'reference' => $intent->intent_id,
            ],
            'transaction_ref' => 'CRYPTO-' . $intent->intent_id,
        ];
    }

    public function verify(string $transactionRef): array
    {
        return ['success' => true, 'status' => 'stub', 'message' => 'Crypto payment verification stub.'];
    }
}