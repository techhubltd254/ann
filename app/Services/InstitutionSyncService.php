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
        $errors = [];


        // Each mapping (sector → entity → attraction → video) is independent.
        // One failing never rolls back another.
        $mappings = $institution->sector_mappings ?? [];
        foreach ($mappings as $mapping) {
            try {
                DB::transaction(function () use ($institution, $county, $mapping, &$summary) {
                    $sector = $this->resolveSector($mapping['sector_slug'] ?? null);
                    if (!$sector) {
                        throw new \Exception("Sector not found for slug: {$mapping['sector_slug']}");
                    }

                    if (!$county->sectors()->where('sector_id', $sector->id)->exists()) {
                        $county->sectors()->attach($sector->id, ['display_on_tile' => 'yes']);
                    } else {
                        $county->sectors()->updateExistingPivot($sector->id, ['display_on_tile' => 'yes']);
                    }
                    $summary['sectors']++;

                    $entity = $this->upsertSectorEntity($institution, $county, $sector, $mapping);
                    $summary['entities']++;

                    if (($mapping['entry_fee'] ?? 0) > 0 && !empty($mapping['entry_name'])) {
                        $this->upsertAttraction($institution, $county, $mapping);
                        $summary['attractions']++;
                    }

                    $summary['videos'] += $this->attachEntityVideos($institution, $entity, $mapping['sector_slug'] ?? null);
                });
            } catch (\Throwable $e) {
                $errors[] = ['mapping' => $mapping['sector_slug'] ?? '?', 'error' => $e->getMessage()];
                Log::warning("InstitutionSync: sector '{$mapping['sector_slug']}' failed: " . $e->getMessage());
            }
        }

        // Products — each product is independent
        $products = $institution->products ?? [];
        foreach ($products as $product) {
            try {
                DB::transaction(function () use ($institution, $county, $product, &$summary) {
                    $this->upsertCountyProduct($institution, $county, $product);
                    $summary['county_products']++;
                });
                DB::transaction(function () use ($institution, $county, $product, &$summary) {
                    $this->upsertMarketplaceProduct($institution, $county, $product);
                    $summary['marketplace_products']++;
                });
            } catch (\Throwable $e) {
                $pn = is_array($product) ? ($product['name'] ?? '?') : '?';
                $errors[] = ['product' => $pn, 'error' => $e->getMessage()];
                Log::warning("InstitutionSync: product '" . $pn . "' failed: " . $e->getMessage());
            }
        }

        // Institution-level videos (no sector target) — isolated
        try {
            $summary['videos'] += $this->attachInstitutionVideos($institution);
        } catch (\Throwable $e) {
            $errors[] = ['video' => 'institution_videos', 'error' => $e->getMessage()];
        }

        // Cleanup stale derived data — isolated
        try {
            $this->cleanup($institution, $county);
        } catch (\Throwable $e) {
            $errors[] = ['task' => 'cleanup', 'error' => $e->getMessage()];
        }

        // Update sync timestamp — always last, bare minimum
        try {
            $institution->syncing = true;
            $institution->forceFill(['synced_at' => now()])->save();
            $institution->syncing = false;
        } catch (\Throwable $e) {
            $errors[] = ['task' => 'save_timestamp', 'error' => $e->getMessage()];
        }

        try {
            event(new GenericDomainEvent('institution_synced', [
                'institution' => $institution->slug,
                'county' => $county->slug,
                'summary' => $summary,
                'errors' => $errors,
            ], n8nEventName: 'institution_synced'));
        } catch (\Throwable $e) {
            Log::warning('N8n fire failed for institution sync: ' . $e->getMessage());
        }

        $this->bustCountyCache($county);

        $summary['errors'] = $errors;
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
            'sectorId' => $sector->id,
            'entity_type' => \App\Models\CountyInstitution::class,
            'entityType' => \App\Models\CountyInstitution::class,
            'entity_id' => $i->id,
            'entityId' => $i->id,
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
            'language_primary' => 'en',
            'languagePrimary' => 'en',
            'capture_target' => 'manual',
        ]);
    }

    protected function upsertAttraction(CountyInstitution $i, County $county, array $mapping): void
    {
        $name = $mapping['entry_name'];
        $attr = CountyTourismAttraction::where('county_id', $county->id)->where('name', $name)->first();

        $data = [
            'county_id' => $county->id,
            'countyId' => $county->id,
            'name' => $name,
            'description' => $mapping['description'] ?? $i->story,
            'category' => ucfirst($mapping['entry_type'] ?? 'tour'),
            'location' => $mapping['location'] ?? $i->location,
            'entry_fee' => $mapping['entry_fee'] ?? 0,
            'latitude' => $i->lat,
            'longitude' => $i->lng,
            'contact' => $i->phone,
            'is_published' => $i->is_published,
            'isPublished' => $i->is_published,
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
        $cp=CountyProduct::where('county_id',$county->id)->where('institution_id',$i->id)->where('name',$name)->first();
        if(!empty($product['county_product_id']))$cp=CountyProduct::where('county_id',$county->id)->where(fn($q)=>$q->where('institution_id',$i->id)->orWhereNull('institution_id'))->find($product['county_product_id'])?:$cp;
        if(!$cp && !empty($product['marketplace_product_id'])){
            $old=Product::where('institution_id',(string)$i->id)->find($product['marketplace_product_id']);
            if($old)$cp=CountyProduct::where('county_id',$county->id)->where('institution_id',$i->id)->where('name',$old->name)->first();
        }


        $data = [
            'county_id' => $county->id,
            'user_id' => $ownerId ?? 0,
            'institution_id' => $i->id,
            'name' => $name,
            'description' => $product['description'] ?? ($i->name . ' product'),
            'category' => $product['category'] ?? 'Food',
            'image_url' => array_key_exists('image_url', $product) ? $product['image_url'] : ($cp->image_url ?? null),
            'video_url' => array_key_exists('video_url', $product) ? $product['video_url'] : ($cp->video_url ?? null),
            'videos' => array_key_exists('videos', $product) ? $product['videos'] : ($cp->videos ?? null),
            'price' => $product['price'] ?? 0,
            'unit' => $product['unit'] ?? 'unit',
            'booking_type' => ($product['price_mode']??'fixed')==='enquiry'?'enquiry':'order',
            'status' => 'available',
            'is_published' => ($product['publication_status']??'active')==='active',
        ];

        if ($cp) {$cp->update($data);}else{$cp=CountyProduct::create($data);}
        $locked=CountyInstitution::lockForUpdate()->findOrFail($i->id);$entries=$locked->products??[];
        foreach($entries as &$e)if((!empty($product['source_key'])&&($e['source_key']??null)===$product['source_key'])||($e['name']??'')===$name)$e['county_product_id']=$cp->id;
        unset($e);$locked->syncing=true;$locked->forceFill(['products'=>$entries])->save();
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

        // Stable institution ownership and source identity survive name edits and reimports.
        if (!empty($product['marketplace_product_id'])) {
            $owned=Product::withTrashed()->where('institution_id',(string)$i->id)->where('county_id',$county->id)->find($product['marketplace_product_id']);
            if($owned)$mp=$owned;
        }
        if (!empty($product['source_key'])) {
            $owned=Product::withTrashed()->where('institution_id',(string)$i->id)->where('sync_key',$product['source_key'])->first();
            if($owned)$mp=$owned;
        }
        if($mp && $mp->institution_id && (string)$mp->institution_id!==(string)$i->id)throw new \RuntimeException('Product belongs to another institution');
        $categoryId = $this->resolveCategoryId($product['category'] ?? 'General');
        $pipelineCode = null;
        // Interlink the product (and its category) to the ledger pipeline so the
        // sector → product → pipeline chain is complete. PipelineRouter routes by
        // category sector → name keywords → HS code, and returns a pipeline code.

        try {
            $router = app(\App\Services\PipelineRouter::class);

            if ($categoryId) {
                $cats = \App\Models\Marketplace\ProductCategory::find($categoryId);
                if ($cats) {
                    // Route this product to its ledger pipeline.
                    $pipelineCode = $router->forProduct(new Product([
                        'name' => $name,
                        'institution_id' => (string) $i->id,
            'county_id' => $county->id,
                        'category_id' => $categoryId,
                    ]));
                    // Resolve a proper *sector* (never a pipeline code) for the
                    // category so sector → product → pipeline chain is complete.
                    $resolver = app(\App\Services\PipelineResolver::class);
                    $catSector = $resolver->forCategory($cats)
                        ?? ($resolver->forCategory(new \App\Models\Marketplace\ProductCategory(['name' => $cats->name, 'slug' => $cats->slug]))
                            ?: null);
                    if (!$catSector) {
                        $catSector = 'trade';
                    }
                    if ($catSector) {
                        $cats->sector = $catSector;
                        $cats->pipeline_code = $router->forSector($catSector);
                        $cats->save();
                        $pipelineCode = $pipelineCode ?: $cats->pipeline_code;
                    }
                }
            }
            $pipelineCode = $pipelineCode ?: $router->forSector('trade');
        } catch (\Throwable $e) {
            Log::warning("InstitutionSync: pipeline routing failed for {$name}: " . $e->getMessage());
        }

        $data = [
            'county_id' => $county->id,
            'user_id' => $ownerId ?? 0,
            'category_id' => $categoryId,
            'institution_id' => (string)$i->id,
            'offering_kind' => $product['offering_kind'] ?? ($mp->offering_kind ?? 'product'),
            'price_mode' => $product['price_mode'] ?? ($mp->price_mode ?? 'fixed'),
            'sync_key' => $product['source_key'] ?? ($mp->sync_key ?? null),
            'source_url' => $product['source_url'] ?? ($mp->source_url ?? null),
            'source_verified_at' => $product['source_verified_at'] ?? ($mp->source_verified_at ?? null),
            'booking_url' => $product['booking_url'] ?? ($mp->booking_url ?? null),
            'offering_details' => $product['offering_details'] ?? ($mp->offering_details ?? []),
            'name' => $name,
            'slug' => $slug,
            'description' => $product['description'] ?? ($i->name . ' — ' . $name),
            'short_description' => Str::limit($product['description'] ?? ($i->name . ' — ' . $name), 120),
            'sku' => $mp->sku ?? ('KICC-INS-' . strtoupper(Str::random(6))),
            'unit' => $product['unit'] ?? 'unit',
            'status' => $product['publication_status'] ?? ($mp->status ?? 'active'),
            'is_featured' => true,
            'pipeline_code' => $pipelineCode ?? ($mp->pipeline_code ?? null),
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

        if($mp->offering_kind==='experience'){
            $details=$mp->offering_details??[];
            \App\Models\InstitutionExperience::updateOrCreate(['product_id'=>$mp->id],['institution_id'=>$i->id,'duration_minutes'=>$details['duration_minutes']??null,'max_guests'=>$details['max_guests']??null,'inclusions'=>$details['inclusions']??[],'requirements'=>$details['requirements']??[]]);
        }
        // Persist identity without replacing concurrent video metadata or scheduling a second sync.
        $locked=CountyInstitution::lockForUpdate()->findOrFail($i->id);$entries=$locked->products??[];
        foreach($entries as &$e)if((!empty($product['source_key'])&&($e['source_key']??null)===$product['source_key'])||($e['name']??'')===$name)$e['marketplace_product_id']=$mp->id;
        unset($e);$locked->syncing=true;$locked->forceFill(['products'=>$entries])->save();
        // Variant
        $variant = $mp->variants()->first() ?? $mp->variants()->make();
        $variant->name='Standard '.($product['unit']??'unit');
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
        $categoryName=$categoryName?:'General';
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
            // Institution-wide videos do not become every sector's footage. A sector must be explicitly named.
            $matchesSector = $targetKey && $targetKey === $sectorSlug;
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
