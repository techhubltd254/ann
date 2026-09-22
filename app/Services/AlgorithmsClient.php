<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AlgorithmsClient — calls the Python KICC Algorithms microservice.
 *
 * Falls back to local PHP computation if the service is unreachable.
 * This ensures the admin pages never 500 when the Python service
 * is restarting or scaling.
 */
class AlgorithmsClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('kicc.algorithms_service_url', 'http://127.0.0.1:8400'), '/');
    }

    public function quality(float $deliveryRate, float $adverseRate, string $trustGrade,
                            float $completeness, float $avgReview, int $mediaTier): array
    {
        return $this->call('/quality', [
            'delivery_rate' => $deliveryRate,
            'adverse_rate'  => $adverseRate,
            'trust_grade'   => $trustGrade,
            'completeness'  => $completeness,
            'avg_review'    => $avgReview,
            'media_tier'    => $mediaTier,
        ], function () use ($deliveryRate, $adverseRate, $trustGrade, $completeness, $avgReview, $mediaTier) {
            return app(\App\Services\Pool\QualityScorer::class)->score(
                $deliveryRate, $adverseRate, $trustGrade, $completeness, $avgReview, $mediaTier
            );
        });
    }

    public function kyb(bool $phoneVerified, ?string $kraPin = null, bool $pinValidated = false, bool $assetVerified = false): array
    {
        return $this->call('/kyb', [
            'phone_verified' => $phoneVerified,
            'kra_pin'       => $kraPin,
            'pin_validated'  => $pinValidated,
            'asset_verified' => $assetVerified,
        ], fn () => app(\App\Services\Onboarding\KybService::class)->evaluate(
            $phoneVerified, $kraPin, $pinValidated, $assetVerified
        ));
    }

    public function screen(string $name): array
    {
        return $this->call('/screen', ['name' => $name], fn () => app(\App\Services\Onboarding\ScreeningService::class)->screen($name));
    }

    public function classify(string $name, array $params = []): array
    {
        $body = array_merge(['name' => $name], $params);
        return $this->call('/classify', $body, function () use ($params) {
            $county = \App\Models\County::where('name', $params['name'] ?? '')->first();
            return $county ? app(\App\Services\CountyClassificationService::class)->classify($county) : ['quadrant' => 'unclassified'];
        });
    }

    public function distributePool(array $contributions, array $qualities = []): array
    {
        return $this->call('/pool/distribute', [
            'contributions' => $contributions,
            'qualities'     => $qualities,
        ], fn () => []);
    }

    public function anonymize(array $rows, string $groupByKey = 'county_id', int $k = 5): array
    {
        return $this->call('/anonymize', [
            'rows'         => $rows,
            'group_by_key' => $groupByKey,
            'k'            => $k,
        ], fn () => $rows);
    }

    private function call(string $path, array $body, \Closure $fallback): array
    {
        try {
            $response = Http::timeout(5)->post($this->baseUrl . $path, $body);
            if ($response->successful()) {
                return $response->json();
            }
            Log::warning('algorithms: service returned ' . $response->status(), ['path' => $path]);
        } catch (\Throwable $e) {
            Log::warning('algorithms: service unreachable', ['path' => $path, 'error' => $e->getMessage()]);
        }
        return $fallback();
    }
}