<?php

namespace App\Services;

use App\Support\RetryPolicy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * IntegrationClient — calls the KICC Node.js Integration Layer.
 *
 * Handles payment processing, freight booking, customs declarations,
 * FX rates, and webhook forwarding. Falls back to mock responses
 * if the service is unreachable (same pattern as AlgorithmsClient).
 */
class IntegrationClient
{
    private string $baseUrl;
    private RetryPolicy $retry;

    public function __construct(?string $baseUrl = null, ?RetryPolicy $retry = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('kicc.integration_service_url', 'http://127.0.0.1:8787'), '/');
        $this->retry = $retry ?? new RetryPolicy(
            (int) config('kicc.integration.max_attempts', 3),
            (int) config('kicc.integration.base_delay_ms', 200),
            (int) config('kicc.integration.max_delay_ms', 5000),
        );
    }

    // ── Inter-pipeline automation ──

    /** The 87-pipeline dependency graph. */
    public function pipelineGraph(): array
    {
        return $this->call('GET', '/api/pipeline/graph');
    }

    /** Settle a pipeline and cascade into every dependent pipeline. */
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

    public function pipelineStatus(): array
    {
        return $this->call('GET', '/api/pipeline/status');
    }

    public function pipelineLedger(int $limit = 100): array
    {
        return $this->call('GET', '/api/pipeline/ledger?limit=' . $limit);
    }

    public function pipelineDlq(int $limit = 100): array
    {
        return $this->call('GET', '/api/pipeline/dlq?limit=' . $limit);
    }

    public function health(): array
    {
        return $this->call('GET', '/health');
    }

    // ── Payment ──

    public function paymentAuthorize(string $lane, float $amount, array $order = []): array
    {
        return $this->call('POST', '/api/payment/authorize', array_merge([
            'lane' => $lane,
            'amount' => $amount,
            'order' => $order,
        ]));
    }

    public function paymentCapture(string $ref, string $lane = 'card'): array
    {
        return $this->call('POST', '/api/payment/capture', [
            'ref' => $ref,
            'lane' => $lane,
        ]);
    }

    public function paymentRefund(string $ref, ?float $amount = null, string $lane = 'card'): array
    {
        return $this->call('POST', '/api/payment/refund', [
            'ref' => $ref,
            'amount' => $amount,
            'lane' => $lane,
        ]);
    }

    public function paymentVerify(string $ref, string $lane = 'card'): array
    {
        return $this->call('POST', '/api/payment/verify', [
            'ref' => $ref,
            'lane' => $lane,
        ]);
    }

    // ── Escrow ──

    public function escrowHold(array $params): array
    {
        return $this->call('POST', '/api/escrow/hold', $params);
    }

    public function escrowRelease(array $params): array
    {
        return $this->call('POST', '/api/escrow/release', $params);
    }

    // ── Freight ──

    public function freightQuote(string $lane, float $weightKg, array $params = []): array
    {
        return $this->call('POST', '/api/freight/quote', array_merge([
            'lane' => $lane,
            'weight_kg' => $weightKg,
        ], $params));
    }

    public function freightLabel(string $lane, array $params = []): array
    {
        return $this->call('POST', '/api/freight/label', array_merge([
            'lane' => $lane,
        ], $params));
    }

    public function freightTrack(string $awb, string $lane = 'ke-nairobi'): array
    {
        return $this->call('POST', '/api/freight/track', [
            'awb' => $awb,
            'lane' => $lane,
        ]);
    }

    public function freightPickup(string $lane, array $params = []): array
    {
        return $this->call('POST', '/api/freight/pickup', array_merge([
            'lane' => $lane,
        ], $params));
    }

    // ── Customs ──

    public function customsDeclare(array $params): array
    {
        return $this->call('POST', '/api/customs/declare', $params);
    }

    // ── FX ──

    public function fxRates(): array
    {
        return $this->call('GET', '/api/fx/rates');
    }

    // ── Payout / Settlement ──

    public function payout(array $params): array
    {
        return $this->call('POST', '/api/payout', $params);
    }

    public function settle(array $params = []): array
    {
        return $this->call('POST', '/api/settle', $params);
    }

    // ── Providers ──

    public function providers(): array
    {
        return $this->call('GET', '/api/providers');
    }

    // ── Low-level HTTP call ──

    /** Low-level call: retry + backoff + idempotency. */
    public function call(string $method, string $path, array $body = [], ?string $idempotencyKey = null): array
    {
        $key = $idempotencyKey ?? RetryPolicy::idempotencyKey($method . ' ' . $path, $body);
        $attempt = 0;
        $lastError = 'unknown';

        while (true) {
            $attempt++;
            try {
                $pending = Http::timeout((int) config('kicc.integration.timeout', 10))
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

        Log::warning('integration: call failed', ['path' => $path, 'attempts' => $attempt, 'error' => $lastError]);

        return ['ok' => false, 'error' => $lastError, 'attempts' => $attempt, 'mock' => true, 'idempotency_key' => $key];
    }
}
