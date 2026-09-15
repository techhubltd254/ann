<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Services\CacheSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncMediaFromR2 extends Command
{
    protected $signature = 'media:sync-from-r2 {--dry-run : Preview changes without writing}';
    protected $description = 'Scan R2 bucket and rebuild MediaAsset records from metadata sidecar files. No path guessing.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $r2 = Storage::disk('r2');
        $allFiles = $r2->allFiles();
        $isDry = $dryRun ? ' (DRY RUN)' : '';

        $this->info("Scanning " . count($allFiles) . " R2 files{$isDry}");
        $this->newLine();

        // Find all metadata sidecar files (.meta.json)
        $metaFiles = array_filter($allFiles, fn($f) => str_ends_with($f, '.meta.json'));
        $metaPaths = [];
        foreach ($metaFiles as $mf) {
            $targetPath = str_replace('.meta.json', '', $mf);
            try {
                $content = $r2->get($mf);
                if ($content) $metaPaths[$targetPath] = json_decode($content, true);
            } catch (\Throwable $e) {}
        }

        $this->info("  Metadata sidecar files: " . count($metaFiles));
        $this->info("  Files with metadata: " . count($metaPaths));

        // Media files eligible for MediaAsset creation (no metadata files themselves)
        $mediaFiles = array_filter($allFiles, fn($f) =>
            preg_match('/\.(mp4|webm|m3u8|jpg|jpeg|png|webp)$/i', $f) &&
            !str_ends_with($f, '.meta.json')
        );
        $this->info("  Media files: " . count($mediaFiles));
        $this->newLine();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $existingPaths = MediaAsset::pluck('path')->toArray();

        foreach ($mediaFiles as $path) {
            // Determine mime and kind from file extension
            $mime = 'application/octet-stream';
            $kind = 'file';
            if (preg_match('/\.(mp4|webm)$/i', $path)) { $mime = 'video/mp4'; $kind = 'video'; }
            elseif (preg_match('/\.m3u8$/i', $path)) { $mime = 'application/vnd.apple.mpegurl'; $kind = 'video'; }
            elseif (preg_match('/\.(jpg|jpeg)$/i', $path)) { $mime = 'image/jpeg'; $kind = 'image'; }
            elseif (preg_match('/\.png$/i', $path)) { $mime = 'image/png'; $kind = 'image'; }
            elseif (preg_match('/\.webp$/i', $path)) { $mime = 'image/webp'; $kind = 'image'; }

            // Get metadata from sidecar file — this is the SOURCE OF TRUTH
            $meta = $metaPaths[$path] ?? null;

            $asset = MediaAsset::where('path', $path)->first();

            if ($asset) {
                // Only update if metadata provides better info than what we have
                if ($meta && (!$asset->owner_type || $asset->owner_type === 'r2_file')) {
                    $updates = [];
                    if (isset($meta['owner_type']) && $meta['owner_type'] !== $asset->owner_type) $updates['owner_type'] = $meta['owner_type'];
                    if (isset($meta['owner_id']) && $meta['owner_id'] !== $asset->owner_id) $updates['owner_id'] = $meta['owner_id'];
                    if (isset($meta['slot']) && $meta['slot'] !== $asset->slot) $updates['slot'] = $meta['slot'];
                    if ($updates) {
                        if ($isDry) { $this->line("  ~ Would update: {$path}"); }
                        else { $asset->update($updates); }
                        $updated++;
                    } else { $skipped++; }
                } else { $skipped++; }
            } else {
                $assetData = [
                    'uuid' => Str::uuid(),
                    'owner_type' => $meta['owner_type'] ?? 'r2_file',
                    'owner_id' => $meta['owner_id'] ?? 0,
                    'slot' => $meta['slot'] ?? null,
                    'disk' => 'r2',
                    'path' => $path,
                    'original_name' => basename($path),
                    'mime' => $mime,
                    'kind' => $kind,
                    'size_bytes' => 0,
                    'status' => 'ready',
                ];
                try { $assetData['size_bytes'] = $r2->fileSize($path); } catch (\Throwable $e) {}

                if ($isDry) {
                    $this->line("  + Would create: {$path}");
                } else {
                    try {
                        MediaAsset::create($assetData);
                        $created++;
                    } catch (\Exception $e) {
                        $this->error("  ✗ {$path}: " . substr($e->getMessage(), 0, 100));
                    }
                }
            }
        }

        $prefix = $isDry ? '[DRY RUN] ' : '';
        $this->newLine();
        $this->info("{$prefix}Created: {$created}, Updated: {$updated}, Skipped: {$skipped}");
        $this->info("{$prefix}Total MediaAssets: " . MediaAsset::count());

        if (($created > 0 || $updated > 0) && !$isDry) {
            try { app(CacheSyncService::class)->kicc(); $this->info("✓ Caches busted"); } catch (\Throwable $e) {}
        }

        return Command::SUCCESS;
    }
}