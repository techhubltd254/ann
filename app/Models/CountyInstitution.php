<?php

namespace App\Models;

use App\Services\DataAvailabilityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CountyInstitution extends Model
{
    protected $fillable = [
        'county_id', 'name', 'type', 'description', 'location', 'phone', 'email', 'website',
        'student_count', 'is_published', 'slug', 'user_id', 'logo_url', 'cover_image_url',
        'headquarters', 'founded_year', 'lat', 'lng', 'social_links', 'story',
        'production_chain', 'sector_mappings', 'products', 'videos', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'production_chain' => 'array',
            'sector_mappings' => 'array',
            'products' => 'array',
            'videos' => 'array',
            'is_published' => 'boolean',
            'founded_year' => 'integer',
            'lat' => 'decimal:6',
            'lng' => 'decimal:6',
            'synced_at' => 'datetime',
        ];
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sectorEntities()
    {
        return $this->hasMany(SectorEntity::class, 'entity_id')
            ->whereIn('entity_type', [CountyInstitution::class, 'institution']);
    }

    public function mediaAssets()
    {
        return $this->morphMany(MediaAsset::class, 'owner');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function syncSummary(): array
    {
        return [
            'entities' => $this->sectorEntities()->count(),
            'products' => \App\Models\CountyProduct::where('county_id', $this->county_id)
                ->where('name', 'like', '%' . $this->name . '%')->count(),
            'videos' => $this->mediaAssets()->count(),
            'synced_at' => $this->synced_at,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $i) {
            if (empty($i->slug)) {
                $i->slug = Str::slug($i->name) . '-' . Str::lower(Str::random(4));
            }
            $i->countyId ??= $i->county_id;
        });

        static::saved(function (self $i) {
            $dav = app(DataAvailabilityService::class);
            $dav->bustInstitution($i->id);
            if ($i->county_id) {
                $dav->bustCounty($i->county_id);
                if ($i->relationLoaded('sectorEntities')) {
                    foreach ($i->sectorEntities as $se) {
                        $dav->bustSector($i->county_id, $se->sector_id);
                    }
                }
            }
        });

        static::deleted(function (self $i) {
            $dav = app(DataAvailabilityService::class);
            $dav->bustInstitution($i->id);
            if ($i->county_id) {
                $dav->bustCounty($i->county_id);
            }
        });
    }
}
