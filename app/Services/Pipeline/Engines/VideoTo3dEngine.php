<?php

namespace App\Services\Pipeline\Engines;

use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use App\Models\PipelineJob;
use App\Services\Pipeline\BaseEngine;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * VideoTo3dEngine — generates depth maps from uploaded MP4 videos.
 *
 * Primary method: MiDaS v3.1 via ONNX Runtime (small, fast, CPU).
 * Fallback: ffmpeg luminance + sobel + gaussian blur.
 *
 * The depth map is a grayscale video where:
 *   white (255) = close to camera (foreground)
 *   black (0)   = far from camera (background)
 *
 * Frontend uses this as a vertex displacement map for 3D parallax.
 *
 * Env:
 *   PIPELINE_VIDEO_TO_3D_ENABLED=true
 *   MIDAS_MODEL_PATH=/opt/models/midas_small.onnx
 *   PIPELINE_VIDEO_TO_3D_PYTHON=python3
 */
class VideoTo3dEngine extends BaseEngine
{
    protected function name(): string
    {
        return 'video_to_3d';
    }

    public function availabilityNote(): ?string
    {
        // Always available — ffmpeg fallback works everywhere
        return null;
    }

    public function run(PipelineJob $job): void
    {
        $source = $this->resolveInputAsset($job);
        $input = $this->localPath($source);
        $slug = $source->uuid ?: Str::slug($source->original_name);
        $workDir = $this->workDir($slug);

        $job->markRunning('extracting_frames');

        // ── Step 1: Extract frames at 2 fps ──
        $framesDir = $workDir . '/frames';
        if (!is_dir($framesDir)) mkdir($framesDir, 0755, true);

        $framePattern = $framesDir . '/frame_%04d.jpg';
        $extractResult = Process::timeout(300)->run([
            'ffmpeg', '-y', '-i', $input,
            '-vf', 'fps=2,scale=512:288',
            '-q:v', '5',
            $framePattern,
        ]);

        if ($extractResult->exitCode() !== 0) {
            throw new \RuntimeException('ffmpeg frame extraction failed: ' . $extractResult->errorOutput());
        }

        $frames = glob($framesDir . '/frame_*.jpg');
        if (empty($frames)) {
            throw new \RuntimeException('No frames extracted from video.');
        }

        $job->setProgress(20, 'frames_extracted');
        $totalFrames = count($frames);

        // ── Step 2: Generate depth maps ──
        $depthDir = $workDir . '/depth';
        if (!is_dir($depthDir)) mkdir($depthDir, 0755, true);

        $midasModel = env('MIDAS_MODEL_PATH', '/opt/models/midas_small.onnx');
        $pythonBin = env('PIPELINE_VIDEO_TO_3D_PYTHON', 'python3');
        $pythonAvailable = $this->checkPython();

        if ($pythonAvailable && file_exists($midasModel)) {
            $job->setProgress(25, 'depth_midas');
            $this->generateDepthMidas($frames, $depthDir, $midasModel, $pythonBin, $totalFrames, $job);
        } else {
            if (!$pythonAvailable) {
                Log::info('VideoTo3dEngine: Python not available, using ffmpeg fallback');
            }
            if (!file_exists($midasModel)) {
                Log::info('VideoTo3dEngine: MiDaS model not found at ' . $midasModel . ', using ffmpeg fallback');
            }
            $job->setProgress(25, 'depth_ffmpeg');
            $this->generateDepthFfmpeg($frames, $depthDir, $totalFrames, $job);
        }

        $depthFrames = glob($depthDir . '/depth_*.jpg');
        if (empty($depthFrames)) {
            throw new \RuntimeException('Depth map generation produced no output frames.');
        }

        $job->setProgress(70, 'encoding_depth');

        // ── Step 3: Encode depth frames back into a video ──
        $depthVideoRel = 'pipeline/' . $slug . '_depth.mp4';
        $depthVideoAbs = storage_path('app/public/' . $depthVideoRel);
        $outDir = dirname($depthVideoAbs);
        if (!is_dir($outDir)) mkdir($outDir, 0755, true);

        // Create a concat file for ffmpeg
        $concatFile = $workDir . '/depth_concat.txt';
        $lines = array_map(fn($f) => "file '" . realpath($f) . "'\nduration 0.5", $depthFrames);
        // Add last frame again for proper duration
        $lines[] = "file '" . realpath(end($depthFrames)) . "'";
        file_put_contents($concatFile, implode("\n", $lines));

        $encodeResult = Process::timeout(300)->run([
            'ffmpeg', '-y',
            '-f', 'concat', '-safe', '0',
            '-i', $concatFile,
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '28',
            '-pix_fmt', 'yuv420p',
            '-vf', 'scale=512:288,fps=2',
            $depthVideoAbs,
        ]);

        if ($encodeResult->exitCode() !== 0) {
            throw new \RuntimeException('ffmpeg depth encoding failed: ' . $encodeResult->errorOutput());
        }

        if (!file_exists($depthVideoAbs)) {
            throw new \RuntimeException('Depth video file was not created.');
        }

        // ── Step 4: Store as derivative ──
        $job->setProgress(90, 'storing');
        $source->derivatives()->updateOrCreate(
            ['kind' => 'depth_map', 'variant' => '480p'],
            [
                'path' => $depthVideoRel,
                'mime' => 'video/mp4',
                'size_bytes' => filesize($depthVideoAbs),
                'width' => 512,
                'height' => 288,
            ]
        );

        // ── Step 5: Set display_mode to 'parallax' if not already set ──
        if (!$source->display_mode || $source->display_mode === 'flat') {
            $source->update(['display_mode' => 'parallax']);
        }

        // ── Cleanup ──
        $this->cleanDir($framesDir);
        $this->cleanDir($depthDir);
        @unlink($concatFile);

        $job->setProgress(100, 'done');
        $job->complete($source->id);
    }

    protected function generateDepthMidas(array $frames, string $depthDir, string $modelPath, string $python, int $total, PipelineJob $job): void
    {
        // Write a temporary Python script that runs MiDaS on all frames
        $scriptPath = storage_path('app/temp/midas_batch_' . Str::random(8) . '.py');
        $framesJson = storage_path('app/temp/midas_frames_' . Str::random(8) . '.json');
        $depthJson = $depthDir . '/_manifest.json';

        file_put_contents($framesJson, json_encode($frames));

        $script = <<<'PYEOF'
import json, os, sys, cv2, numpy as np

try:
    import onnxruntime
except ImportError:
    sys.exit(2)  # signal fallback

frames_path = sys.argv[1]
depth_dir = sys.argv[2]
model_path = sys.argv[3]

with open(frames_path) as f:
    frames = json.load(f)

try:
    session = onnxruntime.InferenceSession(model_path)
    input_name = session.get_inputs()[0].name
except Exception as e:
    print(f"ONNX load failed: {e}", file=sys.stderr)
    sys.exit(2)

total = len(frames)
for i, frame_path in enumerate(frames):
    img = cv2.imread(frame_path)
    if img is None:
        continue
    h, w = img.shape[:2]
    # Prepare input: resize to 256x256, normalize to [-1, 1]
    resized = cv2.resize(img, (256, 256))
    normalized = (resized.astype(np.float32) / 255.0 - 0.5) * 2.0
    # Run inference
    depth = session.run(None, {input_name: normalized[np.newaxis, :]})[0][0]
    # Resize back to original frame dimensions
    depth = cv2.resize(depth, (w, h))
    # Normalize to 0-255
    d_min, d_max = depth.min(), depth.max()
    if d_max > d_min:
        depth = ((depth - d_min) / (d_max - d_min) * 255).astype(np.uint8)
    else:
        depth = np.full((h, w), 128, dtype=np.uint8)
    # Save
    out_path = os.path.join(depth_dir, f"depth_{i:04d}.jpg")
    cv2.imwrite(out_path, depth)

# Write manifest
manifest = [os.path.join(depth_dir, f"depth_{i:04d}.jpg") for i in range(total)]
with open(os.path.join(depth_dir, "_manifest.json"), "w") as mf:
    json.dump(manifest, mf)

print(f"Processed {total} frames with MiDaS")
PYEOF;

        file_put_contents($scriptPath, $script);

        $result = Process::timeout(600)->run([
            $python, $scriptPath, $framesJson, $depthDir, $modelPath,
        ]);

        $exitCode = $result->exitCode();
        @unlink($scriptPath);
        @unlink($framesJson);

        if ($exitCode === 2) {
            // onnxruntime not available — fall back to ffmpeg
            Log::info('VideoTo3dEngine: onnxruntime not available, falling back to ffmpeg');
            $this->generateDepthFfmpeg($frames, $depthDir, $total, $job);
        } elseif ($exitCode !== 0) {
            Log::warning('VideoTo3dEngine: MiDaS failed, falling back to ffmpeg: ' . $result->errorOutput());
            $this->generateDepthFfmpeg($frames, $depthDir, $total, $job);
        }

        // Report progress per 10 frames
        for ($i = 0; $i < $total; $i += max(1, intdiv($total, 10))) {
            $pct = 25 + (int) (($i / $total) * 40);
            $job->setProgress(min(65, $pct), 'depth_midas');
        }
    }

    protected function generateDepthFfmpeg(array $frames, string $depthDir, int $total, PipelineJob $job): void
    {
        foreach ($frames as $i => $frame) {
            $outPath = $depthDir . '/depth_' . sprintf('%04d', $i) . '.jpg';
            // ffmpeg: grayscale → sobel edge detection → contrast stretch → blur
            $result = Process::timeout(30)->run([
                'ffmpeg', '-y', '-i', $frame,
                '-vf', 'format=gray,sobel,eq=brightness=0.1:contrast=2.0,gblur=sigma=1.5',
                '-q:v', '5',
                $outPath,
            ]);

            if ($result->exitCode() !== 0) {
                // If sobel fails, just use grayscale
                Process::timeout(30)->run([
                    'ffmpeg', '-y', '-i', $frame,
                    '-vf', 'format=gray,gblur=sigma=2.0',
                    '-q:v', '5',
                    $outPath,
                ]);
            }

            if ($i % max(1, intdiv($total, 10)) === 0) {
                $pct = 25 + (int) (($i / $total) * 40);
                $job->setProgress(min(65, $pct), 'depth_ffmpeg');
            }
        }
    }

    protected function workDir(string $slug): string
    {
        $dir = storage_path('app/temp/3d_' . $slug);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    protected function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (glob($dir . '/*') as $f) {
            is_file($f) && @unlink($f);
        }
        @rmdir($dir);
    }

    protected function checkPython(): bool
    {
        $result = Process::timeout(10)->run(['python3', '--version']);
        return $result->exitCode() === 0;
    }
}