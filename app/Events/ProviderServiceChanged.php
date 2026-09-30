<?php

namespace App\Events;

/**
 * Fired when a provider service is approved or denied.
 * Replaces N8nService::fire('provider_service_approved', ...).
 */
class ProviderServiceChanged extends DomainEvent
{
    public function __construct(
        public string $table,
        public int $id,
        public string $action,  // 'approved', 'denied'
    ) {
        parent::__construct([
            'table' => $table,
            'id' => $id,
            'action' => $action,
        ]);
    }

    public function eventName(): string { return "provider.{$this->action}"; }
    public function auditLabel(): string { return "Provider {$this->table}#{$this->id} {$this->action}"; }
    public function n8nEvent(): ?string { return $this->action === 'approved' ? 'provider_service_approved' : null; }
}