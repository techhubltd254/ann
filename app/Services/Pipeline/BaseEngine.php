<?php

namespace App\Services\Pipeline;

use App\Models\MediaAsset;
use App\Models\PipelineJob;

abstract class BaseEngine implements PipelineEngineContract
{
    public function available(): bool
    {
        return $this->availabilityNote() === null;
    }

    abstract public function availabilityNote(): ?string;

    public function supportedPipelines(): array
    {
        return config("pipeline.engines." . $this->name() . ".pipeline", []);
    }

    abstract protected function name(): string;

    protected function resolveInputAsset(PipelineJob $job): MediaAsset
    {
        $asset = $job->asset;
        if (!$asset) {
            throw new \RuntimeException('Pipeline job has no source media asset.');
        }

        return $asset;
    }

    protected function localPath(MediaAsset $asset): string
    {
        $disk = $asset->disk === 'public' ? \Illuminate\Support\Facades\Storage::disk('public') : \Illuminate\Support\Facades\Storage::disk($asset->disk);
        $path = $disk->path($asset->path);

        if (!file_exists($path)) {
            throw new \RuntimeException("Source file missing on disk: {$asset->path}");
        }

        return $path;
    }

    protected function outDir(): string
    {
        $dir = storage_path('app/public/pipeline');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    protected function createOutputAsset(MediaAsset $source, string $relativePath, array $attrs = []): MediaAsset
    {
        $abs = storage_path('app/public/' . ltrim($relativePath, '/'));

        return MediaAsset::create(array_merge([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'owner_id' => $source->owner_id,
            'owner_type' => $source->owner_type,
            'disk' => 'public',
            'path' => $relativePath,
            'original_name' => basename($relativePath),
            'mime' => mime_content_type($abs) ?: 'application/octet-stream',
            'kind' => $attrs['kind'] ?? 'video',
            'size_bytes' => file_exists($abs) ? filesize($abs) : 0,
            'status' => 'uploaded',
            'metadata' => ['source_asset_id' => $source->id],
        ], $attrs));
    }
}
