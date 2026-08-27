<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use App\Services\HlsGenerator;
use Illuminate\Console\Command;

class GenerateHls extends Command
{
    protected $signature = 'media:generate-hls
        {--asset= : Specific MediaAsset ID to process}
        {--all : Process all video assets without HLS}
        {--force : Regenerate even if HLS exists}';

    protected $description = 'Generate HLS adaptive streaming segments for video assets (YouTube-style)';

    public function handle(HlsGenerator $generator): int
    {
        $query = MediaAsset::where('kind', 'video');

        if ($assetId = $this->option('asset')) {
            $query->where('id', $assetId);
        }

        if (!$this->option('force')) {
            $processed = MediaDerivative::where('kind', 'hls_master')->pluck('media_asset_id');
            $query->whereNotIn('id', $processed);
        }

        $assets = $query->get();
        $this->info("Found {$assets->count()} video assets to process");

        $count = 0;
        foreach ($assets as $asset) {
            $this->line("  [{$asset->id}] {$asset->path}...");

            try {
                $result = $generator->generate($asset, (bool) $this->option('force'));
                if ($result) {
                    $count++;
                    $this->info("    ✅ HLS generated");
                } else {
                    $this->warn("    ⚠️  Skipped");
                }
            } catch (\Throwable $e) {
                $this->error("    ❌ {$e->getMessage()}");
            }
        }

        $this->info("Done. {$count} HLS streams created.");
        return 0;
    }
}