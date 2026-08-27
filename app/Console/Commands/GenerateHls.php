<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateHls extends Command
{
    protected $signature = 'media:generate-hls
        {--asset= : Specific MediaAsset ID to process}
        {--all : Process all video assets without HLS}
        {--force : Regenerate even if HLS exists}';

    protected $description = 'Generate HLS adaptive streaming segments for video assets (YouTube-style)';

    public function handle(): int
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
                $result = $this->generateHls($asset);
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

    private function generateHls(MediaAsset $asset): bool
    {
        if ($asset->derivatives()->where('kind', 'hls_master')->exists() && !$this->option('force')) {
            return false;
        }

        $disk = Storage::disk($asset->disk);
        $sourcePath = $asset->path;
        $info = pathinfo($sourcePath);
        $hlsDir = $info['dirname'] . '/hls/' . $info['filename'];

        $tempSource = tempnam(sys_get_temp_dir(), 'hls_') . '.mp4';
        $tempOut = sys_get_temp_dir() . '/hls_output_' . Str::random(8);

        // Download source to temp (streamed — files can be hundreds of MB)
        if ($asset->disk === 'r2') {
            $stream = $disk->readStream($sourcePath);
            if (!$stream) {
                $this->warn("    Cannot read source from R2");
                return false;
            }
            $fh = fopen($tempSource, 'wb');
            if (!$fh) {
                fclose($stream);
                return false;
            }
            while (!feof($stream)) {
                fwrite($fh, fread($stream, 1024 * 1024));
            }
            fclose($fh);
            fclose($stream);
        } else {
            $tempSource = $disk->path($sourcePath);
        }

        $tempOut = sys_get_temp_dir() . '/hls_output_' . Str::random(8);
        mkdir($tempOut, 0755, true);

        $masterPath = $hlsDir . '/master.m3u8';
        $masterTemp = $tempOut . '/master.m3u8';

        // Detect audio track — var_stream_map must not reference a:0 when absent
        $hasAudio = false;
        exec('ffprobe -v error -select_streams a -show_entries stream=index -of csv=p=0 ' . escapeshellarg($tempSource), $audioOut, $audioCode);
        $hasAudio = $audioCode === 0 && !empty(array_filter($audioOut));
        $audioOpts = $hasAudio
            ? '-c:a aac -b:a 128k -var_stream_map "v:0,a:0 v:1,a:1 v:2,a:2 v:3,a:3"'
            : '-an -var_stream_map "v:0 v:1 v:2 v:3"';

        // Generate HLS with 4 quality levels using ffmpeg
        // 1080p, 720p, 480p, 360p — adaptive bitrate ladder (low-first for weak networks)
        $cmd = sprintf(
            'ffmpeg -y -i %s ' .
            '-filter_complex ' .
            '"[0:v]split=4[v360][v480][v720][v1080];' .
            '[v360]scale=-2:360,format=yuv420p[v360out];' .
            '[v480]scale=-2:480,format=yuv420p[v480out];' .
            '[v720]scale=-2:720,format=yuv420p[v720out];' .
            '[v1080]scale=-2:1080,format=yuv420p[v1080out]" ' .
            '-map "[v360out]" -map 0:a? -c:v:0 libx264 -profile:v:0 main -pix_fmt:0 yuv420p -b:v:0 400k -maxrate:v:0 500k -bufsize:v:0 800k ' .
            '-map "[v480out]" -map 0:a? -c:v:1 libx264 -profile:v:1 main -pix_fmt:1 yuv420p -b:v:1 800k -maxrate:v:1 1000k -bufsize:v:1 1600k ' .
            '-map "[v720out]" -map 0:a? -c:v:2 libx264 -profile:v:2 main -pix_fmt:2 yuv420p -b:v:2 2500k -maxrate:v:2 3200k -bufsize:v:2 5000k ' .
            '-map "[v1080out]" -map 0:a? -c:v:3 libx264 -profile:v:3 high -pix_fmt:3 yuv420p -b:v:3 5000k -maxrate:v:3 6500k -bufsize:v:3 10000k ' .
            '%s ' .
            '-f hls -hls_time 4 -hls_playlist_type vod ' .
            '-hls_segment_type fmp4 ' .
            '-master_pl_name master.m3u8 ' .
            '-strftime_mkdir 1 ' .
            '-hls_segment_filename "%s/v%%v/seg_%%03d.m4s" ' .
            '-hls_flags independent_segments ' .
            '%s/v%%v/playlist.m3u8',
            escapeshellarg($tempSource),
            $audioOpts,
            escapeshellarg($tempOut),
            escapeshellarg($tempOut)
        );

        $output = null;
        $returnCode = null;
        exec($cmd . ' 2>/dev/null', $output, $returnCode);

        if ($returnCode !== 0 || !file_exists($masterTemp)) {
            $this->cleanup($tempSource, $tempOut, $asset->disk === 'r2');
            $this->warn("    ffmpeg failed (exit code {$returnCode})");
            return false;
        }

        // Upload HLS files to R2
        $this->uploadDir($tempOut, $hlsDir, $disk);

        // Read master playlist content
        $masterContent = file_get_contents($masterTemp);
        $masterSize = filesize($masterTemp);

        // Create derivative records
        $asset->derivatives()->whereIn('kind', ['hls_master', 'hls_playlist', 'hls_segment'])->delete();

        $asset->derivatives()->create([
            'kind' => 'hls_master',
            'path' => $masterPath,
            'mime' => 'application/vnd.apple.mpegurl',
            'size_bytes' => $masterSize,
            'variant' => 'adaptive',
        ]);

        // Store the master playlist content as metadata
        $asset->derivatives()->create([
            'kind' => 'hls_playlist',
            'path' => $masterPath,
            'mime' => 'application/vnd.apple.mpegurl',
            'size_bytes' => $masterSize,
            'variant' => 'master',
            'meta' => ['playlist' => $masterContent],
        ]);

        $this->cleanup($tempSource, $tempOut, $asset->disk === 'r2');

        return true;
    }

    private function uploadDir(string $sourceDir, string $targetDir, $disk): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
            $targetPath = $targetDir . '/' . $relativePath;
            $stream = fopen($file->getPathname(), 'r');
            $disk->writeStream($targetPath, $stream, ['visibility' => 'public']);
            fclose($stream);
        }
    }

    private function cleanup(string $tempSource, string $tempOut, bool $removeSource): void
    {
        if ($removeSource) {
            @unlink($tempSource);
        }
        $this->delTree($tempOut);
    }

    private function delTree(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir($dir);
    }
}