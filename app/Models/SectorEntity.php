<?php

namespace App\Models;

use App\Services\DataAvailabilityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SectorEntity extends Model
{
    protected $fillable = ['county_id', 'countyId', 'sector_id', 'entity_type', 'entityType', 'entity_id', 'entityId', 'name', 'description', 'sector_type', 'capture_status', 'captureStatus', 'sponsor_funder_tag', 'sponsorFunderTag', 'latitude', 'longitude', 'contact_info', 'social_links', 'is_published', 'isPublished', 'language_primary', 'languagePrimary', 'tags', 'verification_owner', 'verificationOwner', 'verification_date', 'verificationDate'];
    protected function casts(): array { return ['contact_info' => 'json', 'social_links' => 'json', 'tags' => 'json', 'is_published' => 'boolean', 'verification_date' => 'datetime']; }
    public function county() { return $this->belongsTo(County::class); }
    public function sector() { return $this->belongsTo(Sector::class); }
    public function entity() { return $this->morphTo(); }

    protected static function booted(): void
    {
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
