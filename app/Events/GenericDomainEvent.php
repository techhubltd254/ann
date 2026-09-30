<?php

namespace App\Events;

/**
 * Generic domain event — catch-all for events that don't have a
 * dedicated domain class yet. Used for the 44 remaining N8nService::fire()
 * call sites during compartmentalization Phase 1.
 */
class GenericDomainEvent extends DomainEvent
{
    public function __construct(
        private string $name,
        array $payload = [],
        private ?string $n8nEventName = null,
        private array $tags = [],
    ) {
        parent::__construct($payload);
    }

    public function eventName(): string { return $this->name; }
    public function auditLabel(): string { return $this->name; }
    public function n8nEvent(): ?string { return $this->n8nEventName ?? $this->name; }
    public function cacheTags(): array { return $this->tags; }
}