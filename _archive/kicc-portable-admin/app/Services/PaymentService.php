<?php

namespace App\Services;

use App\Models\Payment\Gateway;
use App\Models\Payment\PaymentIntent;
use App\Models\Payment\TransactionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaymentService
{
    protected ?Gateway $gateway = null;

    public function __construct(?string $gatewayCode = 'mpesa')
    {
        $this->gateway = Gateway::where('code', $gatewayCode)->where('is_active', true)->first();
    }

    /**
     * Create a payment intent for a billable model (Order, Booking, etc.)
     * and push payment to the active gateway.
     */
    public function charge(Model $billable, float $amount, array $meta = []): PaymentIntent
    {
        $intent = PaymentIntent::createFor($billable, $amount, $meta);
        $intent->gateway()->associate($this->gateway);
        $intent->save();

        // Dispatch to the gateway driver — stub until M-Pesa API key is added
        $driver = $this->resolveDriver($this->gateway?->code ?? 'manual');
        $result = $driver->process($intent, $meta);

        TransactionLog::logFor(
            $intent, 'request',
            $result['request'] ?? [],
            $result['response'] ?? null,
            ($result['success'] ?? false) ? 200 : 400,
        );

        if ($result['success'] ?? false) {
            $intent->confirm();
        } else {
            // STK push sent / pending confirmation — not a failure yet
            $intent->update(['status' => 'processing', 'gateway_id' => $this->gateway?->id]);
        }

        return $intent->fresh();
    }

    public function resolveDriver(string $code): PaymentDriverInterface
    {
        return match ($code) {
            'mpesa'  => app(MpesaService::class),
            'stripe' => app(\Laravel\Cashier\Cashier::class),  // placeholder
            default  => new ManualPaymentDriver(),
        };
    }
}

interface PaymentDriverInterface
{
    public function process(PaymentIntent $intent, array $meta = []): array;
    public function verify(string $transactionRef): array;
}

class ManualPaymentDriver implements PaymentDriverInterface
{
    public function process(PaymentIntent $intent, array $meta = []): array
    {
        return [
            'success' => true,
            'request' => ['intent_id' => $intent->intent_id, 'amount' => $intent->amount],
            'response' => ['message' => 'Payment recorded (awaiting M-Pesa API key).'],
        ];
    }
    public function verify(string $transactionRef): array
    {
        return ['success' => true, 'status' => 'pending'];
    }
}
