<?php

namespace App\Services\Payments;

use App\Models\EscrowTransaction;
use Illuminate\Support\Facades\Log;

class EscrowAdapter implements GatewayAdapter
{
    public function charge(array $params): array
    {
        $escrow = EscrowTransaction::create([
            'seller_id' => $params['seller_id'] ?? 0,
            'buyer_id' => $params['buyer_id'] ?? 0,
            'order_id' => $params['order_id'] ?? 0,
            'amount' => $params['amount'] ?? 0,
            'currency' => $params['currency'] ?? 'KES',
            'status' => 'held',
            'escrow_id' => 'ESC-' . strtoupper(uniqid()),
            'steps' => [
                ['label' => 'Payment received', 'done' => true],
                ['label' => 'Delivery confirmed', 'done' => false],
                ['label' => 'Quality verified', 'done' => false],
                ['label' => 'Funds released', 'done' => false],
            ],
            'current_step' => 1,
            'held_at' => now(),
        ]);

        return ['success' => true, 'gateway_intent_id' => $escrow->escrow_id, 'escrow' => $escrow];
    }

    public function refund(string $intentId, float $amount, string $reason = ''): array
    {
        $escrow = EscrowTransaction::where('escrow_id', $intentId)->first();
        if (!$escrow) return ['success' => false, 'error' => 'Escrow not found'];

        $escrow->update(['status' => 'refunded', 'released_at' => now()]);
        return ['success' => true];
    }

    public function status(string $intentId): array
    {
        $escrow = EscrowTransaction::where('escrow_id', $intentId)->first();
        if (!$escrow) return ['success' => false, 'error' => 'Not found'];
        return ['success' => true, 'status' => $escrow->status];
    }
}
