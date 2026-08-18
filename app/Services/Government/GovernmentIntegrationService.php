<?php

namespace App\Services\Government;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Government API integration base — all ministry integrations extend this.
 * When the API key/endpoint is not configured, the service gracefully
 * falls back to cached/stub data so the platform never hard-fails.
 */
abstract class GovernmentIntegrationService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $serviceName;

    public function __construct(string $serviceName, string $configKey)
    {
        $this->serviceName = $serviceName;
        $this->baseUrl = config("services.$configKey.url", '');
        $this->apiKey = config("services.$configKey.key");
    }

    protected function available(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->apiKey);
    }

    protected function get(string $endpoint, array $params = []): ?array
    {
        if (! $this->available()) {
            Log::info("{$this->serviceName} API not configured, using fallback");
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(15)
                ->retry(2, 100)
                ->get("{$this->baseUrl}/{$endpoint}", $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("{$this->serviceName} API error", [
                'status' => $response->status(),
                'endpoint' => $endpoint,
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::warning("{$this->serviceName} API unavailable", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function post(string $endpoint, array $data = []): ?array
    {
        if (! $this->available()) {
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->retry(2, 100)
                ->post("{$this->baseUrl}/{$endpoint}", $data);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("{$this->serviceName} API unavailable", ['error' => $e->getMessage()]);
            return null;
        }
    }
}