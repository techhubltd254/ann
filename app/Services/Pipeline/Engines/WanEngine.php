<?php

namespace App\Services\Pipeline\Engines;

use App\Models\PipelineJob;
use App\Services\Pipeline\BaseEngine;
use Illuminate\Support\Facades\Http;

/**
 * Wan Video Engine — Alibaba's open-weight video generation model.
 *
 * Strengths: high prompt fidelity, multi-language text rendering,
 * local/API execution. Best for enterprise pipelines and self-hosted workflows.
 *
 * API: https://api.wan.video/v1
 * Self-hosted: ComfyUI / Diffusers with Wan2.1 weights
 */
class WanEngine extends BaseEngine
{
    protected function name(): string
    {
        return 'wan';
    }

    public function availabilityNote(): ?string
    {
        if (! env('PIPELINE_WAN_ENABLED', false)) {
            return 'Wan Video is not enabled (PIPELINE_WAN_ENABLED).';
        }
        if (! env('WAN_API_KEY')) {
            return 'WAN_API_KEY is not set.';
        }
        return null;
    }

    public function run(PipelineJob $job): void
    {
        $apiKey = env('WAN_API_KEY');
        $baseUrl = env('WAN_BASE_URL', 'https://api.wan.video/v1');
        $input = $this->resolveInputAsset($job);

        $this->updateProgress($job, 'submitting', 0.1);

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post("$baseUrl/generations", [
                'model' => 'wan2.1',
                'image_url' => $input->url(),
                'prompt' => $job->options['prompt'] ?? 'Cinematic video from image',
                'duration' => $job->options['duration'] ?? 5,
                'resolution' => $job->options['resolution'] ?? '1080p',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Wan submission failed: ' . $response->body());
        }

        $taskId = $response->json('data.id');
        $this->updateProgress($job, 'processing', 0.2);

        for ($i = 0; $i < 120; $i++) {
            sleep(5);
            $status = Http::withToken($apiKey)
                ->timeout(30)
                ->get("$baseUrl/generations/$taskId")
                ->json();

            $state = $status['data']['status'] ?? 'unknown';

            if ($state === 'completed') {
                $this->updateProgress($job, 'optimizing', 0.9);
                $outputUrl = $status['data']['output']['video_url'] ?? null;
                if ($outputUrl) {
                    $this->createOutputAsset($job, $outputUrl, 'video/mp4');
                    $this->markCompleted($job);
                    return;
                }
                break;
            }

            if ($state === 'failed') {
                throw new \RuntimeException('Wan generation failed');
            }

            $this->updateProgress($job, 'processing', 0.2 + (($i / 120) * 0.6));
        }

        throw new \RuntimeException('Wan generation timed out');
    }
}