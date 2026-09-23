<?php

namespace App\Services;

use App\Support\RetryPolicy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * PipelineBusClient — the Laravel edge of the inter-pipeline event bus.
 *
 * Every outbound call carries a deterministic X-Idempotency-Key and is retried
 * with exponential backoff + jitter on transport errors, 5xx, 429 and 408.
 * This edge is what was missing: previously nothing in Laravel could trigger
 * another pipeline, so one pipeline's success could not drive the next.
 */
class PipelineBusClient
{
    private string $baseUrl;
    private RetryPolicy $retry;

    public function __construct(?string $baseUrl = null, ?RetryPolicy $retry = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('kicc.pipeline_bus.url', 'http://127.0.0.1:8790'), '/');
        $this->retry = $retry ?? new RetryPolicy(
            (int) config('kicc.pipeline_bus.max_attempts', 3),
            (int) config('kicc.pipeline_bus.base_delay_ms', 200),
            (int) config('kicc.pipeline_bus.max_delay_ms', 5000),
        );
    }

    public function baseUrl(): string { return $this->baseUrl; }

    public function health(): array { return $this->call('GET', '/health'); }

    /** The 87-pipeline dependency graph: nodes, labelled edges, layers, cycles. */
    public function graph(): array { return $this->call('GET', '/api/pipeline/graph'); }

    /** Upstream (must run first) and downstream (triggered by) one pipeline. */
    public function upstream(int $pipelineId): array
    {
        return $this->call('GET', '/api/pipeline/upstream?id=' . $pipelineId);
    }

    /** Settle a pipeline and cascade into everything that depends on it. */
    public function cascade(array $roots, array $opts = []): array
    {
        return $this->call('POST', '/api/pipeline/cascade', array_merge([
            'roots' => array_values(array_map('intval', $roots)),
        ], $opts));
    }

    /** Trigger one pipeline directly. */
    public function trigger(int $pipelineId, array $payload = []): array
    {
        return $this->call('POST', '/api/pipeline/trigger', array_merge([
            'pipeline_id' => $pipelineId,
        ], $payload));
    }

    /** Aggregate bus metrics for the Mother Admin monitoring page. */
    public function status(): array { return $this->call('GET', '/api/pipeline/status'); }

    public function ledger(int $limit = 100): array
    {
        return $this->call('GET', '/api/pipeline/ledger?limit=' . $limit);
    }

    public function dlq(int $limit = 100): array
    {
        return $this->call('GET', '/api/pipeline/dlq?limit=' . $limit);
    }

    /** Low-level call: retry + backoff + idempotency. */
    public function call(string $method, string $path, array $body = [], ?string $idempotencyKey = null): array
    {
        $key = $idempotencyKey ?? RetryPolicy::idempotencyKey($method . ' ' . $path, $body);
        $attempt = 0;
        $lastError = 'unknown';

        while (true) {
            $attempt++;
            try {
                $pending = Http::timeout((int) config('kicc.pipeline_bus.timeout', 10))
                    ->withHeaders(['Accept' => 'application/json', 'X-Idempotency-Key' => $key]);

                $response = $method === 'GET'
                    ? $pending->get($this->baseUrl . $path)
                    : $pending->post($this->baseUrl . $path, $body);

                if ($response->successful()) {
                    $json = $response->json();

                    return is_array($json) ? $json : ['ok' => true];
                }

                $lastError = 'HTTP ' . $response->status();
                if (! $this->retry->shouldRetry($attempt, $response->status())) {
                    break;
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                if (! $this->retry->shouldRetry($attempt, null)) {
                    break;
                }
            }

            usleep($this->retry->delayFor($attempt, crc32($key)) * 1000);
        }

        Log::warning('pipeline-bus: call failed', ['path' => $path, 'attempts' => $attempt, 'error' => $lastError]);

        return ['ok' => false, 'error' => $lastError, 'attempts' => $attempt, 'idempotency_key' => $key];
    }
}
