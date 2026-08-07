<?php

namespace App\Services\Pipeline\Engines;

use App\Models\PipelineJob;
use App\Services\Pipeline\BaseEngine;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Wan2GpEngine — local image-to-video diffusion (Wan 2.1 family).
 *
 * Runs the deepbeepmeep/Wan2GP python worker as a detached background
 * process on this server (or a designated GPU worker). The worker writes
 * status lines to a log file; we tail it to drive the progress bar.
 *
 * Env wiring (add to .env when the worker is installed):
 *   PIPELINE_WAN2GP_ENABLED=true
 *   WAN2GP_BIN=/opt/wan2gp/run.py
 *   WAN2GP_PYTHON=/opt/venv/bin/python
 *   WAN2GP_MODEL=Wan2.1-T2V-14B (or I2V-14B for image-to-video)
 */
class Wan2GpEngine extends BaseEngine
{
    protected function name(): string
    {
        return 'wan2gp';
    }

    public function availabilityNote(): ?string
    {
        if (!env('PIPELINE_WAN2GP_ENABLED', false)) {
            return 'Wan2GP is not enabled (PIPELINE_WAN2GP_ENABLED).';
        }
        if (!file_exists(env('WAN2GP_BIN', ''))) {
            return 'Wan2GP worker script not found (WAN2GP_BIN).';
        }

        return null;
    }

    public function run(PipelineJob $job): void
    {
        $source = $this->resolveInputAsset($job);
        $input = $this->localPath($source);

        $opts = $job->options ?? [];
        $bin = env('WAN2GP_BIN');
        $python = env('WAN2GP_PYTHON', 'python3');
        $model = $opts['model'] ?? env('WAN2GP_MODEL', 'Wan2.1-I2V-14B-480P');
        $prompt = $opts['prompt'] ?? 'Cinematic aerial drone shot, smooth camera motion, golden hour, ultra realistic';
        $seed = $opts['seed'] ?? random_int(0, 999999);

        $job->markRunning('wan2gp_generating');
        $slug = $source->uuid ?: Str::slug($source->original_name);
        $outDir = $this->outDir() . "/{$slug}_wan2gp";
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $logPath = storage_path("logs/pipeline_wan2gp_{$job->id}.log");
        $cmd = [
            $python, $bin,
            '--image', $input,
            '--prompt', $prompt,
            '--model', $model,
            '--seed', (string) $seed,
            '--outdir', $outDir,
        ];

        // Detached background run; the queue worker polls the log tail.
        $process = Process::timeout(0)->start($cmd, [
            'env' => array_merge($_ENV, ['PIPELINE_JOB_ID' => (string) $job->id]),
        ]);
        $job->setProgress(10, 'model_loaded');

        $prev = 0;
        $pollDeadline = now()->addMinutes((int) env('WAN2GP_MAX_MINUTES', 25));
        while (now()->lt($pollDeadline)) {
            $tail = $this->tailLog($logPath, $prev);
            $prev = $tail['pos'];

            foreach ($tail['lines'] as $line) {
                if (preg_match('/(\d{1,3})%/i', $line, $m)) {
                    $job->setProgress(max(15, min(90, (int) $m[1] + 10)), 'generating');
                }
            }

            if (glob($outDir . '/*.mp4') || glob($outDir . '/*.webm')) {
                break;
            }
            if ($process->waitUntil()) {
                break;
            }
            usleep(2_000_000);
        }

        $videos = array_merge(glob($outDir . '/*.mp4') ?: [], glob($outDir . '/*.webm') ?: []);
        if (empty($videos)) {
            throw new \RuntimeException('Wan2GP finished without producing a video file.');
        }

        $job->setProgress(95, 'storing');
        $videoFile = $videos[0];
        $rel = 'pipeline/' . basename($videoFile);
        copy($videoFile, storage_path('app/public/' . $rel));

        $output = $this->createOutputAsset($source, $rel, [
            'kind' => 'video',
            'metadata' => ['engine' => 'wan2gp', 'model' => $model, 'prompt' => $prompt],
        ]);

        $job->complete($output->id);
    }

    protected function tailLog(string $path, int $prevPos): array
    {
        $lines = [];
        if (file_exists($path)) {
            $size = filesize($path);
            $handle = fopen($path, 'r');
            if ($handle) {
                fseek($handle, min($prevPos, $size));
                while (($line = fgets($handle)) !== false) {
                    $lines[] = $line;
                }
                $prevPos = ftell($handle);
                fclose($handle);
            }
        }

        return ['pos' => $prevPos, 'lines' => $lines];
    }
}
