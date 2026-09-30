<?php

namespace App\Events;

/**
 * Fired when escrow is released to a seller.
 * Replaces N8nService::fire('escrow_released', ...) and pool recalc trigger.
 */
class EscrowReleased extends DomainEvent
{
    public function __construct(
        public string $escrowId,
        public float $amount,
        public ?int $sellerId = null,
        public ?int $buyerId = null,
    ) {
        parent::__construct([
            'escrow_id' => $escrowId,
            'amount' => $amount,
            'seller_id' => $sellerId,
            'buyer_id' => $buyerId,
        ]);
    }

    public function eventName(): string { return 'escrow.released'; }
    public function auditLabel(): string { return "Escrow {$this->escrowId} released KES {$this->amount}"; }
    public function n8nEvent(): ?string { return 'escrow_released'; }
    public function cacheTags(): array { return ['page:home']; }
}