<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectorEntity extends Model
{
    protected $fillable = ['county_id', 'sector_id', 'entity_type', 'entity_id', 'name', 'description', 'sector_type', 'capture_status', 'sponsor_funder_tag', 'latitude', 'longitude', 'contact_info', 'social_links', 'is_published', 'language_primary', 'tags', 'verification_owner', 'verification_date'];
    protected function casts(): array { return ['contact_info' => 'json', 'social_links' => 'json', 'tags' => 'json', 'is_published' => 'boolean', 'verification_date' => 'datetime']; }
    public function county() { return $this->belongsTo(County::class); }
    public function sector() { return $this->belongsTo(Sector::class); }
    public function entity() { return $this->morphTo(); }
}
