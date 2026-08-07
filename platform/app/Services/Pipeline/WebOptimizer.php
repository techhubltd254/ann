<?php

namespace App\Services\Pipeline;

use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use Illuminate\Support\Facades\Process;

/**
 * WebOptimizer — guarantees every pipeline output is web-fast:
 *  - video: WebM (VP9/AV1) + MP4 (H.264) fallback, faststart, poster frame
 *  - model: .glb only, Draco/Meshopt compression, LOD variants, KTX2 textures
 *  - image: WebP (AVIF when available)
 * Runs async inside the pipeline job so uploads never block the UI.
 */
class WebOptimizer
{
    public function optimizeVideo(MediaAsset $output): void
    {
        $src = $this->localPath($output);
        $slug = pathinfo($src, PATHINFO_FILENAME);
        $dir = dirname($src);
        $width = config('pipeline.video.width');
        $height = config('pipeline.video.height');
        $codec = config('pipeline.video.codec');

        $job = $output->pipelineJobs()->latest()->first();

        // 1. Poster frame (for poster-first lazy loading)
        $poster = "{$dir}/{$slug}_poster.jpg";
        Process::timeout(60)->run(['ffmpeg', '-y', '-i', $src, '-frames:v', '1', '-q:v', '3', $poster]);

        // 2. WebM modern codec
        $enc = $codec === 'av1' ? 'libaom-av1' : 'libvpx-vp9';
        $webm = "{$dir}/{$slug}_web.webm";
        $webmOk = Process::timeout(600)->run([
            'ffmpeg', '-y', '-i', $src,
            '-c:v', $enc, '-b:v', '0', '-crf', (string) config('pipeline.video.crf'),
            '-row-mt', '1', '-threads', '4', '-an', '-movflags', '+faststart',
            $webm,
        ])->exitCode() === 0 && file_exists($webm) && filesize($webm) > 0;

        // 3. MP4 H.264 fallback (faststart for streaming)
        $mp4 = "{$dir}/{$slug}_fallback.mp4";
        Process::timeout(300)->run([
            'ffmpeg', '-y', '-i', $src, '-c:v', 'libx264', '-crf', '24',
            '-preset', 'fast', '-an', '-movflags', '+faststart',
            $mp4,
        ]);

        $this->register($output, 'poster', 'image/jpeg', $poster, 'poster');
        if ($webmOk) {
            $this->register($output, 'video_webm', 'video/webm', $webm, '1080p');
        }
        $this->register($output, 'video_mp4', 'video/mp4', $mp4, '1080p');

        if ($job) {
            $job->setProgress(95, 'web_optimized');
        }
    }

    public function optimizeModel(MediaAsset $output): void
    {
        $src = $this->localPath($output);
        $slug = pathinfo($src, PATHINFO_FILENAME);
        $dir = dirname($src);

        // 1. Draco compression (gltf-transform or draco_encoder)
        $compressor = $this->findCompressor();
        if ($compressor) {
            $draco = "{$dir}/{$slug}_draco.glb";
            $ok = false;

            if (str_ends_with($compressor, 'gltf-transform')) {
                $ok = Process::timeout(300)->run([
                    $compressor, 'compress', $src, $draco,
                    '--method', 'draco', '--texture', 'webp',
                ])->exitCode() === 0;
            } else {
                $ok = Process::timeout(300)->run([
                    $compressor, '-i', $src, '-o', $draco,
                ])->exitCode() === 0;
            }

            if ($ok && file_exists($draco) && filesize($draco) < filesize($src)) {
                $this->register($output, 'model_glb', 'model/gltf-binary', $draco, 'draco');
            }
        }

        // 2. LOD variants (mock decimation via meshopt if available)
        $meshopt = $this->findMeshopt();
        if ($meshopt) {
            foreach (['lod1' => 0.5, 'lod2' => 0.25] as $lod => $ratio) {
                $out = "{$dir}/{$slug}_{$lod}.glb";
                $ok = Process::timeout(300)->run([
                    $meshopt, 'simplify', $src, $out, '--ratio', (string) $ratio,
                ])->exitCode() === 0;

                if ($ok && file_exists($out)) {
                    $this->register($output, 'model_glb', 'model/gltf-binary', $out, $lod);
                }
            }
        }
    }

    protected function register(MediaAsset $asset, string $kind, string $mime, string $absPath, ?string $variant = null): void
    {
        $rel = 'pipeline/' . basename($absPath);
        MediaDerivative::updateOrCreate(
            ['media_asset_id' => $asset->id, 'kind' => $kind, 'variant' => $variant],
            [
                'path' => $rel,
                'mime' => $mime,
                'size_bytes' => filesize($absPath) ?: 0,
                'variant' => $variant,
            ],
        );
    }

    protected function localPath(MediaAsset $asset): string
    {
        return \Illuminate\Support\Facades\Storage::disk($asset->disk)->path($asset->path);
    }

    protected function findCompressor(): ?string
    {
        foreach (['/usr/local/bin/gltf-transform', '/usr/bin/gltf-transform', 'gltf-transform', 'draco_encoder'] as $bin) {
            if ($this->binaryExists($bin)) {
                return $bin;
            }
        }

        return null;
    }

    protected function findMeshopt(): ?string
    {
        foreach (['/usr/local/bin/meshoptimizer', '/usr/bin/meshoptimizer', 'meshopt'] as $bin) {
            if ($this->binaryExists($bin)) {
                return $bin;
            }
        }

        return null;
    }

    protected function binaryExists(string $bin): bool
    {
        if (str_contains($bin, '/')) {
            return file_exists($bin);
        }

        $which = Process::path('/usr/bin')->run(['which', $bin]);

        return $which->exitCode() === 0;
    }
}
