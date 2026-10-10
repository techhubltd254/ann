<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives validated webhook events forwarded from the Node.js integration layer.
 * The Node.js service verifies provider signatures before forwarding.
 */
class IntegrationWebhookController extends Controller
{
    public function forward(Request $request)
    {
        $secret = config('kicc.integration_webhook_secret');
        $header = $request->header('X-Integration-Secret');

        if (!is_string($secret) || strlen($secret) < 32 || $secret === 'dev-secret' || !hash_equals($secret, (string)$header)) {
            Log::warning('integration-webhook: invalid secret', [
                'ip' => $request->ip(),
                'provider' => $request->input('provider'),
            ]);
            return response()->json(['ok' => false, 'error' => 'invalid secret'], 401);
        }

        $provider = $request->input('provider');
        $event = $request->input('event', []);
        $ts = $request->input('ts');

        Log::info('integration-webhook: received', [
            'provider' => $provider,
            'orderRef' => $event['orderRef'] ?? null,
            'state' => $event['state'] ?? null,
            'amount' => $event['amount'] ?? null,
        ]);

        // ── Dispatch to handlers ──
        $this->handleEvent($provider, $event);

        return response()->json(['ok' => true, 'received' => $provider]);
    }

    private function handleEvent(string $provider, array $event): void
    {
        $state = $event['state'] ?? null;
        $orderRef = $event['orderRef'] ?? null;
        $amount = $event['amount'] ?? null;
        $providerRef = $event['providerRef'] ?? null;

        // Payment succeeded -> release escrow
        if ($state === 'paid' && $orderRef) {
            try {
                $escrow = \App\Models\EscrowTransaction::where('escrow_id', $orderRef)->first();
                if ($escrow && $escrow->status === 'held') {
                    app(\App\Services\EscrowService::class)->confirmByBuyer($escrow);
                    app(\App\Services\EscrowService::class)->releaseFunds($escrow);
                    Log::info('integration-webhook: escrow auto-released', [
                        'escrow_id' => $escrow->id,
                        'provider' => $provider,
                        'orderRef' => $orderRef,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('integration-webhook: escrow release failed', [
                    'orderRef' => $orderRef,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Delivery verified -> auto-release escrow
        if ($event['deliveryVerified'] ?? false) {
            try {
                $shipment = \App\Models\CourierShipment::where('tracking_number', $providerRef)->first();
                if ($shipment) {
                    $shipment->update(['status' => 'delivered', 'delivered_at' => now()]);
                    $escrow = $shipment->escrowTransaction;
                    if ($escrow && $escrow->status === 'held') {
                        app(\App\Services\EscrowService::class)->markDelivered($escrow, $event['location'] ?? 'unknown');
                    }
                }
            } catch (\Throwable $e) {
                Log::error('integration-webhook: delivery update failed', [
                    'providerRef' => $providerRef,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}