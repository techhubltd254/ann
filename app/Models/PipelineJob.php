<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineJob extends Model
{
    protected $table = 'media_pipeline_jobs';

    protected $fillable = [
        'uuid', 'media_asset_id', 'pipeline', 'engine', 'status', 'progress',
        'stage', 'options', 'output_asset_id', 'error', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'json',
            'progress' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function output(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'output_asset_id');
    }

    public function markRunning(string $stage): void
    {
        $this->forceFill([
            'status' => 'running',
            'stage' => $stage,
            'started_at' => $this->started_at ?? now(),
        ])->save();
    }

    public function setProgress(int $progress, ?string $stage = null): void
    {
        $this->forceFill([
            'progress' => min(99, max(0, $progress)),
            'stage' => $stage ?? $this->stage,
        ])->save();
    }

    public function complete(?int $outputAssetId = null): void
    {
        $this->forceFill([
            'status' => 'completed',
            'progress' => 100,
            'stage' => 'complete',
            'output_asset_id' => $outputAssetId,
            'finished_at' => now(),
        ])->save();

        if ($this->asset) {
            $this->asset->forceFill(['status' => 'ready'])->save();
        }

        if ($outputAssetId && $this->output) {
            $this->output->forceFill(['status' => 'ready'])->save();
        }
    }

    public function fail(\Throwable $e): void
    {
        $this->forceFill([
            'status' => 'failed',
            'stage' => 'error',
            'error' => $e->getMessage(),
            'finished_at' => now(),
        ])->save();
    }
}
