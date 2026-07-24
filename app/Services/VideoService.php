<?php

namespace App\Services;

use App\Models\Screen;
use App\Models\ScreenImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class VideoService
{
    protected string $tempDir;
    protected string $clipsDir;
    protected string $concatFile;
    protected string $fontFile;
    protected int $fps = 30;
    protected string $resolution = '1920x1080';
    protected int $fontSize = 36;

    public function __construct()
    {
        $this->tempDir = storage_path('app/temp/kicc_showcase');
        $this->clipsDir = $this->tempDir . '/clips';
        $this->concatFile = $this->tempDir . '/concat.txt';
        $this->fontFile = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
    }

    public function generateForScreen(string $screenId, ?string $outputName = null): string
    {
        $screen = Screen::find($screenId);
        if (!$screen) {
            throw new \RuntimeException("Screen '{$screenId}' not found");
        }

        $preset = config("screens.presets.{$screen->preset_key}", config('screens.presets.county_sub'));

        $targetDur = $screen->target_duration_sec;
        $minImg = $screen->min_images ?? $preset['img_range'][0];
        $maxImg = $screen->max_images ?? $preset['img_range'][1];
        $targetCount = min($maxImg, max($minImg, intdiv($targetDur, 4)));

        $images = $this->selectImages(
            countyId: $screen->county_id,
            sectorId: $screen->sector_id,
            targetCount: $targetCount,
        );

        if (empty($images)) {
            throw new \RuntimeException("No images found for screen '{$screenId}'");
        }

        $clipDur = $this->calcClipDuration(count($images), $targetDur, $preset);
        $countyName = $screen->county_id ? Str::title($screen->county_id) : 'Kenya';

        if (!$outputName) {
            $outputName = "auto_{$screenId}.mp4";
        }

        return $this->generateShowcase(
            countyName: $countyName,
            imagePaths: array_column($images, 'storage_path'),
            clipDuration: $clipDur,
            labelStyle: $preset['label'],
            showTitleCard: $preset['title'],
            outputName: $outputName,
        );
    }

    public function generateShowcase(
        string $countyName,
        array $imagePaths,
        float $clipDuration = 4.0,
        string $labelStyle = 'auto',
        bool $showTitleCard = true,
        ?string $outputName = null,
    ): string {
        $photos = [];
        $prefix = explode(' ', $countyName)[0];
        foreach ($imagePaths as $path) {
            $stem = pathinfo($path, PATHINFO_FILENAME);
            $name = str_replace(['_', '-'], ' ', $stem);
            if (str_starts_with($name, $prefix)) {
                $name = trim(substr($name, strlen($prefix)));
            }
            $name = Str::title(trim($name) ?: $prefix);
            $photos[] = ['name' => $name, 'path' => $path];
        }

        if (!$outputName) {
            $outputName = Str::slug($countyName) . '_showcase.mp4';
        }

        // Clean and create temp dir
        $this->cleanTemp();
        if (!is_dir($this->clipsDir)) {
            mkdir($this->clipsDir, 0755, true);
        }

        $totalClips = count($photos) + ($showTitleCard ? 1 : 0);
        $clipIndex = 0;
        $outputPath = Storage::disk('public')->path('screens/' . $outputName);

        // Ensure output dir exists
        $outDir = dirname($outputPath);
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        // Title card
        if ($showTitleCard) {
            $titlePath = $this->generateTitleCard($countyName, count($photos));
            $this->renderClip($titlePath, $this->clipsDir . '/0000_title.mp4', 3.0, 1.0, 1.15, null);
            $clipIndex++;
        }

        // Photo clips
        foreach ($photos as $i => $photo) {
            $label = sprintf('%04d_%s', $i + 1, Str::slug($photo['name']));
            $zoomStart = ($i % 2 === 0) ? 1.15 : 1.0;
            $zoomEnd = ($i % 2 === 0) ? 1.0 : 1.15;
            $text = ($labelStyle !== 'none') ? $photo['name'] : null;
            $this->renderClip(
                $photo['path'],
                $this->clipsDir . '/' . $label . '.mp4',
                $clipDuration,
                $zoomStart,
                $zoomEnd,
                $text,
            );
            $clipIndex++;
        }

        // Concat
        $this->concatClips($outputPath);

        // Cleanup
        $this->cleanTemp();

        return $outputPath;
    }

    protected function renderClip(
        string $inputPath,
        string $outputPath,
        float $duration,
        float $zoomStart,
        float $zoomEnd,
        ?string $drawtext = null,
    ): void {
        $durFrames = (int) ($duration * $this->fps);
        $vf = sprintf(
            "zoompan=z='%s+(%s-%s)*on/%s':d=%s:s=%s,fade=t=in:st=0:d=0.5,fade=t=out:st=%s:d=0.5",
            $zoomStart, $zoomEnd, $zoomStart, $durFrames, $durFrames, $this->resolution,
            max(0, $duration - 0.5),
        );

        if ($drawtext) {
            $safe = str_replace(["'", '&'], ["\\'", '\\\\&'], $drawtext);
            $vf .= ",drawtext=text='{$safe}':fontcolor=white:fontsize={$this->fontSize}:fontfile={$this->fontFile}:x=(w-text_w)/2:y=h-60:shadowcolor=black:shadowx=2:shadowy=2";
        }

        $cmd = [
            'ffmpeg', '-y',
            '-loop', '1', '-i', $inputPath,
            '-vf', $vf,
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '24',
            '-pix_fmt', 'yuv420p',
            '-vsync', 'vfr',
            '-t', (string) $duration,
            $outputPath,
        ];

        $process = new Process($cmd);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    protected function generateTitleCard(string $countyName, int $photoCount): string
    {
        // Try to find a bold font
        $fontPaths = [
            '/usr/share/fonts/truetype/ubuntu/Ubuntu-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ];

        try {
            $img = imagecreatetruecolor(1920, 1080);
            $bg = imagecolorallocate($img, 10, 10, 18);
            imagefill($img, 0, 0, $bg);

            $gold = imagecolorallocate($img, 255, 215, 0);
            $gray = imagecolorallocate($img, 180, 180, 180);
            $dim = imagecolorallocate($img, 120, 120, 140);

            $largeFont = null;
            $smallFont = null;
            foreach ($fontPaths as $fp) {
                if (file_exists($fp)) {
                    $largeFont = $fp;
                    $smallFont = $fp;
                    break;
                }
            }

            $text = "{$countyName} County";
            $fontSize = $largeFont ? 80 : 5;
            if ($largeFont) {
                $bbox = imagettfbbox(80, 0, $largeFont, $text);
                $tw = abs($bbox[2] - $bbox[0]);
                imagettftext($img, 80, 0, (int) ((1920 - $tw) / 2), 460, $gold, $largeFont, $text);
            } else {
                $tw = strlen($text) * imagefontwidth($fontSize);
                imagestring($img, $fontSize, (int) ((1920 - $tw) / 2), 420, $text, $gold);
            }

            $sub = "KICC National Exhibition";
            if ($smallFont) {
                $bbox2 = imagettfbbox(32, 0, $smallFont, $sub);
                $sw = abs($bbox2[2] - $bbox2[0]);
                imagettftext($img, 32, 0, (int) ((1920 - $sw) / 2), 530, $gray, $smallFont, $sub);
            } else {
                $sw = strlen($sub) * imagefontwidth(5);
                imagestring($img, 5, (int) ((1920 - $sw) / 2), 500, $sub, $gray);
            }

            $countText = "{$photoCount} Key Sectors";
            if ($smallFont) {
                $bbox3 = imagettfbbox(28, 0, $smallFont, $countText);
                $cw = abs($bbox3[2] - $bbox3[0]);
                imagettftext($img, 28, 0, (int) ((1920 - $cw) / 2), 580, $dim, $smallFont, $countText);
            } else {
                $cw = strlen($countText) * imagefontwidth(5);
                imagestring($img, 5, (int) ((1920 - $cw) / 2), 540, $countText, $dim);
            }

            $outPath = $this->tempDir . '/title_card.jpg';
            if (!is_dir($this->tempDir)) {
                mkdir($this->tempDir, 0755, true);
            }
            imagejpeg($img, $outPath, 92);
            imagedestroy($img);

            return $outPath;
        } catch (\Throwable $e) {
            // Fallback: generate a solid-color image
            $outPath = $this->tempDir . '/title_card.jpg';
            if (!is_dir($this->tempDir)) {
                mkdir($this->tempDir, 0755, true);
            }
            $img = imagecreatetruecolor(1920, 1080);
            $bg = imagecolorallocate($img, 10, 10, 18);
            imagefill($img, 0, 0, $bg);
            imagejpeg($img, $outPath, 92);
            imagedestroy($img);
            return $outPath;
        }
    }

    protected function concatClips(string $outputPath): void
    {
        $clips = glob($this->clipsDir . '/*.mp4');
        sort($clips);

        $lines = array_map(fn($c) => "file '" . realpath($c) . "'", $clips);
        file_put_contents($this->concatFile, implode("\n", $lines));

        $cmd = [
            'ffmpeg', '-y',
            '-f', 'concat', '-safe', '0',
            '-i', $this->concatFile,
            '-c', 'copy',
            $outputPath,
        ];

        $process = new Process($cmd);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    protected function selectImages(
        ?string $countyId = null,
        ?string $sectorId = null,
        int $targetCount = 15,
        array $excludeIds = [],
    ): array {
        $query = ScreenImage::query();

        if ($countyId && $sectorId) {
            $query->where('county_id', $countyId)
                  ->where('sector_ids', 'like', "%{$sectorId}%");
        } elseif ($countyId) {
            $query->where('county_id', $countyId);
        } elseif ($sectorId) {
            $query->where('sector_ids', 'like', "%{$sectorId}%");
        }

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        $rows = $query->orderBy('quality_score', 'desc')->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $pool = $rows->take(max($targetCount * 2, 20));
        $selected = $pool->count() > $targetCount
            ? $pool->random(min($targetCount, $pool->count()))
            : $pool;

        return $selected->shuffle()->values()->all();
    }

    protected function calcClipDuration(int $imageCount, int $targetDuration, array $preset): float
    {
        $titleDur = $preset['title'] ? 3.0 : 0.0;
        $avail = $targetDuration - $titleDur;
        if ($imageCount <= 0) {
            return $preset['clip_sec'];
        }
        return max(2.0, min($preset['clip_sec'] + 1, $avail / $imageCount));
    }

    protected function cleanTemp(): void
    {
        $this->rmdirRecursive($this->tempDir);
    }

    protected function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        rmdir($dir);
    }
}
