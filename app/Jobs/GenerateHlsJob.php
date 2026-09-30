<?php

namespace App\Jobs;

use App\Models\MediaAsset;
use App\Events\MediaPublished;
use App\Services\HlsGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateHlsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 2;
    public $backoff = [60, 300];

    public function __construct(
        public int $assetId,
        public bool $force = false,
    ) {
        $this->queue = 'video';
    }

    public function handle(HlsGenerator $generator): void
    {
        $asset = MediaAsset::find($this->assetId);
        if (!$asset) {
            Log::warning("GenerateHlsJob: asset {$this->assetId} not found");
            return;
        }

        $ok = $generator->generate($asset, $this->force);

        if ($ok) {
            Log::info("GenerateHlsJob: HLS ready for asset {$this->assetId} ({$asset->path})");
            event(new MediaPublished($this->assetId, 'video_hls', $asset->path));
        } else {
            Log::warning("GenerateHlsJob: HLS skipped/failed for asset {$this->assetId}");
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error("GenerateHlsJob: failed for asset {$this->assetId}: " . $e->getMessage());
    }
}