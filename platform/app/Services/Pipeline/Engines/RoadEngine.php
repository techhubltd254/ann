<?php

namespace App\Services\Pipeline\Engines;

use App\Models\PipelineJob;
use App\Services\Pipeline\BaseEngine;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * RoadEngine — local image-to-3D via the ROAD repo (github.com/h-embodvis/road).
 *
 * Same pattern as Wan2GP: detached python worker, log-tail progress.
 *
 * Env wiring:
 *   PIPELINE_ROAD_ENABLED=true
 *   ROAD_BIN=/opt/road/run.py
 *   ROAD_PYTHON=/opt/venv/bin/python
 */
class RoadEngine extends BaseEngine
{
    protected function name(): string
    {
        return 'road';
    }

    public function availabilityNote(): ?string
    {
        if (!env('PIPELINE_ROAD_ENABLED', false)) {
            return 'ROAD is not enabled (PIPELINE_ROAD_ENABLED).';
        }
        if (!file_exists(env('ROAD_BIN', ''))) {
            return 'ROAD worker script not found (ROAD_BIN).';
        }

        return null;
    }

    public function run(PipelineJob $job): void
    {
        $source = $this->resolveInputAsset($job);
        $input = $this->localPath($source);
        $opts = $job->options ?? [];

        $job->markRunning('road_generating');
        $slug = $source->uuid ?: Str::slug($source->original_name);
        $outDir = $this->outDir() . "/{$slug}_road";
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $cmd = [
            env('ROAD_PYTHON', 'python3'),
            env('ROAD_BIN'),
            '--image', $input,
            '--outdir', $outDir,
            '--iters', (string) ($opts['iterations'] ?? 5000),
        ];

        $result = Process::timeout((int) env('ROAD_MAX_SECONDS', 1500))->run($cmd);
        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('ROAD failed: ' . $result->errorOutput());
        }

        $job->setProgress(80, 'generating');

        $meshes = array_merge(glob($outDir . '/*.glb') ?: [], glob($outDir . '/*.obj') ?: []);
        if (empty($meshes)) {
            throw new \RuntimeException('ROAD finished without producing a mesh.');
        }

        $job->setProgress(90, 'storing');
        $rel = 'pipeline/' . $slug . '_road.glb';
        copy($meshes[0], storage_path('app/public/' . $rel));

        $output = $this->createOutputAsset($source, $rel, [
            'kind' => 'model',
            'metadata' => ['engine' => 'road'],
        ]);

        $job->complete($output->id);
    }
}
