<?php

namespace App\Console\Commands;

use App\Jobs\MediaDerivativesJob;
use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use Illuminate\Console\Command;

class SweepMediaDerivatives extends Command
{
    protected $signature = 'media:sweep-derivatives {--limit=5 : Max jobs to dispatch per run}';

    protected $description = 'Dispatch Tier-1/Tier-2 (poster + hover loop) generation for videos missing them';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $hasPoster = MediaDerivative::where('kind', 'poster')->pluck('media_asset_id');

        $assets = MediaAsset::where('kind', 'video')
            ->whereNotIn('id', $hasPoster)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($assets->isEmpty()) {
            $this->info('No videos pending Tier-1/Tier-2 derivatives. All caught up.');
            return 0;
        }

        foreach ($assets as $asset) {
            MediaDerivativesJob::dispatch($asset->id);
            $this->line("  dispatched derivatives job for asset {$asset->id} ({$asset->path})");
        }

        $this->info("Dispatched {$assets->count()} derivative jobs (video queue).");
        return 0;
    }
}