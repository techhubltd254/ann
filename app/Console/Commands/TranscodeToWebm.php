<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Convert uploaded MP4 videos to WebM (VP9) for lighter, browser-friendly playback.
 *
 * Browses all MediaAssets of kind 'video' that lack a 'video_webm' derivative
 * and generates one using ffmpeg with the VP9 codec (~30% smaller than H.264).
 *
 * Usage: php artisan media:transcode-webm
 *        php artisan media:transcode-webm --asset=30022
 *        php artisan media:transcode-webm --all
 */
class TranscodeToWebm extends Command
{
    protected $signature = 'media:transcode-webm {--asset= : Only process a specific MediaAsset ID}
                                                  {--all : Reprocess all video assets even if webm exists}';

    protected $description = 'Generate WebM (VP9) derivatives for all video MediaAssets';

    public function handle(): int
    {
        $query = MediaAsset::where('kind', 'video');

        if ($assetId = $this->option('asset')) {
            $query->where('id', $assetId);
        }

        if (!$this->option('all')) {
            $processed = MediaDerivative::where('kind', 'video_webm')->pluck('media_asset_id');
            $query->whereNotIn('id', $processed);
        }

        $assets = $query->get();
        $this->info("Found {$assets->count()} video assets to process");

        $count = 0;
        foreach ($assets as $asset) {
            $this->line("  [{$asset->id}] {$asset->path}...");

            try {
                $result = $this->transcode($asset);
                if ($result) {
                    $count++;
                    $this->info("    ✅ WebM created");
                } else {
                    $this->warn("    ⚠️  Skipped (no source or already exists)");
                }
            } catch (\Throwable $e) {
                $this->error("    ❌ {$e->getMessage()}");
            }
        }

        $this->info("Done. {$count} WebM derivatives created.");
        return 0;
    }

    private function transcode(MediaAsset $asset): bool
    {
        // Check if WebM already exists
        if ($asset->derivatives()->where('kind', 'video_webm')->exists()) {
            return false;
        }

        $disk = Storage::disk($asset->disk);
        $sourcePath = $asset->path;

        // Build the WebM filename
        $info = pathinfo($sourcePath);
        $webmPath = $info['dirname'] . '/' . $info['filename'] . '.webm';

        // For R2/S3 disks, download to temp first
        if ($asset->disk === 'r2') {
            $tempSource = tempnam(sys_get_temp_dir(), 'webm_') . '.mp4';
            $tempWebm = tempnam(sys_get_temp_dir(), 'webm_') . '.webm';

            $stream = $disk->readStream($sourcePath);
            if (!$stream) {
                $this->warn("    Cannot read source from R2");
                return false;
            }
            file_put_contents($tempSource, stream_get_contents($stream));
            fclose($stream);
        } else {
            $tempSource = $disk->path($sourcePath);
            $tempWebm = tempnam(sys_get_temp_dir(), 'webm_') . '.webm';
        }

        // Transcode to WebM with VP9 codec
        $ffmpeg = "ffmpeg -y -i " . escapeshellarg($tempSource) . " " .
            "-c:v libvpx-vp9 -b:v 0 -crf 30 -deadline good -cpu-used 2 " .
            "-c:a libopus -b:a 64k " .
            "-vf 'scale=min(1280,iw):min(720,ih):force_original_aspect_ratio=decrease' " .
            escapeshellarg($tempWebm) . " 2>/dev/null";

        $output = null;
        $returnCode = null;
        exec($ffmpeg, $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($tempWebm)) {
            @unlink($tempSource);
            @unlink($tempWebm);
            $this->warn("    ffmpeg failed (exit code {$returnCode})");
            return false;
        }

        // Upload WebM to the same disk
        $webmSize = filesize($tempWebm);
        $webmStream = fopen($tempWebm, 'r');
        $disk->writeStream($webmPath, $webmStream, ['visibility' => 'public']);
        fclose($webmStream);

        // Cleanup temp files
        if ($asset->disk === 'r2') {
            @unlink($tempSource);
        }
        @unlink($tempWebm);

        // Create derivative record
        $asset->derivatives()->create([
            'kind' => 'video_webm',
            'path' => $webmPath,
            'mime' => 'video/webm',
            'size_bytes' => $webmSize,
            'variant' => '720p',
        ]);

        return true;
    }
}