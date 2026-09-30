<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base domain event — all cross-cutting side effects
 * (cache purge, n8n dispatch, audit log) react to these,
 * never to direct service calls from controllers.
 */
abstract class DomainEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $payload = [],
    ) {}

    /** Machine-readable event name for listeners (e.g. 'order.created'). */
    abstract public function eventName(): string;

    /** Human-readable label for audit log entries. */
    abstract public function auditLabel(): string;

    /** Cache tags to invalidate on this event. Empty array = no cache flush. */
    public function cacheTags(): array
    {
        return [];
    }

    /** N8n event name to fire (null = don't fire n8n). */
    public function n8nEvent(): ?string
    {
        return null;
    }

    /** Actor ID (null for system/anonymous events). */
    public function actorId(): ?int
    {
        return $this->payload['actor_id'] ?? auth()->id() ?? null;
    }
}