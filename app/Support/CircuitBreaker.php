<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Lightweight circuit breaker for external service calls.
 *
 * After $failureThreshold consecutive failures, the circuit OPENS and
 * all calls are skipped for $resetTimeout seconds. After timeout,
 * the circuit goes HALF_OPEN — the next call is allowed through as a probe.
 * If the probe succeeds, circuit CLOSES. If it fails, circuit re-opens.
 */
class CircuitBreaker
{
    public function __construct(
        private string $service,
        private int $failureThreshold = 5,
        private int $resetTimeout = 60,
    ) {}

    /** Execute a callable if the circuit is closed. Returns null if open. */
    public function call(callable $fn, string $action, array $context = []): mixed
    {
        if ($this->isOpen()) {
            Log::warning("circuit-breaker: {$this->service} open, skipping [{$action}]");
            return null;
        }

        try {
            $result = $fn();
            $this->recordSuccess();
            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure($action, $e, $context);
            return null;
        }
    }

    private function isOpen(): bool
    {
        $state = Cache::get($this->key('state'), 'closed');
        if ($state === 'open') {
            $openedAt = Cache::get($this->key('opened_at'), 0);
            if (time() - $openedAt >= $this->resetTimeout) {
                Cache::put($this->key('state'), 'half_open', $this->resetTimeout * 2);
                Log::info("circuit-breaker: {$this->service} → half_open (probe allowed)");
                return false;
            }
            return true;
        }
        return false;
    }

    private function recordSuccess(): void
    {
        Cache::put($this->key('failures'), 0, $this->resetTimeout * 2);
        Cache::put($this->key('state'), 'closed', $this->resetTimeout * 2);
    }

    private function recordFailure(string $action, \Throwable $e, array $context): void
    {
        $failures = (int) Cache::get($this->key('failures'), 0) + 1;
        Cache::put($this->key('failures'), $failures, $this->resetTimeout * 2);

        Log::warning("circuit-breaker: {$this->service} failure #{$failures} on [{$action}]", [
            'error' => $e->getMessage(),
            'context' => json_encode($context),
        ]);

        if ($failures >= $this->failureThreshold) {
            Cache::put($this->key('state'), 'open', $this->resetTimeout * 2);
            Cache::put($this->key('opened_at'), time(), $this->resetTimeout * 2);
            Log::error("circuit-breaker: {$this->service} OPEN — failing fast for {$this->resetTimeout}s");
        }
    }

    private function key(string $suffix): string
    {
        return "cb:{$this->service}:{$suffix}";
    }
}