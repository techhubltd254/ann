<?php

namespace App\Services\Pipeline;

use App\Jobs\RunPipelineJob;
use App\Models\MediaAsset;
use App\Models\PipelineJob;
use Illuminate\Support\Str;

/**
 * PipelineService — orchestrator between admin UI, engine registry and queue.
 *
 * Design rules baked in:
 *  - Hick's Law: only enabled engines are offered; recommended engine first.
 *  - No blocking: dispatch is always async via the queue; UI polls status.
 *  - No hardcoded media: everything flows through a MediaAsset row.
 */
class PipelineService
{
    public function engines(string $pipeline): array
    {
        $enabled = collect(config('pipeline.engines', []))
            ->filter(fn ($e) => ($e['enabled'] ?? false) && in_array($pipeline, $e['pipeline'] ?? [], true))
            ->map(fn ($e, $key) => [
                'key' => $key,
                ...$e,
            ]);

        // Recommended = zero-cost local first (Jakob's Law: predictable default).
        return $enabled
            ->sortBy(fn ($e) => $e['cost'] === 'free' ? 0 : 1)
            ->values()
            ->all();
    }

    public function allEngineDefinitions(): array
    {
        return config('pipeline.engines', []);
    }

    public function engineInstance(string $engine): PipelineEngineContract
    {
        $class = data_get(config('pipeline.engines'), "{$engine}.class");
        if (!$class || !class_exists($class)) {
            throw new \RuntimeException("Pipeline engine '{$engine}' is not registered.");
        }

        return app($class);
    }

    public function dispatch(MediaAsset $asset, string $pipeline, string $engine, array $options = []): PipelineJob
    {
        $engineDef = data_get(config('pipeline.engines'), $engine);
        if (!$engineDef || !($engineDef['enabled'] ?? false)) {
            throw new \RuntimeException("Engine '{$engine}' is not enabled.");
        }
        if (!in_array($pipeline, $engineDef['pipeline'] ?? [], true)) {
            throw new \RuntimeException("Engine '{$engine}' does not support pipeline '{$pipeline}'.");
        }

        $job = PipelineJob::create([
            'uuid' => (string) Str::uuid(),
            'media_asset_id' => $asset->id,
            'pipeline' => $pipeline,
            'engine' => $engine,
            'status' => 'queued',
            'options' => $options,
        ]);

        RunPipelineJob::dispatch($job->id)->onQueue('pipeline');

        return $job;
    }

    public function status(PipelineJob $job): array
    {
        return [
            'id' => $job->id,
            'uuid' => $job->uuid,
            'pipeline' => $job->pipeline,
            'engine' => $job->engine,
            'status' => $job->status,
            'progress' => $job->progress,
            'stage' => $job->stage,
            'error' => $job->error,
            'started_at' => $job->started_at?->toIso8601String(),
            'finished_at' => $job->finished_at?->toIso8601String(),
        ];
    }

    public function cancel(PipelineJob $job): PipelineJob
    {
        if (in_array($job->status, ['queued', 'running'], true)) {
            $job->forceFill(['status' => 'cancelled', 'finished_at' => now()])->save();
        }

        return $job;
    }
}
