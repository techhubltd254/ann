<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 3-Layer Image Pipeline — Layer 3: Automated Ingestion.
 *
 * Converts every uploaded raw photo into lightweight responsive WebP variants
 * (thumb 320 / card 640 / hero 1920) plus a tiny base64 blur placeholder,
 * and pushes them to R2 so grids load instantly with zero layout shift.
 */
class ImageOptimizer
{
    public array $sizes = [
        'thumb' => 320,
        'card' => 640,
        'hero' => 1920,
    ];
    public int $webpQuality = 75;
    public int $blurWidth = 20;
    public int $maxWidth = 1920;
    public int $maxHeight = 1920;

    /** R2 base path where variants are stored: opt/{hash}_thumb.webp etc. */
    public string $variantPrefix = 'opt';

    public function __construct()
    {
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            Log::warning('ImageOptimizer: No image processing extension available (imagick/gd)');
        }
    }

    /**
     * Generate all variants for a source image file and upload them to R2.
     *
     * @param string $sourcePath absolute path to the raw image
     * @param string|null $r2BaseKey R2 key base (without extension) — e.g. "counties/muranga/hero"
     * @return array{variants: array<string,string>, blur: string}
     */
    public function generateVariants(string $sourcePath, ?string $r2BaseKey = null): array
    {
        $variants = [];
        $blur = '';

        $info = @getimagesize($sourcePath);
        if (!$info) {
            return ['variants' => [], 'blur' => ''];
        }
        [$srcW, $srcH] = $info;
        $mime = $info['mime'];

        $srcImg = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/gif' => @imagecreatefromgif($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            default => null,
        };

        if (!$srcImg || !function_exists('imagewebp')) {
            return ['variants' => [], 'blur' => ''];
        }

        $hash = Str::random(10);
        $r2 = Storage::disk('r2');

        try {
            foreach ($this->sizes as $name => $width) {
                if ($width >= $srcW) {
                    // Source is smaller than this variant — keep original size (no upscale)
                    $dstW = $srcW;
                    $dstH = $srcH;
                    $dstImg = $srcImg;
                } else {
                    $dstW = $width;
                    $dstH = (int) round($srcH * ($width / $srcW));
                    $dstImg = imagecreatetruecolor($dstW, $dstH);
                    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
                }

                $temp = tempnam(sys_get_temp_dir(), 'imgv_') . '.webp';
                imagewebp($dstImg, $temp, $this->webpQuality);

                $base = $r2BaseKey ?? 'opt/' . $hash;
                $key = $base . "_{$name}.webp";
                $fh = fopen($temp, 'r');
                $r2->writeStream($key, $fh, ['visibility' => 'public']);
                fclose($fh);
                @unlink($temp);

                $variants[$name] = $key;

                if ($dstImg !== $srcImg) {
                    imagedestroy($dstImg);
                }
            }

            // Tiny blur placeholder (20px WebP → base64 data URI)
            $blurImg = imagecreatetruecolor($this->blurWidth, max(1, (int) round($srcH * ($this->blurWidth / $srcW))));
            imagecopyresampled($blurImg, $srcImg, 0, 0, 0, 0, $this->blurWidth, imagesy($blurImg), $srcW, $srcH);
            $blurTemp = tempnam(sys_get_temp_dir(), 'blur_') . '.webp';
            imagewebp($blurImg, $blurTemp, 30);
            $blur = 'data:image/webp;base64,' . base64_encode((string) file_get_contents($blurTemp));
            @unlink($blurTemp);
            imagedestroy($blurImg);
        } catch (\Throwable $e) {
            Log::warning("ImageOptimizer variant generation failed: {$e->getMessage()}");
        } finally {
            imagedestroy($srcImg);
        }

        return ['variants' => $variants, 'blur' => $blur];
    }

    /**
     * Generate variants from an UploadedFile and persist them to R2.
     */
    public function generateFromUpload(UploadedFile $file, ?string $r2BaseKey = null): array
    {
        return $this->generateVariants($file->getRealPath(), $r2BaseKey);
    }

    /** Convenience: public URL for a variant key. */
    public static function variantUrl(string $key): string
    {
        return media_url() . '/' . ltrim($key, '/');
    }
}