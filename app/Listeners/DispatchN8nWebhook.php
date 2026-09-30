<?php

namespace App\Listeners;

use App\Events\DomainEvent;
use App\Services\N8nService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listens to ALL domain events. Dispatches corresponding n8n webhook
 * asynchronously via the queue. Eliminates all 50 inline
 * N8nService::fire() call sites from controllers/services.
 */
class DispatchN8nWebhook implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 5;

    public function handle(DomainEvent $event): void
    {
        $name = $event->n8nEvent();
        if (! $name) return;

        try {
            N8nService::fire($name, $event->payload);
        } catch (\Throwable $e) {
            Log::warning("n8n-listener: [{$name}] failed", ['error' => $e->getMessage()]);
        }
    }
}