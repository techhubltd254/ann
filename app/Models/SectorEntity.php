<?php

namespace App\Models;

use App\Services\DataAvailabilityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SectorEntity extends Model
{
    protected $fillable = ['county_id', 'sector_id', 'entity_type', 'entity_id', 'name', 'description', 'sector_type', 'capture_status', 'sponsor_funder_tag', 'latitude', 'longitude', 'contact_info', 'social_links', 'is_published', 'language_primary', 'tags', 'verification_owner', 'verification_date'];
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
