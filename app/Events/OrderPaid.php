<?php

namespace App\Events;

/**
 * Fired when an order transitions to 'paid' status.
 * Replaces N8nService::fire('order_created', ...) and related cache busts.
 */
class OrderPaid extends DomainEvent
{
    public function __construct(
        public int $orderId,
        public float $amount,
        public ?string $pipelineCode = null,
        public ?int $buyerId = null,
    ) {
        parent::__construct([
            'order_id' => $orderId,
            'amount' => $amount,
            'pipeline_code' => $pipelineCode,
            'buyer_id' => $buyerId,
        ]);
    }

    public function eventName(): string { return 'order.paid'; }
    public function auditLabel(): string { return "Order #{$this->orderId} paid KES {$this->amount}"; }

    public function n8nEvent(): ?string { return 'order_created'; }

    public function cacheTags(): array
    {
        return ['page:home', 'page:marketplace'];
    }
}