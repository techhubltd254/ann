<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Log;

class StripeAdapter implements GatewayAdapter
{
    private string $secretKey;
    private string $webhookSecret;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret_key', '');
        $this->webhookSecret = config('services.stripe.webhook_secret', '');
    }

    public function charge(array $params): array
    {
        // Stripe charges are handled client-side via PaymentElement.
        // This server-side method confirms payment_intents for server-driven flows.
        return ['success' => false, 'error' => 'Use client-side Stripe Elements for card payments'];
    }

    public function refund(string $intentId, float $amount, string $reason = ''): array
    {
        try {
            $stripe = new \Stripe\StripeClient($this->secretKey);
            $refund = $stripe->refunds->create([
                'payment_intent' => $intentId,
                'amount' => (int) ($amount * 100), // cents
                'reason' => $reason ?: 'requested_by_customer',
            ]);
            return ['success' => true, 'refund_id' => $refund->id, 'raw' => $refund->toArray()];
        } catch (\Exception $e) {
            Log::error('Stripe refund failed', ['intent' => $intentId, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function status(string $intentId): array
    {
        try {
            $stripe = new \Stripe\StripeClient($this->secretKey);
            $intent = $stripe->paymentIntents->retrieve($intentId);
            return ['success' => true, 'status' => $intent->status, 'data' => $intent->toArray()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
