<?php

namespace App\Listeners;

use App\Events\DomainEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Listens to ALL domain events. Invalidates cache tags derived
 * from the event. Eliminates all 24 inline CacheSyncService::kicc()
 * / county() / national() / sector() call sites from controllers.
 */
class InvalidateCacheOnChange implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 3;

    public function handle(DomainEvent $event): void
    {
        $tags = $event->cacheTags();
        if (empty($tags)) return;

        try {
            foreach ($tags as $tag) {
                // Tag-based flush is O(1). Replaces Redis::scan() prefix-based
                // invalidation which was O(n) per prefix.
                Cache::tags([$tag])->flush();
            }
            Log::debug("cache-invalidator: flushed tags", ['tags' => $tags, 'event' => $event->eventName()]);
        } catch (\Throwable $e) {
            // Laravel's Cache::tags() requires a driver that supports tags
            // (Redis, Memcached). On file/array driver, fall back to no-op.
            Log::debug("cache-invalidator: tag flush skipped (driver may not support tags)", ['error' => $e->getMessage()]);
        }
    }
}