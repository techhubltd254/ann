<?php

namespace App\Services;

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

    public function __construct()
    {
        $this->baseUrl = rtrim(config('kicc.integration_service_url', 'http://127.0.0.1:8787'), '/');
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

    private function call(string $method, string $path, array $body = []): array
    {
        try {
            $response = $method === 'GET'
                ? Http::timeout(5)->get($this->baseUrl . $path)
                : Http::timeout(10)->post($this->baseUrl . $path, $body);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('integration: service returned ' . $response->status(), [
                'path' => $path,
                'body' => $body,
            ]);

            return ['ok' => false, 'error' => "HTTP {$response->status()}", 'mock' => true];
        } catch (\Throwable $e) {
            Log::warning('integration: service unreachable', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'service unreachable', 'mock' => true];
        }
    }
}