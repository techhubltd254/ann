<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class TransactionLog extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['duration_ms' => 'integer'];

    public function intent() { return $this->belongsTo(PaymentIntent::class); }
    public function gateway() { return $this->belongsTo(Gateway::class, 'gateway_id'); }

    public static function logFor(PaymentIntent $intent, string $type, array $request = [], array|string|null $response = null, ?int $status = null, ?int $gatewayId = null): self
    {
        return self::create([
            'payment_intent_id' => $intent->id,
            'gateway_id' => $gatewayId ?? $intent->gateway_id,
            'type' => $type,
            'request_payload' => json_encode($request),
            'response_payload' => $response ? json_encode($response) : null,
            'status_code' => $status,
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}
