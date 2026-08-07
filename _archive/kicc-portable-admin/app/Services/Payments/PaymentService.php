<?php

namespace App\Services\Payments;

use App\Models\PaymentIntent;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private array $adapters = [];

    public function __construct()
    {
        $this->adapters = [
            'mpesa' => app(MpesaAdapter::class),
            'stripe' => app(StripeAdapter::class),
            'escrow' => app(EscrowAdapter::class),
        ];
    }

    public function createIntent(array $params): PaymentIntent
    {
        $intent = PaymentIntent::create([
            'intent_id' => 'INT-' . strtoupper(uniqid()),
            'user_id' => $params['user_id'],
            'gateway_id' => $params['gateway_id'] ?? null,
            'amount' => $params['amount'],
            'currency' => $params['currency'] ?? 'KES',
            'status' => 'pending',
            'reference_type' => $params['reference_type'],
            'reference_id' => $params['reference_id'],
            'description' => $params['description'] ?? '',
            'metadata' => isset($params['metadata']) ? json_encode($params['metadata']) : null,
        ]);

        return $intent;
    }

    public function process(PaymentIntent $intent, array $gatewayParams = []): array
    {
        $gateway = PaymentGateway::find($intent->gateway_id);
        $adapterCode = $gateway?->code ?? 'mpesa';
        $adapter = $this->adapters[$adapterCode] ?? $this->adapters['mpesa'];

        DB::beginTransaction();
        try {
            $result = $adapter->charge(array_merge($gatewayParams, [
                'amount' => $intent->amount,
                'reference' => $intent->intent_id,
                'description' => $intent->description,
            ]));

            $this->logTransaction($intent, $adapterCode, $result);

            if ($result['success']) {
                $intent->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'metadata' => json_encode(array_merge(
                        json_decode($intent->metadata ?? '{}', true) ?? [],
                        ['gateway_response' => $result['raw'] ?? []]
                    )),
                ]);
                DB::commit();
                return ['success' => true, 'intent' => $intent->fresh()];
            }

            $intent->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $result['error'] ?? 'Gateway error',
            ]);
            DB::commit();
            return ['success' => false, 'error' => $result['error'] ?? 'Payment failed'];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing failed', ['intent' => $intent->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function refund(PaymentIntent $intent, float $amount, string $reason = ''): array
    {
        $gateway = PaymentGateway::find($intent->gateway_id);
        $adapterCode = $gateway?->code ?? 'mpesa';
        $adapter = $this->adapters[$adapterCode] ?? $this->adapters['mpesa'];

        $result = $adapter->refund($intent->intent_id, $amount, $reason);
        $this->logTransaction($intent, $adapterCode . '_refund', $result);

        if ($result['success']) {
            $intent->update(['status' => 'refunded']);
            return ['success' => true];
        }
        return ['success' => false, 'error' => $result['error'] ?? 'Refund failed'];
    }

    private function logTransaction(PaymentIntent $intent, string $gatewayCode, array $result): void
    {
        DB::table('transaction_logs')->insert([
            'gateway' => $gatewayCode,
            'intent_id' => $intent->id,
            'request_payload' => json_encode($intent->toArray()),
            'response_payload' => json_encode($result),
            'status' => $result['success'] ? 'success' : 'failed',
            'created_at' => now(),
        ]);
    }
}
