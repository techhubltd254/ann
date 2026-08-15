<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Order;
use App\Services\Payments\StripePaymentDriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stripe webhook receiver — signature-verified (whsec), replay-safe (5-min window),
 * idempotent on event id. Updates payment_intents from Stripe PaymentIntent events.
 */
class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sig = (string) $request->header('Stripe-Signature', '');
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '' || ! StripePaymentDriver::verifyWebhook($payload, $sig, $secret)) {
            Log::warning('stripe webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'invalid signature'], 401);
        }

        $event = json_decode($payload, true);
        $eventId = $event['id'] ?? null;
        $type = $event['type'] ?? '';
        $piId = $event['data']['object']['id'] ?? null;

        if (! $eventId || ! $piId) {
            return response()->json(['error' => 'malformed event'], 422);
        }

        // Idempotency: process each event once.
        $marker = DB::table('transaction_logs')->where('gateway_response', 'like', '%' . $eventId . '%')->exists();
        if ($marker) {
            return response()->json(['status' => 'duplicate']);
        }

        $status = match ($type) {
            'payment_intent.succeeded' => 'confirmed',
            'payment_intent.payment_failed' => 'failed',
            'payment_intent.canceled' => 'failed',
            default => null,
        };

        if ($status) {
            DB::table('payment_intents')->where('provider_ref', $piId)->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
            DB::table('transaction_logs')->insert([
                'payment_intent_id' => DB::table('payment_intents')->where('provider_ref', $piId)->value('id'),
                'type' => 'webhook',
                'gateway_request' => json_encode(['type' => $type]),
                'gateway_response' => json_encode(['event_id' => $eventId, 'pi' => $piId, 'status' => $status]),
                'created_at' => now(),
            ]);

            if ($type === 'payment_intent.succeeded') {
                $pi = DB::table('payment_intents')->where('provider_ref', $piId)->first();
                if ($pi && $pi->reference_type === 'order') {
                    $order = Order::find($pi->reference_id);
                    if ($order) {
                        $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
                        \App\Models\Ecommerce\OrderStatusHistory::create([
                            'order_id' => $order->id,
                            'status_from' => 'pending',
                            'status_to' => 'paid',
                            'notes' => 'Payment confirmed via Stripe webhook',
                            'changed_by_user_id' => null,
                        ]);
                        try {
                            $gcController = app(\App\Http\Controllers\Web\GiftCardController::class);
                            $gcController->activateByOrder($order);
                        } catch (\Throwable $e) {
                            Log::warning('Stripe webhook: gift card activation failed', ['order' => $order->id, 'error' => $e->getMessage()]);
                        }
                    }
                }
            }
        }

        return response()->json(['status' => 'ok', 'type' => $type]);
    }
}
