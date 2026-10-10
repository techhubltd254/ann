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

        $request->validate(['provider'=>'required|string|max:80','event'=>'required|array','ts'=>'required|integer']);
        $ts=(int)$request->input('ts');if(abs(time()-$ts)>300)return response()->json(['ok'=>false],401);
        $nonce=(string)$request->header('X-Integration-Nonce');$sig=(string)$request->header('X-Integration-Signature');
        if(!preg_match('/^[A-Za-z0-9_-]{16,128}$/',$nonce)||!hash_equals(hash_hmac('sha256',$ts.'.'.$nonce.'.'.$request->getContent(),$secret),$sig))return response()->json(['ok'=>false],401);
        if(!\Illuminate\Support\Facades\Cache::add('integration-replay:'.hash('sha256',$nonce),1,600))return response()->json(['ok'=>false],409);
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

        // A forwarded payment event is not buyer consent or verified settlement.
        // Fund release is deliberately disabled here; use the authorized escrow workflow.
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