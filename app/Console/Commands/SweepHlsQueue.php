<?php

namespace App\Console\Commands;

use App\Jobs\GenerateHlsJob;
use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use Illuminate\Console\Command;

class SweepHlsQueue extends Command
{
    protected $signature = 'media:sweep-hls {--limit=5 : Max jobs to dispatch per run}';

    protected $description = 'Dispatch HLS generation jobs for video assets still missing adaptive streams';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $processed = MediaDerivative::where('kind', 'hls_master')->pluck('media_asset_id');

        $assets = MediaAsset::where('kind', 'video')
            ->whereNotIn('id', $processed)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($assets->isEmpty()) {
            $this->info('No videos pending HLS. All caught up.');
            return 0;
        }

        foreach ($assets as $asset) {
            GenerateHlsJob::dispatch($asset->id);
            $this->line("  dispatched HLS job for asset {$asset->id} ({$asset->path})");
        }

        $this->info("Dispatched {$assets->count()} HLS jobs (video queue).");
        return 0;
    }
}