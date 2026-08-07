<?php

namespace App\Services\Pipeline\Engines;

use App\Models\PipelineJob;
use App\Services\Pipeline\BaseEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Tripo3dEngine — image-to-3D mesh via the Tripo3D API (tripo3d.ai).
 *
 * Returns a .glb; post-processed by the WebOptimizer (Draco compression,
 * LOD variants) before being marked ready.
 *
 * Env wiring:
 *   PIPELINE_TRIPO3D_ENABLED=true
 *   TRIPO3D_API_KEY=...
 */
class Tripo3dEngine extends BaseEngine
{
    protected function name(): string
    {
        return 'tripo3d';
    }

    public function availabilityNote(): ?string
    {
        if (!env('PIPELINE_TRIPO3D_ENABLED', false)) {
            return 'Tripo3D is not enabled (PIPELINE_TRIPO3D_ENABLED).';
        }
        if (!env('TRIPO3D_API_KEY')) {
            return 'TRIPO3D_API_KEY is not set.';
        }

        return null;
    }

    public function run(PipelineJob $job): void
    {
        $source = $this->resolveInputAsset($job);
        $input = $this->localPath($source);
        $opts = $job->options ?? [];

        $job->markRunning('tripo3d_submitting');
        $provider = config('pipeline.providers.tripo3d');
        $base = rtrim($provider['base_url'], '/');
        $headers = ['Authorization' => 'Bearer ' . $provider['api_key'], 'Content-Type' => 'application/json'];

        // 1. Submit image-to-3D task
        $submit = Http::withHeaders($headers)->post("{$base}/openapi/tasks", [
            'type' => 'text_to_model',
            'data' => [
                'prompt' => $opts['prompt'] ?? 'A detailed 3D model of the subject in the reference image',
                'image' => $this->dataUri($input),
                'texture' => 'quilt',
                'pbr' => false,
            ],
        ]);

        if ($submit->failed()) {
            throw new \RuntimeException('Tripo3D submit failed: ' . $submit->body());
        }

        $taskId = $submit->json('data.task_id');
        if (!$taskId) {
            throw new \RuntimeException('Tripo3D did not return a task_id.');
        }

        $job->setProgress(10, 'submitted');

        // 2. Poll task status
        for ($i = 0; $i < 180; $i++) {
            sleep(5);
            $status = Http::withHeaders($headers)
                ->get("{$base}/openapi/tasks/{$taskId}")
                ->json('data');

            $state = strtolower((string) ($status['status'] ?? ''));
            if ($state === 'succeeded' || $state === 'success') {
                break;
            }
            if (in_array($state, ['failed', 'cancelled', 'expired'], true)) {
                throw new \RuntimeException('Tripo3D task failed: ' . json_encode($status));
            }
            $job->setProgress(15 + min(70, (int) floor($i * 0.4)), 'generating');
        }

        // 3. Download the .glb
        $glbUrl = $status['output']['model_url']
            ?? $status['output']['file']
            ?? $status['data']['output']['model_url']
            ?? null;

        if (!$glbUrl) {
            throw new \RuntimeException('Tripo3D completed but returned no model URL.');
        }

        $job->setProgress(88, 'downloading');
        $glb = Http::timeout(300)->get($glbUrl);
        if ($glb->failed()) {
            throw new \RuntimeException('Failed downloading Tripo3D model.');
        }

        $rel = 'pipeline/' . $source->uuid . '_model.glb';
        file_put_contents(storage_path('app/public/' . $rel), $glb->body());

        $output = $this->createOutputAsset($source, $rel, [
            'kind' => 'model',
            'metadata' => ['engine' => 'tripo3d', 'task_id' => $taskId],
        ]);

        $job->setProgress(95, 'finalizing');
        $job->complete($output->id);
    }

    protected function dataUri(string $path): string
    {
        return 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path));
    }
}
