<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\County;
use App\Models\CountyFarm;
use App\Models\CountyHealthFacility;
use App\Models\CountyHotel;
use App\Models\CountyCultureSite;
use App\Models\CountyInstitution;
use App\Models\CountyProduct;
use App\Models\CountyTourismAttraction;
use App\Models\CountyTransport;
use App\Models\Exhibition;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductImage;
use App\Models\MediaAsset;
use App\Models\Ministry;
use App\Models\Venue;
use App\Services\MediaFallbackResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillMediaFallbacks extends Command
{
    protected $signature = 'media:backfill-fallbacks {--dry-run : Preview changes without writing} {--limit=100 : Max entities per type}';

    protected $description = 'Auto-diagnose missing media and fill from tree fallback hierarchy (institution → sector → county → peer → branded placeholder)';

    protected array $stats = ['scanned' => 0, 'filled' => 0, 'skipped' => 0, 'errors' => 0];

    public function handle(): int
    {
        $this->info('=== Media Fallback Backfill ===');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        if ($dryRun) $this->warn('DRY RUN — no changes will be written');

        $resolver = app(MediaFallbackResolver::class);

        // Define entity types with their image fields and fallback priority
        $entityTypes = [
            // [Model class, image field, display name, hasDbColumn]
            [Product::class, 'image_url', 'Product', false],
            [CountyInstitution::class, 'logo_url', 'Institution (logo)', true],
            [CountyInstitution::class, 'cover_image_url', 'Institution (cover)', true],
            [CountyTourismAttraction::class, 'image_url', 'Attraction', true],
            [CountyHotel::class, 'image_url', 'Hotel', true],
            [CountyFarm::class, 'image_url', 'Farm', true],
            [CountyTransport::class, 'image_url', 'Transport', true],
            [CountyHealthFacility::class, 'image_url', 'Health Facility', true],
            [CountyCultureSite::class, 'image_url', 'Culture Site', true],
            [CountyProduct::class, 'image_url', 'County Product', true],
            [Exhibition::class, 'cover_image', 'Exhibition', true],
            [Venue::class, 'cover_image', 'Venue', true],
            [Ministry::class, 'logo', 'Ministry', true],
            [Agency::class, 'logo', 'Agency', true],
        ];

        foreach ($entityTypes as [$modelClass, $field, $label, $hasDbColumn]) {
            $this->line(" Scanning {$label}s...");
            $filled = $this->backfillModel($modelClass, $field, $resolver, $dryRun, $limit, $hasDbColumn);
            $this->info("  {$label}: {$filled} filled");
        }

        // Also generate frame derivatives for sector video assets that lack posters
        $this->line(' Generating sector video frames...');
        $frameCount = $this->generateVideoFrames($resolver, $dryRun, $limit);
        $this->info("  Frames generated: {$frameCount}");

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Scanned', $this->stats['scanned']],
                ['Filled', $this->stats['filled']],
                ['Skipped (already had image)', $this->stats['skipped']],
                ['Errors', $this->stats['errors']],
                ['Video frames', $frameCount],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN — no changes were written. Run without --dry-run to apply.');
        }

        return 0;
    }

    protected function backfillModel(string $modelClass, string $field, MediaFallbackResolver $resolver, bool $dryRun, int $limit, bool $hasDbColumn = true): int
    {
        $filled = 0;
        $query = $modelClass::query();

        if (!$hasDbColumn && $modelClass === Product::class) {
            // Product has no image_url column — check for missing ProductImage records
            $query->whereDoesntHave('images');
        } elseif ($hasDbColumn) {
            $query->where(function ($q) use ($field) {
                $q->whereNull($field)->orWhere($field, '')->orWhere($field, 'like', '%default%');
            });
        } else {
            // HasDbColumn is false but not Product — skip
            return 0;
        }

        $entities = $query->limit($limit)->get();
        $this->stats['scanned'] += $entities->count();

        foreach ($entities as $entity) {
            $this->stats['skipped']++;
            try {
                $best = $resolver->resolveTree($entity);
                if (!$best) {
                    $best = $this->generatePlaceholder($entity, $modelClass, $field, $resolver, $hasDbColumn);
                }
                if (!$best) continue;

                $this->stats['skipped']--;
                $this->stats['filled']++;
                $filled++;

                if ($dryRun) {
                    $this->line("   [DRY] {$modelClass}#{$entity->id}: {$field} ← {$best}");
                    continue;
                }

                if (!$hasDbColumn && $modelClass === Product::class) {
                    ProductImage::create([
                        'product_id' => $entity->id,
                        'url' => $best,
                        'is_primary' => true,
                    ]);
                } else {
                    $entity->update([$field => $best]);
                }

                $resolver->bustCache($entity);
                $this->line("   ✅ {$modelClass}#{$entity->id}: {$field} ← {$best}");
            } catch (\Throwable $e) {
                $this->stats['errors']++;
                Log::warning("Backfill failed for {$modelClass}#{$entity->id}: " . $e->getMessage());
            }
        }

        return $filled;
    }

    protected function generateVideoFrames(MediaFallbackResolver $resolver, bool $dryRun, int $limit): int
    {
        $count = 0;
        $assets = MediaAsset::where('kind', 'video')
            ->where('status', 'ready')
            ->whereDoesntHave('derivatives', fn ($q) => $q->where('kind', 'poster'))
            ->limit($limit)
            ->get();

        foreach ($assets as $asset) {
            if ($count >= $limit) break;
            $videoUrl = $asset->mp4Url() ?? $asset->url();
            if (!$videoUrl) continue;

            if ($dryRun) {
                $this->line("   [DRY] Frame for MediaAsset#{$asset->id}: {$videoUrl}");
                $count++;
                continue;
            }

            $frame = $resolver->extractFrame($videoUrl, $asset);
            if ($frame) {
                $count++;
                $this->line("   🎬 Frame generated for MediaAsset#{$asset->id}: {$frame}");
            }
        }

        return $count;
    }

    protected function generatePlaceholder($entity, string $modelClass, string $field, MediaFallbackResolver $resolver, bool $hasDbColumn = true): ?string
    {
        try {
            return $resolver->defaultUrl($entity);
        } catch (\Throwable $e) {
            return null;
        }
    }
}