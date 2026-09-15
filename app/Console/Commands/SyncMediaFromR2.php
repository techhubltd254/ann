<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\MediaAsset;
use App\Models\MediaDerivative;
use App\Models\Sector;
use App\Services\CacheSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncMediaFromR2 extends Command
{
    protected $signature = 'media:sync-from-r2 {--dry-run : Preview changes without writing}';
    protected $description = 'Scan R2 bucket and rebuild MediaAsset records with proper owners/slots';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $r2 = Storage::disk('r2');
        $allFiles = $r2->allFiles();
        $isDry = $dryRun ? ' (DRY RUN)' : '';

        $this->info("Scanning " . count($allFiles) . " R2 files{$isDry}");
        $this->newLine();

        $videoFiles = array_filter($allFiles, fn($f) => preg_match('/\.(mp4|webm)$/i', $f));
        $hlsFiles = array_filter($allFiles, fn($f) => preg_match('/\.(m3u8)$/i', $f));
        $imageFiles = array_filter($allFiles, fn($f) => preg_match('/\.(jpg|jpeg|png|webp)$/i', $f));
        $derivativeFiles = array_filter($allFiles, fn($f) => str_starts_with($f, 'derivatives/'));
        $posterFiles = array_filter($allFiles, fn($f) => str_contains($f, '-poster.'));

        $this->info("  Videos: " . count($videoFiles));
        $this->info("  HLS playlists: " . count($hlsFiles));
        $this->info("  Images: " . count($imageFiles));
        $this->info("  Derivatives: " . count($derivativeFiles));
        $this->info("  Posters: " . count($posterFiles));
        $this->newLine();

        // Build path → owner mapping
        $pathMap = $this->buildPathMap($videoFiles, $imageFiles, $hlsFiles, $derivativeFiles, $posterFiles);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $existingPaths = MediaAsset::pluck('path')->toArray();

        foreach ($pathMap as $path => $data) {
            $asset = MediaAsset::where('path', $path)->first();
            $slotLabel = $data['slot'] ?? 'none';
            $sizeBytes = 0;
            try { $sizeBytes = $r2->fileSize($path); } catch (\Throwable $e) {}

            if ($asset) {
                // Update existing
                $changes = [];
                foreach (['owner_type', 'owner_id', 'slot', 'disk', 'kind', 'mime', 'status'] as $field) {
                    if (isset($data[$field]) && ($asset->{$field} ?? null) !== $data[$field]) {
                        $changes[$field] = $data[$field];
                    }
                }
                if ($changes) {
                    if ($isDry) {
                        $this->line("  ~ Would update: {$path}");
                    } else {
                        $asset->update($changes);
                    }
                    $updated++;
                }
                $skipped++;
            } else {
                // Create new
                if ($isDry) {
                    $this->line("  + Would create: {$path} → {$slotLabel}");
                } else {
                    try {
                        $asset = MediaAsset::create([
                            'uuid' => Str::uuid(),
                            'owner_type' => $data['owner_type'] ?? 'r2_file',
                            'owner_id' => $data['owner_id'] ?? 0,
                            'slot' => $data['slot'] ?? null,
                            'disk' => 'r2',
                            'path' => $path,
                            'original_name' => basename($path),
                            'mime' => $data['mime'] ?? 'application/octet-stream',
                            'kind' => $data['kind'] ?? 'video',
                            'size_bytes' => $sizeBytes,
                            'status' => 'ready',
                        ]);
                        $created++;

                        // Create derivative for mp4 videos
                        if (preg_match('/\.mp4$/i', $path) && $asset) {
                            MediaDerivative::firstOrCreate([
                                'asset_id' => $asset->id,
                                'kind' => 'video_mp4',
                            ], [
                                'path' => $path,
                                'mime' => 'video/mp4',
                                'size_bytes' => $sizeBytes,
                            ]);
                        }

                        // Link poster if exists
                        $posterPath = preg_replace('/\.(mp4|webm)$/i', '-poster.jpg', $path);
                        if ($r2->exists($posterPath)) {
                            MediaDerivative::firstOrCreate([
                                'asset_id' => $asset->id,
                                'kind' => 'poster',
                            ], [
                                'path' => $posterPath,
                                'mime' => 'image/jpeg',
                                'size_bytes' => $r2->fileSize($posterPath) ?? 0,
                            ]);
                        }

                        // Link poster via derivatives directory
                        $derivativePoster = "derivatives/poster/{$path}";
                        if ($r2->exists($derivativePoster)) {
                            MediaDerivative::firstOrCreate([
                                'asset_id' => $asset->id,
                                'kind' => 'poster',
                            ], [
                                'path' => $derivativePoster,
                                'mime' => r2->mimeType($derivativePoster) ?? 'image/jpeg',
                                'size_bytes' => $r2->fileSize($derivativePoster) ?? 0,
                            ]);
                        }
                    } catch (\Exception $e) {
                        $this->error("  ✗ {$path}: {$e->getMessage()}");
                    }
                }
            }
        }

        $prefix = $isDry ? '[DRY RUN] ' : '';
        $this->newLine();
        $this->info("{$prefix}Created: {$created}, Updated: {$updated}, Skipped: {$skipped}");
        $this->info("{$prefix}Total MediaAssets: " . MediaAsset::count());

        // Bust caches if changes were made
        if (($created > 0 || $updated > 0) && !$isDry) {
            try {
                app(CacheSyncService::class)->kicc();
                $this->info("✓ Caches busted");
            } catch (\Throwable $e) {
                $this->warn("Cache bust failed: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }

    protected function buildPathMap(array $videos, array $images, array $hls, array $derivatives, array $posters): array
    {
        $map = [];
        $counties = County::pluck('id', 'slug')->toArray();
        $sectors = Sector::pluck('id', 'slug')->toArray();

        foreach ($videos as $path) {
            $data = $this->resolveOwnerFromPath($path, $counties, $sectors);
            if ($data) {
                $map[$path] = $data;
                // Also register poster derivatives
                $posterPath = preg_replace('/\.(mp4|webm)$/i', '-poster.jpg', $path);
                $dp = "derivatives/poster/" . preg_replace('/\.(mp4|webm)$/i', '.jpg', $path);
                foreach ([$posterPath, $dp] as $pp) {
                    if (in_array($pp, $posters)) {
                        $map[$pp] = [
                            'owner_type' => $data['owner_type'],
                            'owner_id' => $data['owner_id'],
                            'slot' => $data['slot'],
                            'disk' => 'r2',
                            'kind' => 'image',
                            'mime' => 'image/jpeg',
                            'status' => 'ready',
                        ];
                    }
                }
            }
        }

        return $map;
    }

    protected function resolveOwnerFromPath(string $path, array $counties, array $sectors): ?array
    {
        $mime = 'video/mp4';
        if (preg_match('/\.webm$/i', $path)) $mime = 'video/webm';

        // Landing page hero
        if (str_starts_with($path, '4d/kicc_hero') || str_starts_with($path, 'landing/hero/') || str_starts_with($path, 'kicc-hero')) {
            return ['owner_type' => 'landing_page', 'owner_id' => 1, 'slot' => 'hero_video', 'mime' => $mime, 'kind' => 'video', 'status' => 'ready'];
        }

        // County hero/showcase/4d videos
        if (preg_match('#^counties/([^/]+)/(showcase|hero|4d|hover|sector-videos/)#', $path, $m)) {
            $slug = $m[1];
            $countyId = $counties[$slug] ?? null;
            if ($countyId) {
                $slot = str_contains($path, 'sector-videos/') ? 'sector_video' : (str_contains($path, 'hover/') ? 'hover_video' : (str_contains($path, '4d/') ? '4d_video' : 'hero_video'));
                return ['owner_type' => 'county', 'owner_id' => $countyId, 'slot' => $slot, 'mime' => $mime, 'kind' => 'video', 'status' => 'ready'];
            }
        }

        // Muranga direct paths (files without counties/ prefix)
        if (str_starts_with($path, 'muranga/')) {
            $murangaId = $counties['muranga'] ?? 21;
            $slot = str_contains($path, 'sector-videos/') ? 'sector_video' : (str_contains($path, 'hover/') ? 'hover_video' : (str_contains($path, '4d/') ? '4d_video' : 'hero_video'));
            return ['owner_type' => 'county', 'owner_id' => $murangaId, 'slot' => $slot, 'mime' => $mime, 'kind' => 'video', 'status' => 'ready'];
        }

        // Institution videos
        if (str_starts_with($path, 'institutions/')) {
            $parts = explode('/', $path);
            $slug = $parts[1] ?? '';
            $slot = str_contains($path, '4d/') ? '4d_video' : 'hero_video';
            return ['owner_type' => 'institution', 'owner_id' => crc32($slug), 'slot' => $slot, 'mime' => $mime, 'kind' => 'video', 'status' => 'ready'];
        }

        // KICC 4D clips (sector videos)
        if (preg_match('#^kicc/4d/clips/([^/]+)\.#', $path, $m)) {
            $sectorSlug = $m[1];
            $sectorId = $sectors[$sectorSlug] ?? crc32($sectorSlug);
            return ['owner_type' => 'sector', 'owner_id' => $sectorId, 'slot' => '4d_video', 'mime' => $mime, 'kind' => 'video', 'status' => 'ready'];
        }

        // Derivatives — owner resolved from derivative metadata
        if (str_starts_with($path, 'derivatives/')) {
            return ['owner_type' => 'r2_file', 'owner_id' => 0, 'slot' => null, 'mime' => $mime, 'kind' => 'video', 'status' => 'ready'];
        }

        return ['owner_type' => 'r2_file', 'owner_id' => 0, 'slot' => null, 'mime' => $mime, 'kind' => 'video', 'status' => 'ready'];
    }
}