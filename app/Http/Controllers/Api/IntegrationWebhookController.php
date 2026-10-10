<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives authenticated, replay-protected events from the integration layer.
 * Transport authentication is not proof of settlement or delivery.
 * Financial and delivery state transitions require independent provider verification.
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

        // Neither payment nor delivery assertions may mutate shipment/escrow state.
        // A claimed delivery previously unlocked downstream auto-release workflows.
        // Provider-specific verification must be implemented before re-enabling this.
        if ($state === 'paid' || ($event['deliveryVerified'] ?? false)) {
            Log::notice('integration-webhook: unverified state transition ignored', [
                'provider' => $provider,
                'state' => $state,
            ]);
        }
    }
}