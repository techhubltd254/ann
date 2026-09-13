<?php

namespace App\Jobs;

use App\Models\MediaAsset;
use App\Services\MediaDerivativesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MediaDerivativesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;
    public $tries = 2;
    public $backoff = [60, 300];

    public function __construct(
        public int $assetId,
        public bool $force = false,
    ) {
        $this->queue = 'video';
    }

    public function handle(MediaDerivativesService $service): void
    {
        $asset = MediaAsset::find($this->assetId);
        if (!$asset) {
            Log::warning("MediaDerivativesJob: asset {$this->assetId} not found");
            return;
        }

        $created = $service->generate($asset, $this->force);

        if (empty($created)) {
            MediaAsset::where('id', $this->assetId)->update(['status' => 'failed']);
            Log::error("MediaDerivativesJob: failed to create any derivatives for asset {$this->assetId}");
            return;
        }

        if ($created) {
            Log::info("MediaDerivativesJob: created [" . implode(',', $created) . "] for asset {$this->assetId}");
        } else {
            Log::warning("MediaDerivativesJob: nothing created for asset {$this->assetId}");
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("MediaDerivativesJob: failed for asset {$this->assetId}: " . $e->getMessage());
    }
}