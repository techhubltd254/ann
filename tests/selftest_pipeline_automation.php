<?php
/**
 * Standalone self-test for the inter-pipeline automation glue (no Laravel boot needed).
 * Run: php tests/selftest_pipeline_automation.php
 */
require __DIR__ . '/../app/Support/RetryPolicy.php';

use App\Support\RetryPolicy;

$pass = 0; $fail = 0;
function ck(string $name, bool $ok, string $detail = ''): void {
    global $pass, $fail;
    if ($ok) { $pass++; echo "PASS  {$name}" . ($detail ? "  -> {$detail}" : '') . "\n"; }
    else { $fail++; echo "FAIL  {$name}" . ($detail ? "  -> {$detail}" : '') . "\n"; }
}

$r = new RetryPolicy(3, 200, 5000, 2.0);
ck('backoff is exponential', $r->delayFor(1) === 200 && $r->delayFor(2) === 400 && $r->delayFor(3) === 800, "200/400/800 ms");
ck('backoff is capped', $r->delayFor(12) === 5000, "attempt 12 capped at {$r->delayFor(12)} ms");
ck('jitter stays within +/-20%', (function () use ($r) {
    for ($seed = 1; $seed < 400; $seed++) { $d = $r->delayFor(3, $seed); if ($d < 640 || $d > 960) return false; }
    return true;
})(), '400 seeds checked at attempt 3');
ck('transport errors are retried', $r->shouldRetry(1, null) === true);
ck('5xx is retried', $r->shouldRetry(1, 503) === true);
ck('429 is retried', $r->shouldRetry(1, 429) === true);
ck('408 is retried', $r->shouldRetry(1, 408) === true);
ck('4xx client fault is NOT retried', $r->shouldRetry(1, 400) === false && $r->shouldRetry(1, 404) === false);
ck('retries stop at maxAttempts', $r->shouldRetry(3, 503) === false, 'attempt 3 of 3 is final');

$k1 = RetryPolicy::idempotencyKey('POST /api/pipeline/cascade', ['roots' => [1, 2], 'x' => 'y']);
$k2 = RetryPolicy::idempotencyKey('POST /api/pipeline/cascade', ['x' => 'y', 'roots' => [1, 2]]);
$k3 = RetryPolicy::idempotencyKey('POST /api/pipeline/cascade', ['roots' => [1, 3], 'x' => 'y']);
ck('idempotency key is order-independent', $k1 === $k2, $k1);
ck('idempotency key changes with payload', $k1 !== $k3, "{$k1} vs {$k3}");
ck('idempotency key is a stable length', strlen($k1) === 40, strlen($k1) . ' hex chars');

echo "\nRESULT: {$pass} passed, {$fail} failed\n";
exit($fail === 0 ? 0 : 1);
