<?php

namespace App\Services;

use App\Models\County;
use App\Models\SectorEntity;
use Illuminate\Support\Facades\Cache;

/**
 * SectorPitchService — generates compelling selling-point pitches for each sector
 * based on entity data, video presence, and count. Integrated with the sync
 * algorithm and cached for performance.
 */
class SectorPitchService
{
    /** Default pitches when no data is available */
    protected static array $defaultPitches = [
        'tourism' => 'Discover breathtaking landscapes, scenic trails, and unforgettable adventures waiting for you.',
        'hospitality' => 'Experience warm hospitality, comfortable stays, and exceptional service in the heart of Kenya.',
        'farms' => 'Explore fertile farmlands, sustainable agriculture, and farm-to-table experiences.',
        'agriculture' => 'Explore fertile farmlands, sustainable agriculture, and farm-to-table experiences.',
        'products' => 'Shop authentic Kenyan goods — from fresh produce to handcrafted treasures, direct from producers.',
        'commerce' => 'Shop authentic Kenyan goods — from fresh produce to handcrafted treasures, direct from producers.',
        'education' => 'Home to leading institutions shaping the next generation of innovators and leaders.',
        'institutions' => 'Home to leading institutions shaping the next generation of innovators and leaders.',
        'transport' => 'Well-connected networks driving commerce, tourism, and regional integration.',
        'health' => 'Quality healthcare facilities committed to community well-being and medical excellence.',
        'healthcare' => 'Quality healthcare facilities committed to community well-being and medical excellence.',
        'culture' => 'Rich cultural heritage, traditions, and vibrant community stories passed through generations.',
        'industries' => 'Industrial hubs powering innovation, manufacturing, and economic growth.',
        'energy' => 'Renewable energy initiatives powering sustainable development and green innovation.',
    ];

    public static function generate(County $county, string $sectorSlug, array $sectorData): string
    {
        $cacheKey = "sector_pitch_{$county->id}_{$sectorSlug}";

        return Cache::remember($cacheKey, 3600, function () use ($county, $sectorSlug, $sectorData) {
            $count = $sectorData['count'] ?? 0;
            $name = $sectorData['name'] ?? $sectorSlug;

            // Find the sector model
            $sectorModel = $county->sectors()->where('slug', 'like', $sectorSlug . '%')->first();
            if (!$sectorModel) {
                return self::$defaultPitches[$sectorSlug] ?? "Explore {$name} in {$county->name} County.";
            }

            // Get entities for this sector
            $entities = SectorEntity::where('county_id', $county->id)
                ->where('sector_id', $sectorModel->id)
                ->where('is_published', true)
                ->get();

            if ($entities->isEmpty()) {
                return self::$defaultPitches[$sectorSlug] ?? "Explore {$name} in {$county->name} County.";
            }

            // Count entities with videos
            $entityIds = $entities->pluck('id');
            $videoCount = \App\Models\MediaAsset::where('owner_type', SectorEntity::class)
                ->whereIn('owner_id', $entityIds)
                ->where('slot', '4d_video')
                ->count();

            $instIds = $entities->whereIn('entity_type', [\App\Models\CountyInstitution::class, 'institution', \App\Services\InstitutionSyncService::ENTITY_TYPE])
                ->pluck('entity_id')->unique();
            if ($instIds->isNotEmpty()) {
                $videoCount += \App\Models\MediaAsset::where('owner_type', \App\Models\CountyInstitution::class)
                    ->whereIn('owner_id', $instIds)
                    ->where('slot', 'hero_video')
                    ->count();
            }

            // Pick the best entity name for the pitch
            $firstEntity = $entities->first();
            $entityName = $firstEntity?->name ?? '';

            // Build a dynamic pitch
            $pitches = [];

            if ($count > 0) {
                $pitches[] = "{$count} registered " . strtolower($name);
            }
            if ($videoCount > 0) {
                $pitches[] = "{$videoCount} with video walkthroughs";
            }
            if ($entityName) {
                $pitches[] = "featuring {$entityName}";
            }

            if (!empty($pitches)) {
                return ucfirst(implode(', ', $pitches)) . '.';
            }

            return self::$defaultPitches[$sectorSlug] ?? "Explore {$name} in {$county->name} County.";
        });
    }

    public static function clearCache(County $county): void
    {
        foreach (array_keys(self::$defaultPitches) as $slug) {
            Cache::forget("sector_pitch_{$county->id}_{$slug}");
        }
    }
}