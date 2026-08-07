<?php

namespace App\Services\Pipeline\Engines;

use App\Models\PipelineJob;
use App\Services\Pipeline\BaseEngine;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * HailuoEngine — Hailuo AI / Minimax image-to-video generation.
 *
 * Submits the image, then polls the task endpoint until completion, mapping
 * API status to the DB progress bar. Webhook delivery is optional; polling
 * keeps the flow dependency-free (works behind any network).
 *
 * Env wiring:
 *   PIPELINE_HAILUO_ENABLED=true
 *   HAILUO_API_KEY=...
 *   HAILUO_GROUP_ID=...
 */
class HailuoEngine extends BaseEngine
{
    protected function name(): string
    {
        return 'hailuo';
    }

    public function availabilityNote(): ?string
    {
        if (!env('PIPELINE_HAILUO_ENABLED', false)) {
            return 'Hailuo is not enabled (PIPELINE_HAILUO_ENABLED).';
        }
        if (!env('HAILUO_API_KEY')) {
            return 'HAILUO_API_KEY is not set.';
        }

        return null;
    }

    public function run(PipelineJob $job): void
    {
        $source = $this->resolveInputAsset($job);
        $input = $this->localPath($source);
        $opts = $job->options ?? [];

        $job->markRunning('hailuo_submitting');
        $provider = config('pipeline.providers.hailuo');
        $base = rtrim($provider['base_url'], '/');
        $group = $provider['group_id'];

        $endpoint = $group
            ? "{$base}/v1/video_generation/{$group}"
            : "{$base}/v1/video_generation";

        // 1. Submit image-to-video task
        $submit = Http::withHeaders([
            'Authorization' => 'Bearer ' . $provider['api_key'],
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'model' => $opts['model'] ?? 'MiniMax-Hailuo-01',
            'prompt' => $opts['prompt'] ?? 'Cinematic camera move, depth of field, realistic lighting',
            'prompt_optimizer' => true,
            'subject_reference' => [
                'type' => 'image',
                'image_file' => $this->uploadImage($provider['api_key'], $input),
            ],
            'first_frame_image' => $this->dataUri($input),
            'duration' => (int) ($opts['duration'] ?? 6),
            'aspect_ratio' => $opts['aspect_ratio'] ?? '16:9',
            'callback_url' => $provider['webhook_url'],
        ]);

        if ($submit->failed()) {
            throw new \RuntimeException('Hailuo submit failed: ' . $submit->body());
        }

        $taskId = $submit->json('task_id') ?? $submit->json('data.task_id');
        if (!$taskId) {
            throw new \RuntimeException('Hailuo did not return a task_id.');
        }

        $job->setProgress(10, 'submitted');

        // 2. Poll until done (2s interval, max 10 min)
        $poll = rtrim(config('pipeline.providers.hailuo.base_url'), '/');
        $query = $group ? "?GroupId={$group}" : '';

        for ($i = 0; $i < 300; $i++) {
            sleep(2);
            $status = Http::withHeaders(['Authorization' => 'Bearer ' . $provider['api_key']])
                ->get("{$poll}/v1/query/video_generation?task_id={$taskId}{$query}")
                ->json();

            $state = strtolower((string) ($status['status'] ?? $status['data']['status'] ?? ''));

            if ($state === 'succeed' || $state === 'success') {
                break;
            }
            if ($state === 'fail' || $state === 'failed' || $state === 'cancelled') {
                throw new \RuntimeException('Hailuo task failed: ' . json_encode($status));
            }

            $job->setProgress(15 + min(80, (int) floor($i * 0.28)), 'generating');
        }

        // 3. Download result
        $fileUrl = $status['file_id']
            ? "{$base}/v1/files/retrieve?file_id=" . $status['file_id'] . ($group ? "&GroupId={$group}" : '')
            : ($status['data']['video_url'] ?? null);

        if (!$fileUrl) {
            throw new \RuntimeException('Hailuo completed but returned no video URL.');
        }

        $job->setProgress(92, 'downloading');
        $rel = 'pipeline/' . $source->uuid . '_hailuo.mp4';
        $this->downloadTo($fileUrl, storage_path('app/public/' . $rel), $provider['api_key'], $group);

        $output = $this->createOutputAsset($source, $rel, [
            'kind' => 'video',
            'metadata' => ['engine' => 'hailuo', 'task_id' => $taskId],
        ]);

        $job->setProgress(100, 'finalizing');
        $job->complete($output->id);
    }

    protected function dataUri(string $path): string
    {
        return 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($path));
    }

    protected function uploadImage(string $apiKey, string $path): string
    {
        $up = Http::withHeaders(['Authorization' => 'Bearer ' . $apiKey])
            ->attach('file', file_get_contents($path), basename($path))
            ->post('https://api.minimax.chat/v1/files/upload', [
                'purpose' => 'generation',
            ]);

        return $up->json('file.file_id') ?? $up->json('file_id') ?? '';
    }

    protected function downloadTo(string $url, string $dst, string $apiKey, ?string $group): void
    {
        $headers = ['Authorization' => 'Bearer ' . $apiKey];
        $target = str_starts_with($url, 'http') ? $url : 'https://api.minimax.chat/v1/files/retrieve?file_id=' . $url . ($group ? "&GroupId={$group}" : '');

        $resp = Http::withHeaders($headers)->timeout(300)->get($target);
        if ($resp->failed()) {
            throw new \RuntimeException('Failed downloading Hailuo result: ' . $resp->status());
        }
        file_put_contents($dst, $resp->body());
    }
}
