<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageOptimizer
{
    public array $sizes = [320, 640, 1280, 1920];
    public int $jpegQuality = 80;
    public int $webpQuality = 75;
    public int $pngCompression = 6;
    public int $maxWidth = 1920;
    public int $maxHeight = 1920;

    public function __construct()
    {
        $this->detectAvailableDriver();
    }

    private function availableDriver(): string
    {
        return extension_loaded('imagick') ? 'imagick' : 'gd';
    }

    private function detectAvailableDriver(): void
    {
        if (!extension_loaded('imagick') && !extension_loaded('gd')) {
            Log::warning('ImageOptimizer: No image processing extension available (imagick/gd)');
        }
    }

    public function optimize(string $sourcePath, ?string $destDir = null): array
    {
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Source file not found: $sourcePath");
        }

        $destDir ??= dirname($sourcePath);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return [$sourcePath];
        }

        $outputs = [];
        $driver = $this->availableDriver();

        if ($driver === 'imagick') {
            $outputs = $this->optimizeWithImagick($sourcePath, $destDir, $filename);
        } elseif ($driver === 'gd') {
            $outputs = $this->optimizeWithGd($sourcePath, $destDir, $filename);
        }

        return $outputs;
    }

    public function optimizeAndStore(UploadedFile $file, string $storagePath): array
    {
        $tempPath = $file->getRealPath();
        $optimized = $this->optimize($tempPath);

        $stored = [];
        foreach ($optimized as $localPath) {
            $relative = $storagePath . '/' . basename($localPath);
            Storage::put($relative, file_get_contents($localPath), 'public');
            $stored[] = $relative;
        }

        return $stored;
    }

    private function optimizeWithImagick(string $source, string $destDir, string $filename): array
    {
        $outputs = [];

        try {
            $img = new \Imagick($source);
            $img->setImageCompressionQuality($this->jpegQuality);

            $img->stripImage();
            $img->setSamplingFactors(['2x2', '1x1', '1x1']);

            if ($img->getImageAlphaChannel()) {
                $img->setImageFormat('png');
                $img->setOption('png:compression-level', (string)$this->pngCompression);
                $outputPath = "$destDir/{$filename}.png";
                $img->writeImage($outputPath);
                $outputs[] = $outputPath;
            } else {
                foreach ([null, 'webp'] as $fmt) {
                    if ($fmt === 'webp') {
                        $webp = clone $img;
                        $size = filesize($source);
                        $quality = $size > 500000 ? 70 : 80;
                        $webp->setImageFormat('webp');
                        $webp->setImageCompressionQuality($quality);
                        $outputPath = "$destDir/{$filename}.webp";
                        $webp->writeImage($outputPath);
                        $outputs[] = $outputPath;
                        $webp->clear();
                    } else {
                        $img->setImageFormat('jpeg');
                        $outputPath = "$destDir/{$filename}.jpg";
                        $img->writeImage($outputPath);
                        $outputs[] = $outputPath;
                    }
                }
            }

            $img->clear();
        } catch (\Throwable $e) {
            Log::warning("Imagick optimization failed for $source: {$e->getMessage()}");
            $outputs[] = $source;
        }

        return $outputs;
    }

    private function optimizeWithGd(string $source, string $destDir, string $filename): array
    {
        $outputs = [];
        $info = getimagesize($source);

        if (!$info) {
            return [$source];
        }

        [$srcW, $srcH] = $info;
        $mime = $info['mime'];

        $srcImg = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/gif' => @imagecreatefromgif($source),
            'image/webp' => @imagecreatefromwebp($source),
            default => null,
        };

        if (!$srcImg) {
            return [$source];
        }

        $ratio = min($this->maxWidth / $srcW, $this->maxHeight / $srcH, 1);
        $dstW = (int)round($srcW * $ratio);
        $dstH = (int)round($srcH * $ratio);

        if ($ratio < 1) {
            $dstImg = imagecreatetruecolor($dstW, $dstH);
            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        } else {
            $dstImg = $srcImg;
            $dstW = $srcW;
            $dstH = $srcH;
        }

        $jpgPath = "$destDir/{$filename}.jpg";
        imagejpeg($dstImg, $jpgPath, $this->jpegQuality);
        $outputs[] = $jpgPath;

        if (function_exists('imagewebp')) {
            $webpPath = "$destDir/{$filename}.webp";
            $size = filesize($source);
            $webpQuality = $size > 500000 ? 70 : 80;
            imagewebp($dstImg, $webpPath, $webpQuality);
            $outputs[] = $webpPath;
        }

        imagedestroy($srcImg);
        if ($ratio < 1) imagedestroy($dstImg);

        return $outputs;
    }

    public static function imgUrl(?string $path, int $width = 0, string $format = 'auto'): string
    {
        if (!$path) return '';

        $base = url('storage/' . $path);
        $webp = $width > 0
            ? url('storage/' . pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . ".webp")
            : str_replace(['.jpg', '.jpeg', '.png'], '.webp', $base);

        $resized = $width > 0 ? $base : $base;

        if ($format === 'webp') return $webp;

        return $base;
    }

    public static function picture(?string $path, string $class = '', string $alt = '', int $width = 0, int $height = 0): string
    {
        if (!$path) return '';

        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $base = url("storage/$path");
        $webp = url("storage/$dir/$name.webp");

        $sizeAttr = $width ? "width=\"$width\" height=\"$height\"" : '';
        $loading = $height > 200 ? 'loading="lazy"' : '';

        return "<picture>
            <source srcset=\"$webp\" type=\"image/webp\">
            <img src=\"$base\" alt=\"" . htmlspecialchars($alt) . "\" class=\"$class\" $sizeAttr $loading decoding=\"async\">
        </picture>";
    }

    public function batchOptimize(string $directory): array
    {
        $results = ['processed' => 0, 'skipped' => 0, 'errors' => 0, 'saved_bytes' => 0];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (!in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
            if (str_contains($file->getPathname(), '/optimized/')) continue;
            if (str_contains($file->getPathname(), '/thumb/')) continue;

            $origSize = $file->getSize();
            try {
                $result = $this->optimize($file->getPathname(), $file->getPath());
                $newPath = $result[0] ?? null;
                if ($newPath && $newPath !== $file->getPathname()) {
                    $newSize = filesize($newPath);
                    $results['saved_bytes'] += $origSize - $newSize;
                    $results['processed']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Throwable $e) {
                Log::warning("Batch optimize failed for {$file->getPathname()}: {$e->getMessage()}");
                $results['errors']++;
            }
        }

        $results['saved_mb'] = round($results['saved_bytes'] / 1048576, 2);
        return $results;
    }
}