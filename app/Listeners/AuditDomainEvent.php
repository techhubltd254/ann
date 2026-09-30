<?php

namespace App\Listeners;

use App\Events\DomainEvent;
use App\Services\AuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listens to ALL domain events. Writes one audit_log row per event.
 * Eliminates inconsistent inline AuditLogger::log() calls — every
 * domain event is now audited uniformly.
 */
class AuditDomainEvent implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 5;

    public function handle(DomainEvent $event): void
    {
        try {
            AuditLogger::log(
                $event->actorId(),
                $event->eventName(),
                get_class($event),
                null,
                $event->payload,
            );
        } catch (\Throwable $e) {
            Log::warning("audit-listener: failed", ['error' => $e->getMessage(), 'event' => $event->eventName()]);
        }
    }
}