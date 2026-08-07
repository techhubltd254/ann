<?php

namespace App\Services\Pipeline\Engines;

use App\Models\MediaAsset;
use App\Models\PipelineJob;
use App\Services\Pipeline\BaseEngine;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * FfmpegKenburnsEngine — the zero-cost default engine.
 *
 * Renders a cinematic Ken Burns motion (zoom + pan) over a single image,
 * plus optional camera-path presets (pan_lr, pan_rl, tilt, zoom_in, orbit).
 * Works offline with stock ffmpeg — this is the "works today" pipeline that
 * guarantees the admin flow is fully usable before AI engines are connected.
 */
class FfmpegKenburnsEngine extends BaseEngine
{
    protected function name(): string
    {
        return 'ffmpeg_kenburns';
    }

    public function availabilityNote(): ?string
    {
        return null; // always available
    }

    public function run(PipelineJob $job): void
    {
        $source = $this->resolveInputAsset($job);
        $input = $this->localPath($source);

        $opts = $job->options ?? [];
        $path = $opts['camera_path'] ?? 'zoom_in';
        $duration = (int) ($opts['duration'] ?? config('pipeline.video.duration_sec'));
        $width = (int) ($opts['width'] ?? config('pipeline.video.width'));
        $height = (int) ($opts['height'] ?? config('pipeline.video.height'));
        $fps = (int) config('pipeline.video.fps');
        $codec = config('pipeline.video.codec');

        $job->markRunning('rendering_motion');

        $slug = $source->uuid ?: Str::slug($source->original_name);
        $base = "pipeline/{$slug}";

        // 1. Render master H.264 (fast, universal)
        $job->setProgress(5, 'rendering_master');
        $master = $this->outDir() . "/{$slug}_master.mp4";
        $this->renderMotion($input, $master, $path, $duration, $width, $height, $fps, 'h264');
        $job->setProgress(45, 'master_done');

        // 2. Compress to modern web format (webm vp9/av1) with mp4 fallback
        $job->setProgress(50, 'compressing_web');
        $webmPath = null;
        $webEnc = $this->webmCodec($codec);
        if ($webEnc !== null) {
            $webm = $this->outDir() . "/{$slug}_showcase.webm";
            if ($this->encodeWebm($master, $webm, $webEnc, $width, $height)) {
                $webmPath = "{$base}_showcase.webm";
            }
        }

        $mp4Path = "{$base}_showcase.mp4";
        $this->copyToPublic($master, storage_path('app/public/' . $mp4Path));
        $job->setProgress(80, 'compressed');

        // 3. Poster frame (poster-first loading: no 3-min waits, image shows instantly)
        $job->setProgress(85, 'extracting_poster');
        $posterRel = "{$base}_poster.jpg";
        $this->extractPoster($master, storage_path('app/public/' . $posterRel));

        // 4. Register output asset + derivatives
        $output = $this->createOutputAsset($source, $mp4Path, [
            'kind' => 'video',
            'metadata' => [
                'source_asset_id' => $source->id,
                'pipeline' => 'cinematic_video',
                'engine' => 'ffmpeg_kenburns',
                'camera_path' => $path,
                'duration' => $duration,
            ],
        ]);

        $output->derivatives()->createMany([
            ['kind' => 'video_mp4', 'path' => $mp4Path, 'mime' => 'video/mp4', 'size_bytes' => filesize(storage_path('app/public/' . $mp4Path)), 'width' => $width, 'height' => $height, 'variant' => '1080p'],
            $webmPath ? ['kind' => 'video_webm', 'path' => $webmPath, 'mime' => 'video/webm', 'size_bytes' => filesize(storage_path('app/public/' . $webmPath)), 'width' => $width, 'height' => $height, 'variant' => '1080p'] : null,
            ['kind' => 'poster', 'path' => $posterRel, 'mime' => 'image/jpeg', 'size_bytes' => filesize(storage_path('app/public/' . $posterRel)), 'width' => $width, 'height' => $height],
        ]);

        @unlink($master);

        $job->setProgress(100, 'finalizing');
        $job->complete($output->id);
    }

    protected function renderMotion(string $input, string $output, string $path, int $duration, int $w, int $h, int $fps, string $codec): void
    {
        $zoom = $this->zoomExpr($path, $duration * $fps);
        $pan = $this->panExpr($path, $duration * $fps, $w, $h);

        $vf = sprintf(
            "scale=%d:%d:force_original_aspect_ratio=increase,crop=%d:%d,scale=%d:%d,zoompan=z='%s':d=1:x='%s':y='%s':s=%dx%d:fps=%d,format=yuv420p",
            $w, $h, $w, $h, $w * 2, $h * 2, $zoom, $pan['x'], $pan['y'], $w, $h, $fps
        );

        $result = Process::timeout(300)
            ->path(dirname($input))
            ->run([
                'ffmpeg', '-y', '-loop', '1', '-i', $input,
                '-vf', $vf, '-t', (string) $duration,
                '-c:v', $codec === 'h264' ? 'libx264' : 'libx264',
                '-preset', 'veryfast', '-crf', '22', '-pix_fmt', 'yuv420p',
                $output,
            ]);

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('ffmpeg motion render failed: ' . $result->errorOutput());
        }
    }

    protected function zoomExpr(string $path, int $frames): string
    {
        $end = match ($path) {
            'zoom_out' => 'min(zoom+0.0015,1.0)',
            'pan_lr', 'pan_rl', 'tilt_up', 'tilt_down', 'orbit' => '1.10',
            default => 'min(zoom+0.0015,1.5)',
        };
        $start = match ($path) {
            'zoom_out' => '1.5',
            'pan_lr', 'pan_rl', 'tilt_up', 'tilt_down', 'orbit' => '1.10',
            default => '1.0',
        };

        return sprintf('if(lte(on,1),%s,%s)', $start, $end);
    }

    protected function panExpr(string $path, int $frames, int $w, int $h): array
    {
        $cw = $w / 2;
        $ch = $h / 2;

        return match ($path) {
            'pan_lr' => [
                'x' => sprintf('(iw-%d)/2 + %d*on/%d', $cw, $w / 2, $frames),
                'y' => sprintf('(ih-%d)/2', $ch),
            ],
            'pan_rl' => [
                'x' => sprintf('(iw-%d)/2 + %d*(1-on/%d)', $cw, $w / 2, $frames),
                'y' => sprintf('(ih-%d)/2', $ch),
            ],
            'tilt_up' => [
                'x' => sprintf('(iw-%d)/2', $cw),
                'y' => sprintf('(ih-%d)/2 + %d*(1-on/%d)', $ch, $h / 2, $frames),
            ],
            'tilt_down' => [
                'x' => sprintf('(iw-%d)/2', $cw),
                'y' => sprintf('(ih-%d)/2 + %d*on/%d', $ch, $h / 2, $frames),
            ],
            'orbit' => [
                'x' => sprintf('(iw-%d)/2 + %d*sin(2*PI*on/%d)', $cw, $w / 4, $frames),
                'y' => sprintf('(ih-%d)/2 + %d*cos(2*PI*on/%d)', $ch, $h / 4, $frames),
            ],
            default => [
                'x' => sprintf('(iw-%d)/2', $cw),
                'y' => sprintf('(ih-%d)/2', $ch),
            ],
        };
    }

    protected function webmCodec(string $codec): ?string
    {
        return match ($codec) {
            'av1' => 'libaom-av1',
            'vp9' => 'libvpx-vp9',
            default => null,
        };
    }

    protected function encodeWebm(string $master, string $out, string $enc, int $w, int $h): bool
    {
        $result = Process::timeout(600)->run([
            'ffmpeg', '-y', '-i', $master,
            '-c:v', $enc, '-b:v', '0', '-crf', (string) config('pipeline.video.crf'),
            '-row-mt', '1', '-threads', '4',
            '-an', '-movflags', '+faststart',
            $out,
        ]);

        return $result->exitCode() === 0 && file_exists($out) && filesize($out) > 0;
    }

    protected function copyToPublic(string $src, string $dst): void
    {
        if (!is_dir(dirname($dst))) {
            mkdir(dirname($dst), 0755, true);
        }
        copy($src, $dst);
    }

    protected function extractPoster(string $master, string $dst): void
    {
        if (!is_dir(dirname($dst))) {
            mkdir(dirname($dst), 0755, true);
        }
        Process::timeout(60)->run([
            'ffmpeg', '-y', '-i', $master, '-frames:v', '1', '-q:v', '3', $dst,
        ]);
    }
}
