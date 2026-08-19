<?php

namespace App\Services\Pipeline\Engines;

use App\Models\PipelineJob;
use App\Services\Pipeline\BaseEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Kling AI Engine — Kuaishou's video generation platform.
 *
 * Strengths: multi-shot storytelling, lip-sync/audio, complex camera motion,
 * up to 10-15s clips. Best for dynamic cinematic scenes with camera control.
 *
 * API: https://api.klingai.com/v1
 * Docs: https://docs.klingai.com
 */
class KlingEngine extends BaseEngine
{
    protected function name(): string
    {
        return 'kling';
    }

    public function availabilityNote(): ?string
    {
        if (! env('PIPELINE_KLING_ENABLED', false)) {
            return 'Kling AI is not enabled (PIPELINE_KLING_ENABLED).';
        }
        if (! env('KLING_API_KEY')) {
            return 'KLING_API_KEY is not set.';
        }
        return null;
    }

    public function run(PipelineJob $job): void
    {
        $apiKey = env('KLING_API_KEY');
        $baseUrl = env('KLING_BASE_URL', 'https://api.klingai.com/v1');
        $input = $this->resolveInputAsset($job);

        $this->updateProgress($job, 'submitting', 0.1);

        // Submit image-to-video task
        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post("$baseUrl/images/{$input->uuid}/video", [
                'image_url' => $input->url(),
                'prompt' => $job->options['prompt'] ?? 'Cinematic video from image',
                'duration' => $job->options['duration'] ?? 5,
                'camera_motion' => $job->options['camera_motion'] ?? 'pan',
                'style' => $job->options['style'] ?? 'cinematic',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Kling submission failed: ' . $response->body());
        }

        $taskId = $response->json('data.task_id');
        $this->updateProgress($job, 'processing', 0.2);

        // Poll for completion
        $maxPolls = 120;
        for ($i = 0; $i < $maxPolls; $i++) {
            sleep(5);
            $status = Http::withToken($apiKey)
                ->timeout(30)
                ->get("$baseUrl/tasks/$taskId")
                ->json();

            $state = $status['data']['status'] ?? 'unknown';
            $progress = $status['data']['progress'] ?? ($i / $maxPolls);

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

            if (in_array($state, ['failed', 'error'])) {
                throw new \RuntimeException('Kling generation failed: ' . ($status['data']['error'] ?? 'unknown'));
            }

            $this->updateProgress($job, 'processing', 0.2 + ($progress * 0.6));
        }

        throw new \RuntimeException('Kling generation timed out');
    }
}