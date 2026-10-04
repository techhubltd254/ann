<?php

namespace App\Services;

use App\Models\County;
use App\Models\CountyInstitution;
use App\Models\CountyProduct;
use App\Models\CountyTourismAttraction;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductImage;
use App\Models\Marketplace\ProductVariant;
use App\Models\MediaAsset;
use App\Models\Sector;
use App\Models\SectorEntity;
use App\Events\GenericDomainEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * InstitutionSyncService — the auto-populate algorithm.
 *
 * Takes the master institution record (story, production chain, sector
 * mappings, products, videos) and distributes it across the county portal
 * and the global marketplace. Idempotent — re-running never duplicates.
 */
class InstitutionSyncService
{
    public const ENTITY_TYPE = 'institution';

    public function sync(CountyInstitution $institution): array
    {
        $county = $institution->county;
        $summary = [
            'sectors' => 0,
            'entities' => 0,
            'county_products' => 0,
            'marketplace_products' => 0,
            'attractions' => 0,
            'videos' => 0,
        ];

        DB::transaction(function () use ($institution, $county, &$summary) {
            $mappings = $institution->sector_mappings ?? [];
            foreach ($mappings as $mapping) {
                $sector = $this->resolveSector($mapping['sector_slug'] ?? null);
                if (!$sector) {
                    Log::warning("InstitutionSync: sector not found for slug {$mapping['sector_slug']}");
                    continue;
                }

                // 1. Link sector to county if missing
                if (!$county->sectors()->where('sector_id', $sector->id)->exists()) {
                    $county->sectors()->attach($sector->id, ['display_on_tile' => 'yes']);
                } else {
                    $county->sectors()->updateExistingPivot($sector->id, ['display_on_tile' => 'yes']);
                }
                $summary['sectors']++;

                // 2. Upsert SectorEntity for this mapping
                $entity = $this->upsertSectorEntity($institution, $county, $sector, $mapping);
                $summary['entities']++;

                // 3. Attraction from mapping if it has an entry fee
                if (($mapping['entry_fee'] ?? 0) > 0 && !empty($mapping['entry_name'])) {
                    $this->upsertAttraction($institution, $county, $mapping);
                    $summary['attractions']++;
                }

                // 4. Videos attached to this entity
                $summary['videos'] += $this->attachEntityVideos($institution, $entity, $mapping['sector_slug'] ?? null);
            }

            // 5. Products → county commerce + global marketplace
            $products = $institution->products ?? [];
            foreach ($products as $product) {
                $this->upsertCountyProduct($institution, $county, $product);
                $this->upsertMarketplaceProduct($institution, $county, $product);
                $summary['county_products']++;
                $summary['marketplace_products']++;
            }

            // 6. Institution-level videos (no sector target)
            $summary['videos'] += $this->attachInstitutionVideos($institution);

            // 7. Cleanup stale derived data when mappings/products removed
            $this->cleanup($institution, $county);

            $institution->syncing = true;
            $institution->forceFill(['synced_at' => now()])->save();
            $institution->syncing = false;
        });

        try {
            event(new GenericDomainEvent('institution_synced', [
                'institution' => $institution->slug,
                'county' => $county->slug,
                'summary' => $summary,
            ], n8nEventName: 'institution_synced'));;
        } catch (\Throwable $e) {
            Log::warning('N8n fire failed for institution sync: ' . $e->getMessage());
        }

        $this->bustCountyCache($county);

        return $summary;
    }

    protected function bustCountyCache(County $county): void
    {
        try {
            $county->loadMissing('sectors');
            $allSectorIds = $county->sectors->pluck('id');

            foreach ([
                "kicc_county_sector_counts_{$county->id}",
                "kicc_county_attractions_{$county->id}",
                "kicc_county_hotels_{$county->id}",
                "kicc_county_products_{$county->id}",
                "kicc_county_exhibitions_{$county->id}",
                "kicc_county_linked_sectors_{$county->id}",
                "kicc_county_sectors_{$county->id}_v2",
                "tile_media_ids_{$county->id}",
                "county_pins_{$county->id}",
                'kicc_counties_index',
                'kicc_home_page_data_v2',
            ] as $key) {
                \Illuminate\Support\Facades\Cache::forget($key);
            }

            // Bump version for ALL sectors + their aliases so entity lists
            // (kicc_county_sector_items_{country}_{sector}_{version}_{page})
            // are recomputed on next request.
            foreach ($allSectorIds as $sid) {
                \Illuminate\Support\Facades\Cache::increment("kicc_sector_version_{$county->id}_{$sid}");
            }

            // Also forget any dav:* keys used by DataAvailabilityService
            \Illuminate\Support\Facades\Cache::forget("dav:county:{$county->id}");
            foreach ($allSectorIds as $sid) {
                \Illuminate\Support\Facades\Cache::forget("dav:sector:{$county->id}:{$sid}");
            }

            // Clear sector pitches so they recompute with new data
            try { \App\Services\SectorPitchService::clearCache($county); } catch (\Throwable) {}
        } catch (\Throwable $e) {
            Log::warning('Cache bust failed: ' . $e->getMessage());
        }
    }

    protected function resolveSector(?string $slug): ?Sector
    {
        if (!$slug) return null;

        // Try exact slug match, then fuzzy by name
        $sector = Sector::where('slug', $slug)->first();
        if ($sector) return $sector;

        $sector = Sector::where('name', $slug)->first();
        if ($sector) return $sector;

        // Map canonical names to group slugs (tourism/agriculture/commerce...)
        $map = [
            'tourism' => ['tourism', 'Tourism', 'Tourism, Culture & Heritage'],
            'agriculture' => ['agriculture', 'farms', 'Agriculture', 'Agriculture, Livestock & Fisheries'],
            'commerce' => ['products', 'commerce', 'Commerce & End Products', 'Trade, Industry & Cooperatives'],
            'hospitality' => ['hotels', 'hospitality', 'Hospitality'],
            'education' => ['institutions', 'education', 'Education'],
            'health' => ['health', 'healthcare', 'Healthcare'],
            'culture' => ['culture', 'Culture'],
            'transport' => ['transport', 'Transport'],
        ];

        $slugLower = Str::lower($slug);
        foreach ($map as $canonical => $aliases) {
            if (in_array($slugLower, array_map('strtolower', $aliases), true)) {
                // Match by exact slug/name, or LIKE prefix (sector slugs often have -1 suffixes)
                $sector = Sector::whereIn('slug', $aliases)
                    ->orWhereIn('name', $aliases)
                    ->first();
                if ($sector) return $sector;

                foreach ($aliases as $alias) {
                    $sector = Sector::where('slug', 'like', $alias . '%')->first();
                    if ($sector) return $sector;
                }
            }
        }

        return null;
    }

    protected function upsertSectorEntity(CountyInstitution $i, County $county, Sector $sector, array $mapping): SectorEntity
    {
        $entity = SectorEntity::where('county_id', $county->id)
            ->where('sector_id', $sector->id)
            ->whereIn('entity_type', [\App\Models\CountyInstitution::class, 'institution'])
            ->where('entity_id', $i->id)
            ->first();

        $description = $mapping['description'] ?? Str::limit($i->story ?? $i->description ?? '', 240);

        if ($entity) {
            $entity->update([
                'name' => $mapping['entry_name'] ?? $i->name,
                'description' => $description,
                'contact_info' => [
                    'phone' => $i->phone,
                    'email' => $i->email,
                    'website' => $i->website,
                    'location' => $mapping['location'] ?? $i->location,
                ],
                'latitude' => $i->lat,
                'longitude' => $i->lng,
                'tags' => [$sector->slug, Str::slug($i->name)],
                'is_published' => $i->is_published,
                'entity_type' => \App\Models\CountyInstitution::class, // normalize
            ]);
            return $entity;
        }

        return SectorEntity::create([
            'county_id' => $county->id,
            'countyId' => $county->id,
            'sector_id' => $sector->id,
            'entity_type' => \App\Models\CountyInstitution::class,
            'entity_id' => $i->id,
            'name' => $mapping['entry_name'] ?? $i->name,
            'description' => $description,
            'sector_type' => $sector->slug,
            'capture_status' => 'none',
            'captureStatus' => 'none',
            'contact_info' => [
                'phone' => $i->phone,
                'email' => $i->email,
                'website' => $i->website,
                'location' => $mapping['location'] ?? $i->location,
            ],
            'latitude' => $i->lat,
            'longitude' => $i->lng,
            'tags' => [$sector->slug, Str::slug($i->name)],
            'is_published' => $i->is_published,
            'isPublished' => $i->is_published,
        ]);
    }

    protected function upsertAttraction(CountyInstitution $i, County $county, array $mapping): void
    {
        $name = $mapping['entry_name'];
        $attr = CountyTourismAttraction::where('county_id', $county->id)->where('name', $name)->first();

        $data = [
            'county_id' => $county->id,
            'name' => $name,
            'description' => $mapping['description'] ?? $i->story,
            'category' => ucfirst($mapping['entry_type'] ?? 'tour'),
            'location' => $mapping['location'] ?? $i->location,
            'entry_fee' => $mapping['entry_fee'] ?? 0,
            'latitude' => $i->lat,
            'longitude' => $i->lng,
            'contact' => $i->phone,
            'is_published' => $i->is_published,
        ];

        if ($attr) {
            $attr->update($data);
        } else {
            CountyTourismAttraction::create($data);
        }
    }

    protected function upsertCountyProduct(CountyInstitution $i, County $county, array $product): void
    {
        $name = $product['name'];
        $ownerId = $i->user_id ?? null;
        $cp = CountyProduct::where('county_id', $county->id)
            ->when($ownerId, fn ($q) => $q->where('user_id', $ownerId))
            ->where('name', $name)
            ->first();

        $data = [
            'county_id' => $county->id,
            'user_id' => $ownerId ?? 0,
            'name' => $name,
            'description' => $product['description'] ?? ($i->name . ' product'),
            'category' => $product['category'] ?? 'Food',
            'image_url' => array_key_exists('image_url', $product) ? $product['image_url'] : ($cp->image_url ?? null),
            'video_url' => array_key_exists('video_url', $product) ? $product['video_url'] : ($cp->video_url ?? null),
            'videos' => array_key_exists('videos', $product) ? $product['videos'] : ($cp->videos ?? null),
            'price' => $product['price'] ?? 0,
            'unit' => $product['unit'] ?? 'unit',
            'booking_type' => 'order',
            'status' => 'available',
            'is_published' => true,
        ];

        if ($cp) {
            $cp->update($data);
        } else {
            CountyProduct::create($data);
        }
    }

    protected function upsertMarketplaceProduct(CountyInstitution $i, County $county, array $product): void
    {
        $ownerId = $i->user_id ?? null;
        $name = $product['name'];
        $slug = Str::slug($i->name . ' ' . $name . ' ' . $county->slug);

        $mp = Product::withTrashed()
            ->where('county_id', $county->id)
            ->where('slug', $slug)
            ->first();

        $categoryId = $this->resolveCategoryId($product['category'] ?? null);

        $data = [
            'county_id' => $county->id,
            'user_id' => $ownerId ?? 0,
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => $product['description'] ?? ($i->name . ' — ' . $name),
            'short_description' => Str::limit($product['description'] ?? ($i->name . ' — ' . $name), 120),
            'sku' => 'KICC-INS-' . strtoupper(Str::random(6)),
            'unit' => $product['unit'] ?? 'unit',
            'status' => 'active',
            'is_featured' => true,
            // Preserve existing media when the product array doesn't specify it —
            // otherwise every re-sync wipes uploaded videos.
            'video_url' => array_key_exists('video_url', $product) ? $product['video_url'] : ($mp->video_url ?? null),
            'videos' => array_key_exists('videos', $product) ? $product['videos'] : ($mp->videos ?? null),
        ];

        if ($mp) {
            if ($mp->trashed()) $mp->restore();
            $mp->update($data);
        } else {
            $mp = Product::create($data);
        }

        // Variant
        $variant = $mp->variants()->firstOrNew(['name' => 'Standard ' . ($product['unit'] ?? 'unit')]);
        $variant->fill([
            'sku' => $mp->sku . '-V1',
            'price' => $product['price'] ?? 0,
            'stock' => $product['stock'] ?? 100,
            'is_active' => true,
            'image_url' => $product['image_url'] ?? null,
        ])->save();

        // Image
        if (!empty($product['image_url'])) {
            ProductImage::updateOrCreate(
                ['product_id' => $mp->id, 'url' => $product['image_url']],
                ['product_id' => $mp->id, 'url' => $product['image_url'], 'sort_order' => 0]
            );
        }
    }

    protected function resolveCategoryId(?string $categoryName): ?int
    {
        if (!$categoryName) return null;
        $cat = \App\Models\Marketplace\ProductCategory::where('name', $categoryName)->first();
        if ($cat) return $cat->id;

        // Try fuzzy
        $cat = \App\Models\Marketplace\ProductCategory::where('name', 'like', '%' . $categoryName . '%')->first();
        if ($cat) return $cat->id;

        // Create fallback category
        return \App\Models\Marketplace\ProductCategory::create([
            'name' => $categoryName,
            'slug' => Str::slug($categoryName),
            'is_active' => true,
        ])->id;
    }

    protected function attachEntityVideos(CountyInstitution $i, SectorEntity $entity, string $sectorSlug): int
    {
        $count = 0;
        $videos = $i->videos ?? [];

        foreach ($videos as $video) {
            $targetKey = $video['entity_key'] ?? null;
            // Attach if video targets this sector slug OR has no specific target and this is the first entity
            $matchesSector = !$targetKey || $targetKey === $sectorSlug;
            if (!$matchesSector) continue;

            $asset = MediaAsset::where('owner_type', SectorEntity::class)
                ->where('owner_id', $entity->id)
                ->where('slot', '4d_video')
                ->where('path', $video['path'] ?? '')
                ->first();

            if (!$asset && !empty($video['path'])) {
                MediaAsset::create([
                    'uuid' => (string) Str::uuid(),
                    'owner_id' => $entity->id,
                    'owner_type' => SectorEntity::class,
                    'slot' => '4d_video',
                    'disk' => 'r2',
                    'path' => $video['path'],
                    'original_name' => $video['title'] ?? 'institution-video.mp4',
                    'mime' => $video['mime'] ?? 'video/mp4',
                    'kind' => 'video',
                    'size_bytes' => $video['size_bytes'] ?? 0,
                    'status' => 'ready',
                    'metadata' => ['description' => $video['description'] ?? ''],
                ]);
                $count++;
            }
        }

        return $count;
    }

    protected function attachInstitutionVideos(CountyInstitution $i): int
    {
        $count = 0;
        $videos = $i->videos ?? [];

        foreach ($videos as $video) {
            // Only videos without a sector entity target attach at institution level
            if (!empty($video['entity_key'])) continue;

            $asset = MediaAsset::where('owner_type', CountyInstitution::class)
                ->where('owner_id', $i->id)
                ->where('slot', 'institution_video')
                ->where('path', $video['path'] ?? '')
                ->first();

            if (!$asset && !empty($video['path'])) {
                MediaAsset::create([
                    'uuid' => (string) Str::uuid(),
                    'owner_id' => $i->id,
                    'owner_type' => CountyInstitution::class,
                    'slot' => 'institution_video',
                    'disk' => 'r2',
                    'path' => $video['path'],
                    'original_name' => $video['title'] ?? 'institution-video.mp4',
                    'mime' => $video['mime'] ?? 'video/mp4',
                    'kind' => 'video',
                    'size_bytes' => $video['size_bytes'] ?? 0,
                    'status' => 'ready',
                    'metadata' => ['description' => $video['description'] ?? ''],
                ]);
                $count++;
            }
        }

        return $count;
    }

    protected function cleanup(CountyInstitution $i, County $county): void
    {
        // Stale SectorEntities — remove those whose mapping was removed
        $keptSectorIds = collect($i->sector_mappings ?? [])
            ->map(fn ($m) => $this->resolveSector($m['sector_slug'] ?? null)?->id)
            ->filter();

        SectorEntity::where('county_id', $county->id)
            ->whereIn('entity_type', [\App\Models\CountyInstitution::class, self::ENTITY_TYPE])
            ->where('entity_id', $i->id)
            ->when($keptSectorIds->isNotEmpty(), fn ($q) => $q->whereNotIn('sector_id', $keptSectorIds))
            ->delete();

        // Stale CountyProducts
        $keptProductNames = collect($i->products ?? [])->pluck('name');
        CountyProduct::where('county_id', $county->id)
            ->where('name', 'like', $i->name . '%')
            ->when($keptProductNames->isNotEmpty(), fn ($q) => $q->whereNotIn('name', $keptProductNames))
            ->delete();
    }

    public static function deleteDerived(CountyInstitution $i): void
    {
        SectorEntity::where('entity_type', self::ENTITY_TYPE)->where('entity_id', $i->id)->delete();
        CountyProduct::where('county_id', $i->county_id)->where('name', 'like', $i->name . '%')->delete();
        Product::where('user_id', $i->user_id ?? -1)->delete();
        MediaAsset::where('owner_type', CountyInstitution::class)->where('owner_id', $i->id)->delete();

        $county = County::find($i->county_id);
        if ($county) {
            (new self())->bustCountyCache($county);
        }
    }
}
