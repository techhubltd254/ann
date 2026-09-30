<?php

namespace App\Events;

/**
 * Fired when a marketplace product is created, updated, or deleted.
 * Replaces N8nService::fire('product_created/updated/deleted', ...).
 */
class ProductChanged extends DomainEvent
{
    public function __construct(
        public int $productId,
        public string $action,  // 'created', 'updated', 'deleted', 'restored'
        public ?int $countyId = null,
        public ?int $sellerId = null,
    ) {
        parent::__construct([
            'product_id' => $productId,
            'action' => $action,
            'county_id' => $countyId,
            'seller_id' => $sellerId,
        ]);
    }

    public function eventName(): string { return "product.{$this->action}"; }
    public function auditLabel(): string { return "Product #{$this->productId} {$this->action}"; }

    public function n8nEvent(): ?string
    {
        return match ($this->action) {
            'created' => 'product_created',
            'updated' => 'product_updated',
            'deleted' => 'product_deleted',
            default => null,
        };
    }

    public function cacheTags(): array
    {
        $tags = ['page:marketplace'];
        if ($this->countyId) $tags[] = "county:{$this->countyId}";
        return $tags;
    }
}