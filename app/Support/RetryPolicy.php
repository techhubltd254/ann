<?php

namespace App\Support;

/**
 * Pure, dependency-free retry / backoff policy.
 *
 * Extracted from IntegrationClient so the retry maths and the idempotency-key
 * derivation are unit-testable without booting Laravel.
 */
final class RetryPolicy
{
    public function __construct(
        public readonly int $maxAttempts = 3,
        public readonly int $baseDelayMs = 200,
        public readonly int $maxDelayMs = 5000,
        public readonly float $multiplier = 2.0,
    ) {}

    /** Delay in ms before the given 1-indexed attempt. Deterministic jitter when $seed != 0. */
    public function delayFor(int $attempt, int $seed = 0): int
    {
        $raw = $this->baseDelayMs * ($this->multiplier ** max(0, $attempt - 1));
        $capped = (int) min($raw, $this->maxDelayMs);
        if ($seed === 0) {
            return $capped;
        }
        $spread = (int) round($capped * 0.2);
        if ($spread < 1) {
            return $capped;
        }
        $jitter = ($seed % (2 * $spread + 1)) - $spread;

        return max(0, $capped + $jitter);
    }

    /** Retry transport errors, 5xx, 429 and 408. Never retry a 4xx client fault. */
    public function shouldRetry(int $attempt, ?int $status = null): bool
    {
        if ($attempt >= $this->maxAttempts) {
            return false;
        }
        if ($status === null) {
            return true;
        }

        return $status >= 500 || $status === 429 || $status === 408;
    }

    /** Stable idempotency key: the same logical call always derives the same key. */
    public static function idempotencyKey(string $scope, array $payload): string
    {
        ksort($payload);

        return substr(hash('sha256', $scope . '|' . json_encode($payload)), 0, 40);
    }
}
