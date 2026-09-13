<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tier-1/Tier-2 derivative generation for the 3-tier media delivery architecture:
 *  - Tier 1: WebP poster frame (~30-60 KB, instant grid load)
 *  - Tier 2: 3s low-bitrate hover loop (~300-600 KB, hover / in-view playback)
 *  - Tier 3: full HLS adaptive stream (handled by HlsGenerator)
 */
class MediaDerivativesService
{
    /**
     * Generate poster + hover loop for a video asset. Returns array of created kinds.
     */
    public function generate(MediaAsset $asset, bool $force = false): array
    {
        if ($asset->kind !== 'video') {
            return [];
        }

        $disk = Storage::disk($asset->disk);
        $sourcePath = $asset->path;
        $info = pathinfo($sourcePath);
        $baseDir = $info['dirname'];
        $baseName = $info['filename'];

        $tempSource = tempnam(sys_get_temp_dir(), 'deriv_') . '.mp4';
        $created = [];

        try {
            // Stream source to temp (files can be hundreds of MB)
            if ($asset->disk === 'r2') {
                $stream = $disk->readStream($sourcePath);
                if (!$stream) {
                    Log::warning("MediaDerivatives: cannot read source: {$sourcePath}");
                    return [];
                }
                $fh = fopen($tempSource, 'wb');
                if (!$fh) {
                    fclose($stream);
                    return [];
                }

                if (disk_free_space(sys_get_temp_dir()) < 500 * 1024 * 1024) {
                    fclose($fh);
                    fclose($stream);
                    @unlink($tempSource);
                    throw new \RuntimeException('Insufficient temp space for media processing');
                }

                set_time_limit(300);
                while (!feof($stream)) {
                    fwrite($fh, fread($stream, 1024 * 1024));
                }
                fclose($fh);
                fclose($stream);
            } else {
                $tempSource = $disk->path($sourcePath);
            }

            if (!file_exists($tempSource)) {
                Log::warning("MediaDerivatives: source missing: {$tempSource}");
                return [];
            }

            // ═══ Tier 1: WebP poster (frame @1s) ═══
            if ($force || !$asset->derivatives()->where('kind', 'poster')->exists()) {
                $posterRel = $baseDir . '/poster/' . $baseName . '.webp';
                $posterTemp = sys_get_temp_dir() . '/poster_' . Str::random(8) . '.webp';
                $cmd = sprintf(
                    'ffmpeg -y -ss 1 -i %s -vframes 1 -vf "scale=720:-2" -c:v libwebp -q:v 75 %s',
                    escapeshellarg($tempSource),
                    escapeshellarg($posterTemp)
                );
                exec($cmd . ' 2>/dev/null', $out, $code);
                if ($code === 0 && file_exists($posterTemp)) {
                    $stream = fopen($posterTemp, 'r');
                    $disk->writeStream($posterRel, $stream, ['visibility' => 'public']);
                    fclose($stream);
                    $asset->derivatives()->updateOrCreate(
                        ['kind' => 'poster', 'variant' => '720p'],
                        ['path' => $posterRel, 'mime' => 'image/webp', 'size_bytes' => filesize($posterTemp)]
                    );
                    $created[] = 'poster';
                    @unlink($posterTemp);
                } else {
                    Log::warning("MediaDerivatives: poster failed for asset {$asset->id}");
                }
            }

            // ═══ Tier 2: 3s hover loop (640px, crf28, no audio) ═══
            if ($force || !$asset->derivatives()->where('kind', 'hover_loop')->exists()) {
                $loopRel = $baseDir . '/hover/' . $baseName . '.mp4';
                $loopTemp = sys_get_temp_dir() . '/hover_' . Str::random(8) . '.mp4';
                $cmd = sprintf(
                    'ffmpeg -y -i %s -ss 0 -t 3 ' .
                    '-vf "scale=640:-2:flags=lanczos,fps=30,format=yuv420p" ' .
                    '-c:v libx264 -profile:v baseline -level 3.0 -crf 28 -an -movflags +faststart %s',
                    escapeshellarg($tempSource),
                    escapeshellarg($loopTemp)
                );
                exec($cmd . ' 2>/dev/null', $out, $code);
                if ($code === 0 && file_exists($loopTemp)) {
                    $stream = fopen($loopTemp, 'r');
                    $disk->writeStream($loopRel, $stream, ['visibility' => 'public']);
                    fclose($stream);
                    $asset->derivatives()->updateOrCreate(
                        ['kind' => 'hover_loop', 'variant' => '640p'],
                        ['path' => $loopRel, 'mime' => 'video/mp4', 'size_bytes' => filesize($loopTemp)]
                    );
                    $created[] = 'hover_loop';
                    @unlink($loopTemp);
                } else {
                    Log::warning("MediaDerivatives: hover loop failed for asset {$asset->id}");
                }
            }

            return $created;
        } catch (\Throwable $e) {
            Log::error("MediaDerivatives: exception for {$asset->id}: " . $e->getMessage());
            return [];
        } finally {
            if ($asset->disk === 'r2' && file_exists($tempSource)) {
                @unlink($tempSource);
            }
        }
    }
}