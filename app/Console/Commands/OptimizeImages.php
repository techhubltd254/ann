<?php

namespace App\Console\Commands;

use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
        {--dry-run : Show what would be processed without making changes}
        {--path= : Specific relative path to optimize (e.g. counties, room3d)}
        {--force : Re-optimize already optimized images}';

    protected $description = 'Optimize all images — resize, compress, generate WebP';

    public function handle(ImageOptimizer $optimizer): int
    {
        $disk = Storage::disk('public');
        $root = $disk->path('');

        $directories = $this->option('path')
            ? [$root . '/' . $this->option('path')]
            : array_filter([
                $root . '/counties',
                $root . '/room3d',
                $root . '/screens',
            ], 'is_dir');

        if (empty($directories)) {
            $this->warn('No image directories found.');
            return Command::SUCCESS;
        }

        $this->info('=== KICC Image Optimizer ===');
        $this->newLine();

        $totalProcessed = 0;
        $totalSaved = 0;

        foreach ($directories as $dir) {
            $rel = str_replace($root, '', $dir);
            $this->line(" Scanning: <fg=yellow>$rel</>");

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            $count = 0;
            $saved = 0;

            foreach ($files as $file) {
                $ext = strtolower($file->getExtension());
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;

                $relPath = str_replace($root . '/', '', $file->getPathname());

                if (str_ends_with($relPath, '.webp') && !$this->option('force')) continue;

                $origSize = $file->getSize();
                $origMb = round($origSize / 1048576, 2);

                if ($this->option('dry-run')) {
                    $count++;
                    $this->line("   [DRY] <fg=cyan>$relPath</> ({$origMb} MB)");
                    continue;
                }

                $this->output->write("   Processing: <fg=cyan>" . Str::limit($relPath, 60) . "</> ({$origMb} MB)... ");

                try {
                    $results = $optimizer->optimize($file->getPathname(), $file->getPath());
                    $newPath = $results[0] ?? null;

                    if ($newPath && $newPath !== $file->getPathname() && file_exists($newPath)) {
                        $newSize = filesize($newPath);
                        $savedBytes = $origSize - $newSize;
                        $saved += $savedBytes;
                        $pct = $origSize > 0 ? round(($savedBytes / $origSize) * 100) : 0;
                        $this->line("<fg=green>✓</> saved {$pct}% (" . number_format($savedBytes / 1048576, 2) . " MB)");
                    } else {
                        $this->line("<fg=yellow>✓</> already optimal");
                    }
                } catch (\Throwable $e) {
                    $this->error("✗ {$e->getMessage()}");
                }

                $count++;
            }

            $totalProcessed += $count;
            $totalSaved += $saved;
            $this->newLine();
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Directories scanned', count($directories)],
                ['Images processed', (string)$totalProcessed],
                ['Total space saved', number_format($totalSaved / 1048576, 2) . ' MB'],
                ['WebP versions', $this->option('dry-run') ? 'N/A' : 'Generated for supported images'],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('This was a dry run — run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }
}