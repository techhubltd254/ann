<?php

namespace App\Pipeline;

use App\Pipeline\Stages\ContextResolver;
use App\Pipeline\Stages\CorrelationStage;
use App\Pipeline\Stages\ExperienceBuilder;
use App\Pipeline\Stages\ItineraryStage;
use App\Pipeline\Stages\BookingStage;
use App\Pipeline\Stages\ReceiptStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PipelineManager
{
    protected array $stages = [];
    protected array $results = [];
    protected array $config;

    public function __construct()
    {
        $this->config = config('experience.pipeline', ['stages' => ['context', 'correlation', 'builder', 'itinerary', 'booking', 'receipt']]);
        $this->registerStages();
    }

    protected function registerStages(): void
    {
        $map = [
            'context' => ContextResolver::class,
            'correlation' => CorrelationStage::class,
            'builder' => ExperienceBuilder::class,
            'itinerary' => ItineraryStage::class,
            'booking' => BookingStage::class,
            'receipt' => ReceiptStage::class,
        ];

        foreach ($this->config['stages'] as $name) {
            if (isset($map[$name])) {
                $this->stages[$name] = app($map[$name]);
            }
        }
    }

    public function run(string $startStage, mixed $anchor, Request $request = null): array
    {
        $context = [
            'anchor' => $anchor,
            'request' => $request,
            'county' => null,
            'coordinates' => null,
            'anchor_type' => null,
            'correlations' => [],
            'selections' => [],
            'itinerary' => null,
            'booking' => null,
            'receipt' => null,
            'errors' => [],
        ];

        $started = false;
        foreach ($this->stages as $name => $stage) {
            if ($name === $startStage) $started = true;
            if (!$started) continue;

            try {
                $context = $stage->handle($context);
                $this->results[$name] = $context;
            } catch (\Throwable $e) {
                $context['errors'][] = ['stage' => $name, 'message' => $e->getMessage()];
                Log::warning("Pipeline stage '{$name}' failed: " . $e->getMessage());
                if ($this->config['stop_on_failure'] ?? false) break;
            }
        }

        return $context;
    }

    public function runFrom(string $stage, array $context): array
    {
        $started = false;
        foreach ($this->stages as $name => $stageObj) {
            if ($name === $stage) $started = true;
            if (!$started) continue;
            try {
                $context = $stageObj->handle($context);
            } catch (\Throwable $e) {
                $context['errors'][] = ['stage' => $name, 'message' => $e->getMessage()];
                Log::warning("Pipeline stage '{$name}' failed: " . $e->getMessage());
                if ($this->config['stop_on_failure'] ?? false) break;
            }
        }
        return $context;
    }

    public function result(string $key = null): mixed
    {
        return $key ? ($this->results[$key] ?? null) : $this->results;
    }
}