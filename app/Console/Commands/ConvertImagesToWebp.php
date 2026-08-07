<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ConvertImagesToWebp extends Command
{
    protected $signature = 'images:to-webp {dir=counties}';
    protected $description = 'Convert all JPEG/PNG images to WebP in a storage directory';

    public function handle(): int
    {
        $dir = $this->argument('dir');
        $base = storage_path("app/public/{$dir}");
        
        if (!is_dir($base)) {
            $this->error("Directory not found: {$base}");
            return self::FAILURE;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $converted = 0;
        $skipped = 0;

        foreach ($files as $file) {
            if (!$file->isFile()) continue;
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;

            $webpPath = $file->getPath() . '/' . $file->getBasename("." . $file->getExtension()) . '.webp';
            
            if (file_exists($webpPath)) {
                $skipped++;
                continue;
            }

            $image = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($file->getPathname()),
                'png' => @imagecreatefrompng($file->getPathname()),
                default => null,
            };

            if (!$image) {
                $this->warn("  Could not decode: {$file->getFilename()}");
                continue;
            }

            // Convert to true color if not already
            if (!imageistruecolor($image)) {
                imagepalettetotruecolor($image);
            }

            // Save as WebP with 80 quality
            $success = imagewebp($image, $webpPath, 80);
            imagedestroy($image);

            if ($success) {
                $origSize = filesize($file->getPathname());
                $webpSize = filesize($webpPath);
                $saved = $origSize > 0 ? round((1 - $webpSize / $origSize) * 100) : 0;
                $this->line("  ✅ {$file->getFilename()} → .webp ({$saved}% smaller)");
                $converted++;
            } else {
                $this->warn("  ❌ Failed: {$file->getFilename()}");
            }
        }

        $this->info("\n✅ Converted {$converted} images to WebP ({$skipped} already existed)");
        return self::SUCCESS;
    }
}
