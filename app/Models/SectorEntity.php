<?php

namespace App\Models;

use App\Services\DataAvailabilityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SectorEntity extends Model
{
    protected $fillable = ['county_id', 'countyId', 'sector_id', 'entity_type', 'entityType', 'entity_id', 'entityId', 'name', 'description', 'sector_type', 'sectorType', 'capture_status', 'captureStatus', 'sponsor_funder_tag', 'sponsorFunderTag', 'latitude', 'longitude', 'contact_info', 'contactInfo', 'social_links', 'socialLinks', 'is_published', 'isPublished', 'language_primary', 'languagePrimary', 'tags', 'verification_owner', 'verificationOwner', 'verification_date', 'verificationDate'];
    protected function casts(): array { return ['contact_info' => 'json', 'social_links' => 'json', 'tags' => 'json', 'is_published' => 'boolean', 'verification_date' => 'datetime']; }
    public function county() { return $this->belongsTo(County::class); }
    public function sector() { return $this->belongsTo(Sector::class); }
    public function entity() { return $this->morphTo(); }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // TiDB raw-schema table uses camelCase columns that are NOT NULL
            // without defaults. Ensure both snake + camel variants are set.
            $map = [
                'county_id' => 'countyId',
                'entity_id' => 'entityId',
                'entity_type' => 'entityType',
                'capture_status' => 'captureStatus',
                'sponsor_funder_tag' => 'sponsorFunderTag',
                'is_published' => 'isPublished',
                'language_primary' => 'languagePrimary',
                'verification_owner' => 'verificationOwner',
                'verification_date' => 'verificationDate',
                'contact_info' => 'contactInfo',
                'social_links' => 'socialLinks',
            ];
            foreach ($map as $snake => $camel) {
                if ($m->getAttribute($camel) === null) {
                    $m->setAttribute($camel, $m->getAttribute($snake));
                }
                if ($m->getAttribute($snake) === null && $m->getAttribute($camel) !== null) {
                    $m->setAttribute($snake, $m->getAttribute($camel));
                }
            }
        });

        $invalidate = function (self $m) {
            $dav = app(DataAvailabilityService::class);
            if ($m->county_id) {
                $dav->bustCounty($m->county_id);
            }
            if ($m->county_id && $m->sector_id) {
                $dav->bustSector($m->county_id, $m->sector_id);
            }
        };
        static::saved(fn (self $m) => $invalidate($m));
        static::deleted(fn (self $m) => $invalidate($m));
    }
}
